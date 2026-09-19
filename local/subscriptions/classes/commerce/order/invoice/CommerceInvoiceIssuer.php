<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\order\invoice;

defined('MOODLE_INTERNAL') || die();

use core\lock\lock_config;
use local_subscriptions\commerce\legal\entity\CommerceLegalEntitySnapshot;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentation;
use moodle_database;

/**
 * Issues a stable invoice identity once per paid purchase.
 *
 * Seller/customer/financial data are persisted at first issue so a later
 * configuration or Moodle-profile change cannot rewrite an historical invoice.
 */
final class CommerceInvoiceIssuer {
    public const TABLE_INVOICE = 'local_subs_commerce_invoice';
    public const TABLE_SEQUENCE = 'local_subs_commerce_inv_seq';

    public function __construct(private readonly moodle_database $database) {}

    public function get_or_issue(CommerceOrderPresentation $order): CommerceIssuedInvoice {
        $existing = $this->database->get_record(self::TABLE_INVOICE, ['purchaseid' => $order->purchaseid], '*', IGNORE_MISSING);
        if ($existing !== false) {
            return $this->map($existing);
        }
        if (!$order->is_paid()) {
            throw new \coding_exception('A Commerce invoice can only be issued for a paid purchase.');
        }

        $seller = $this->seller_snapshot($order);
        $entitykey = trim((string)($seller['legal_entity_key'] ?? ''));
        if ($entitykey === '') {
            throw new \coding_exception('A Commerce invoice requires a legal seller identity.');
        }
        $issuedat = $order->paidat ?? time();
        $year = (int)userdate($issuedat, '%Y');
        $prefix = $this->number_prefix($entitykey);

        $factory = lock_config::get_lock_factory('local_subscriptions_commerce_invoice');
        // Number uniqueness is defined by the public prefix/year, not by the
        // internal legal-entity key. fr_main and fr_legacy both emit CFR-FR-...
        // and must therefore share the same numbering lock.
        $lock = $factory->get_lock('invoice-sequence:' . $prefix . ':' . $year, 10);
        if ($lock === false) {
            throw new \coding_exception('Unable to acquire the Commerce invoice numbering lock.');
        }

        try {
            // Another process may have issued it while this process waited.
            $existing = $this->database->get_record(self::TABLE_INVOICE, ['purchaseid' => $order->purchaseid], '*', IGNORE_MISSING);
            if ($existing !== false) {
                return $this->map($existing);
            }

            $transaction = $this->database->start_delegated_transaction();
            try {
                $sequencerecord = $this->database->get_record(
                    self::TABLE_SEQUENCE,
                    ['entitykey' => $entitykey, 'invoiceyear' => $year],
                    '*',
                    IGNORE_MISSING
                );

                // The immutable public ledger is the durable source of truth.
                // Internal entity keys may change while keeping the same public
                // numbering prefix (for example fr_legacy -> fr_main), so reconcile
                // every counter and issued document sharing this prefix/year.
                $prefixcounterlast = 0;
                $counterrecords = $this->database->get_records(
                    self::TABLE_SEQUENCE,
                    ['invoiceyear' => $year],
                    '',
                    'id,entitykey,lastsequence'
                );
                foreach ($counterrecords as $counterrecord) {
                    if ($this->number_prefix((string)$counterrecord->entitykey) !== $prefix) {
                        continue;
                    }
                    $prefixcounterlast = max(
                        $prefixcounterlast,
                        (int)$counterrecord->lastsequence
                    );
                }

                $numberpattern = sprintf('CFR-%s-%04d-%%', $prefix, $year);
                $maxissuedsql = 'SELECT MAX(sequence)'
                    . ' FROM {' . self::TABLE_INVOICE . '}'
                    . ' WHERE invoiceyear = :invoiceyear'
                    . ' AND ' . $this->database->sql_like(
                        'invoicenumber',
                        ':numberpattern',
                        false
                    );
                $maxissued = (int)($this->database->get_field_sql(
                    $maxissuedsql,
                    [
                        'invoiceyear' => $year,
                        'numberpattern' => $numberpattern,
                    ]
                ) ?: 0);

                $counterlast = $sequencerecord === false
                    ? 0
                    : (int)$sequencerecord->lastsequence;
                $sequence = max($counterlast, $prefixcounterlast, $maxissued) + 1;
                $number = $this->format_number($entitykey, $year, $sequence);

                // Defensive protection for imported/corrupted historical rows whose
                // stored sequence does not match the suffix embedded in the public
                // invoice number. The prefix/year lock makes this loop race-safe.
                while ($this->database->record_exists(
                    self::TABLE_INVOICE,
                    ['invoicenumber' => $number]
                )) {
                    $sequence++;
                    $number = $this->format_number($entitykey, $year, $sequence);
                }

                if ($sequencerecord === false) {
                    $this->database->insert_record(self::TABLE_SEQUENCE, (object)[
                        'entitykey' => $entitykey,
                        'invoiceyear' => $year,
                        'lastsequence' => $sequence,
                        'timemodified' => time(),
                    ]);
                } else {
                    $sequencerecord->lastsequence = $sequence;
                    $sequencerecord->timemodified = time();
                    $this->database->update_record(self::TABLE_SEQUENCE, $sequencerecord);
                }
                $now = time();
                $id = (int)$this->database->insert_record(self::TABLE_INVOICE, (object)[
                    'purchaseid' => $order->purchaseid,
                    'entitykey' => $entitykey,
                    'invoicenumber' => $number,
                    'invoiceyear' => $year,
                    'sequence' => $sequence,
                    'issuedat' => $issuedat,
                    'sellerjson' => $this->encode($seller),
                    'customerjson' => $this->encode($order->customerSnapshot),
                    'financialjson' => $this->encode([
                        'currency' => $order->currency,
                        'total_minor' => $order->totalminor,
                        // Tax is intentionally not invented in I6 V1.
                        'tax_status' => 'not_modelled_v1',
                    ]),
                    'metadatajson' => $this->encode([
                        'schema' => 'commerce_invoice_v1',
                        'purchase_reference' => $order->reference,
                    ]),
                    'timecreated' => $now,
                    'timemodified' => $now,
                ]);
                $transaction->allow_commit();
            } catch (\Throwable $exception) {
                // Never leave a delegated transaction active when invoice
                // generation fails inside the mail pipeline. Moodle's rollback
                // rethrows the original exception after restoring DB state.
                $transaction->rollback($exception);
            }

            $record = $this->database->get_record(self::TABLE_INVOICE, ['id' => $id], '*', MUST_EXIST);
            return $this->map($record);
        } finally {
            $lock->release();
        }
    }

