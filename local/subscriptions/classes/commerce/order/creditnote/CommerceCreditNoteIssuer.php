<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\order\creditnote;

defined('MOODLE_INTERNAL') || die();

use core\lock\lock_config;
use local_subscriptions\commerce\order\invoice\CommerceInvoiceIssuer;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentationService;
use local_subscriptions\commerce\payment\refund\CommercePaymentRefundRecord;
use local_subscriptions\commerce\payment\refund\CommercePaymentRefundResult;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseReadRepository;
use moodle_database;

/**
 * Issues one immutable credit note per successful, individually identifiable refund.
 *
 * The seller/customer identity is inherited from the original issued invoice,
 * never re-resolved from current configuration. Mutable aggregate provider
 * snapshots are deliberately not document-bearing because their amount may
 * change on later synchronisations.
 */
final class CommerceCreditNoteIssuer {
    public const TABLE_CREDIT_NOTE = 'local_subs_commerce_credit_note';
    public const TABLE_SEQUENCE = 'local_subs_commerce_cn_seq';

    public function __construct(private readonly moodle_database $database) {}

    public function find_by_refund_id(int $refundid): ?CommerceIssuedCreditNote {
        if ($refundid <= 0) {
            return null;
        }
        $record = $this->database->get_record(
            self::TABLE_CREDIT_NOTE,
            ['refundid' => $refundid],
            '*',
            IGNORE_MISSING
        );
        return $record === false ? null : $this->map($record);
    }

    /**
     * Issue the credit note when the refund is document-eligible.
     *
     * @return CommerceIssuedCreditNote|null Null for pending/failed or mutable aggregate imports.
     */
    public function issue_if_eligible(
        CommercePaymentRefundRecord $refund
    ): ?CommerceIssuedCreditNote {
        $existing = $this->find_by_refund_id($refund->get_id());
        if ($existing !== null) {
            return $existing;
        }

        if ($refund->get_status() !== CommercePaymentRefundResult::STATUS_SUCCEEDED) {
            return null;
        }

        if ($this->is_mutable_aggregate($refund)) {
            return null;
        }

        $payment = $this->database->get_record(
            'local_subscriptions_commerce_payment',
            ['id' => $refund->get_payment_id()],
            'id,purchaseid',
            MUST_EXIST
        );
        $purchaseid = (int)$payment->purchaseid;

        $details = (new CommercePurchaseReadRepository($this->database))
            ->find_by_id($purchaseid);
        if ($details === null) {
            throw new \coding_exception(
                'Unable to issue a Commerce credit note for an unknown purchase.'
            );
        }

        $order = (new CommerceOrderPresentationService(
            $this->database,
            new CommercePurchaseReadRepository($this->database)
        ))->present($details);

        // A credit note always references a durable invoice. If the invoice was
        // never downloaded before the refund, issue it now first.
        $invoice = (new CommerceInvoiceIssuer($this->database))->get_or_issue($order);

        $entitykey = trim($invoice->entitykey);
        if ($entitykey === '') {
            throw new \coding_exception(
                'A Commerce credit note requires the original invoice legal entity.'
            );
        }

        $issuedat = max(
            $refund->get_time_created(),
            $refund->get_time_modified()
        );
        $year = (int)userdate($issuedat, '%Y');
        $prefix = $this->number_prefix($entitykey);

        $factory = lock_config::get_lock_factory(
            'local_subscriptions_commerce_credit_note'
        );
        // Credit-note numbers are unique by public prefix/year. Legacy and
        // current internal legal-entity keys can share that public prefix.
        $lock = $factory->get_lock(
            'credit-note-sequence:' . $prefix . ':' . $year,
            10
        );
        if ($lock === false) {
            throw new \coding_exception(
                'Unable to acquire the Commerce credit-note numbering lock.'
            );
        }

        try {
            $existing = $this->find_by_refund_id($refund->get_id());
            if ($existing !== null) {
                return $existing;
            }

            $transaction = $this->database->start_delegated_transaction();
            $sequencerecord = $this->database->get_record(
                self::TABLE_SEQUENCE,
                [
                    'entitykey' => $entitykey,
                    'creditnoteyear' => $year,
                ],
                '*',
                IGNORE_MISSING
            );

            $prefixcounterlast = 0;
            $counterrecords = $this->database->get_records(
                self::TABLE_SEQUENCE,
                ['creditnoteyear' => $year],
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

            $numberpattern = sprintf('CFR-%s-A-%04d-%%', $prefix, $year);
            $maxissuedsql = 'SELECT MAX(sequence)'
                . ' FROM {' . self::TABLE_CREDIT_NOTE . '}'
                . ' WHERE creditnoteyear = :creditnoteyear'
                . ' AND ' . $this->database->sql_like(
                    'creditnotenumber',
                    ':numberpattern',
                    false
                );
            $maxissued = (int)($this->database->get_field_sql(
                $maxissuedsql,
                [
                    'creditnoteyear' => $year,
                    'numberpattern' => $numberpattern,
                ]
            ) ?: 0);

            $counterlast = $sequencerecord === false
                ? 0
                : (int)$sequencerecord->lastsequence;
            $sequence = max($counterlast, $prefixcounterlast, $maxissued) + 1;
            $number = $this->format_number(
                $entitykey,
                $year,
                $sequence
            );

            while ($this->database->record_exists(
                self::TABLE_CREDIT_NOTE,
                ['creditnotenumber' => $number]
            )) {
                $sequence++;
                $number = $this->format_number(
                    $entitykey,
                    $year,
                    $sequence
                );
            }

            if ($sequencerecord === false) {
                $this->database->insert_record(
                    self::TABLE_SEQUENCE,
                    (object)[
                        'entitykey' => $entitykey,
                        'creditnoteyear' => $year,
                        'lastsequence' => $sequence,
                        'timemodified' => time(),
                    ]
                );
            } else {
                $sequencerecord->lastsequence = $sequence;
                $sequencerecord->timemodified = time();
                $this->database->update_record(
                    self::TABLE_SEQUENCE,
                    $sequencerecord
                );
            }
            $financial = [
                'currency' => $refund->get_currency(),
                'refund_minor' => $refund->get_amount_minor(),
                'original_invoice_total_minor' =>
                    (int)($invoice->financial['total_minor'] ?? 0),
                // Juridique/Fiscalité V2 will define tax allocation.
                'tax_status' => 'not_modelled_v1',
            ];
            $metadata = [
                'schema' => 'commerce_credit_note_v1',
                'invoice_number' => $invoice->number,
                'invoice_id' => $invoice->id,
                'refund_id' => $refund->get_id(),
                'provider' => $refund->get_provider(),
                'provider_refund_id' => $refund->get_provider_refund_id(),
                'refund_reason' => $refund->get_reason(),
                'refund_idempotency_key' => $refund->get_idempotency_key(),
                'original_purchase' => [
                    'reference' => $order->reference,
                    'currency' => $order->currency,
                    'total_minor' => $order->totalminor,
                    'items' => array_map(
                        static fn($item): array => [
                            'reference' => $item->reference,
                            'type' => $item->type,
                            'label' => $item->label,
                            'quantity' => $item->quantity,
                            'currency' => $item->currency,
                            'unit_minor' => $item->unitminor,
                            'gross_minor' => $item->grossminor,
                            'discount_minor' => $item->discountminor,
                            'net_minor' => $item->netminor,
                        ],
                        $order->items
                    ),
                ],
            ];
            $now = time();

            $id = (int)$this->database->insert_record(
                self::TABLE_CREDIT_NOTE,
                (object)[
                    'refundid' => $refund->get_id(),
                    'invoiceid' => $invoice->id,
                    'purchaseid' => $invoice->purchaseid,
                    'entitykey' => $entitykey,
                    'creditnotenumber' => $number,
                    'creditnoteyear' => $year,
                    'sequence' => $sequence,
                    'issuedat' => $issuedat,
                    'sellerjson' => $this->encode($invoice->seller),
                    'customerjson' => $this->encode($invoice->customer),
                    'financialjson' => $this->encode($financial),
                    'metadatajson' => $this->encode($metadata),
                    'timecreated' => $now,
                    'timemodified' => $now,
                ]
            );
            $transaction->allow_commit();

            $record = $this->database->get_record(
                self::TABLE_CREDIT_NOTE,
                ['id' => $id],
                '*',
                MUST_EXIST
            );
            return $this->map($record);
        } finally {
            $lock->release();
        }
    }

    private function is_mutable_aggregate(
        CommercePaymentRefundRecord $refund
    ): bool {
        $payload = $refund->get_provider_payload() ?? [];
        $metadata = $refund->get_metadata();

        return !empty($payload['aggregate'])
            || !empty($payload['aggregate_total'])
            || !empty($metadata['aggregate'])
            || !empty($metadata['aggregate_total']);
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

    private function format_number(
        string $entitykey,
        int $year,
        int $sequence
    ): string {
        return sprintf(
            'CFR-%s-A-%04d-%06d',
            $this->number_prefix($entitykey),
            $year,
            $sequence
        );
    }

    private function map(\stdClass $record): CommerceIssuedCreditNote {
        return new CommerceIssuedCreditNote(
            (int)$record->id,
            (int)$record->refundid,
            (int)$record->invoiceid,
            (int)$record->purchaseid,
            (string)$record->creditnotenumber,
            (string)$record->entitykey,
            (int)$record->sequence,
            (int)$record->creditnoteyear,
            (int)$record->issuedat,
            $this->decode((string)$record->sellerjson),
            $this->decode((string)$record->customerjson),
            $this->decode((string)$record->financialjson),
            $this->decode((string)$record->metadatajson)
        );
    }

    private function encode(array $value): string {
        return json_encode(
            $value,
            JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_THROW_ON_ERROR
        );
    }

    private function decode(string $value): array {
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }
}