    private function seller_snapshot(CommerceOrderPresentation $order): array {
        $metadata = $order->purchaseSnapshot['metadata'] ?? [];
        $snapshot = is_array($metadata) ? ($metadata['legal_entity_snapshot'] ?? []) : [];
        if (is_array($snapshot) && trim((string)($snapshot['legal_entity_key'] ?? '')) !== '') {
            // Validate the durable snapshot before using it as an invoice issuer.
            return CommerceLegalEntitySnapshot::from_array($snapshot)->to_array();
        }

        // Legacy purchases pre-I4: preserve the old behaviour as an explicit compatibility path.
        $legacy = (new CommerceInvoiceProfileResolver())->resolve($order->currency, $order->provider);
        $key = $legacy['key'] === 'rub' ? 'ru_legacy' : 'fr_legacy';
        return [
            'version' => 0,
            'legal_entity_key' => $key,
            'registered_country' => $legacy['key'] === 'rub' ? 'RU' : 'FR',
            'name' => $legacy['name'],
            'address' => $legacy['address'],
            'legal' => $legacy['legal'],
            'registration' => '',
            'tax_identifier' => '',
            'email' => $legacy['email'],
            'phone' => $legacy['phone'],
            'website' => $legacy['website'],
            'tax_notice' => $legacy['taxnotice'],
            'footer' => $legacy['footer'],
            'market_country' => 'ZZ',
            'resolution_rule' => 'legacy_invoice_profile',
            'resolved_at' => $order->timecreated,
            'currency' => $order->currency,
            'provider' => strtolower((string)$order->provider),
        ];
    }

    private function number_prefix(string $entitykey): string {
        return match ($entitykey) {
            'fr_main', 'fr_legacy' => 'FR',
            'ru_main', 'ru_legacy' => 'RU',
            default => strtoupper(substr(
                preg_replace('/[^a-z0-9]/i', '', $entitykey) ?: 'LE',
                0,
                8
            )),
        };
    }

    private function format_number(string $entitykey, int $year, int $sequence): string {
        return sprintf(
            'CFR-%s-%04d-%06d',
            $this->number_prefix($entitykey),
            $year,
            $sequence
        );
    }

    private function map(\stdClass $record): CommerceIssuedInvoice {
        return new CommerceIssuedInvoice(
            (int)$record->id,
            (int)$record->purchaseid,
            (string)$record->invoicenumber,
            (string)$record->entitykey,
            (int)$record->sequence,
            (int)$record->invoiceyear,
            (int)$record->issuedat,
            $this->decode((string)$record->sellerjson),
            $this->decode((string)$record->customerjson),
            $this->decode((string)$record->financialjson)
        );
    }

    private function encode(array $value): string {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function decode(string $value): array {
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }
}
