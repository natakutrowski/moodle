<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\readmodel;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\order\reference\CommercePublicOrderReference;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;

/** Builds the unified Native Commerce customer read model used by CRM. */
final class CommerceCustomerReadService {
    private const TABLE_GRANT = 'local_subs_commerce_grant';

    public function __construct(private readonly \moodle_database $db) {}

    public function build_for_user(int $userid): CommerceCustomerSnapshot {
        if ($userid <= 0) {
            throw new \coding_exception('A positive Moodle user identifier is required.');
        }
        $user = $this->db->get_record('user', ['id' => $userid], 'id,email,firstname,lastname,deleted', MUST_EXIST);
        return $this->build($userid, (string)$user->email);
    }

    public function build_for_email(string $email): CommerceCustomerSnapshot {
        $email = $this->normalise_email($email);
        if ($email === '') {
            throw new \coding_exception('A customer email is required.');
        }
        $user = $this->find_user_by_email($email);
        return $this->build($user ? (int)$user->id : null, $email);
    }

    public function build(?int $userid, ?string $email): CommerceCustomerSnapshot {
        if ($userid !== null && $userid <= 0) {
            throw new \coding_exception('A Commerce customer user identifier must be positive.');
        }
        $email = $this->normalise_email((string)$email);
        $user = null;
        if ($userid !== null) {
            $user = $this->db->get_record('user', ['id' => $userid], 'id,email,firstname,lastname,deleted', MUST_EXIST);
            if ($email === '') {
                $email = $this->normalise_email((string)$user->email);
            }
        } else if ($email !== '') {
            $user = $this->find_user_by_email($email);
            if ($user !== null) {
                $userid = (int)$user->id;
            }
        }
        if ($userid === null && $email === '') {
            throw new \coding_exception('A Commerce customer lookup requires a user identifier or an email.');
        }

        $purchaserecords = $this->find_purchase_records($userid, $email);
        $purchaseids = array_map(static fn(\stdClass $record): int => (int)$record->id, $purchaserecords);
        $references = array_map(static fn(\stdClass $record): string => (string)$record->reference, $purchaserecords);

        $itemsby = $this->load_items($purchaseids);
        $paymentsby = $this->load_payments($purchaseids);
        $grantrecords = $this->load_grants($references, $userid, $email);
        $grantsby = [];
        $allgrants = [];
        foreach ($grantrecords as $record) {
            $grant = $this->map_grant($record);
            $allgrants[] = $grant;
            $grantsby[$grant->purchasereference][] = $grant;
        }

        $purchases = [];
        $allpayments = [];
        $customerids = [];
        $hasguesthistory = false;
        $publicreferences = new CommercePublicOrderReference();
        foreach ($purchaserecords as $record) {
            $customer = $this->decode((string)$record->customerjson);
            foreach ($this->extract_customer_ids($customer) as $customerid) {
                $customerids[$customerid] = $customerid;
            }
            $guestorigin = empty($record->userid)
                || (
                    array_key_exists('userid', $customer)
                    && empty($customer['userid'])
                );
            if ($guestorigin) {
                $hasguesthistory = true;
            }

            $payments = [];
            foreach ($paymentsby[(int)$record->id] ?? [] as $paymentrecord) {
                $payment = $this->map_payment($paymentrecord);
                $payments[] = $payment;
                $allpayments[] = $payment;
            }
            $items = array_map(fn(\stdClass $item): array => $this->map_item($item), $itemsby[(int)$record->id] ?? []);
            $metadata = $this->decode((string)$record->metadatajson);
            $purchases[] = new CommerceCustomerPurchase(
                (int)$record->id,
                (string)$record->purchaseuuid,
                (string)$record->reference,
                $publicreferences->from_internal((string)$record->reference, (int)$record->timecreated),
                $this->resolve_purchase_type((string)$record->type, $items),
                strtolower(trim((string)$record->status)),
                strtoupper((string)$record->currency),
                (int)$record->totalminor,
                !empty($record->userid) ? (int)$record->userid : null,
                $this->normalise_email((string)$record->customeremail) ?: null,
                (int)$record->timecreated,
                (int)$record->timemodified,
                $items,
                $payments,
                $grantsby[(string)$record->reference] ?? [],
                $metadata,
                $guestorigin
            );
        }

        usort($purchases, static fn(CommerceCustomerPurchase $a, CommerceCustomerPurchase $b): int =>
            [$b->timecreated, $b->id] <=> [$a->timecreated, $a->id]
        );
        usort($allpayments, static fn(CommerceCustomerPayment $a, CommerceCustomerPayment $b): int =>
            [$b->timecreated, $b->id] <=> [$a->timecreated, $a->id]
        );
        usort($allgrants, static fn(CommerceCustomerGrant $a, CommerceCustomerGrant $b): int => $b->id <=> $a->id);

        $identity = new CommerceCustomerIdentity(
            $userid,
            $email !== '' ? $email : null,
            $user !== null ? (string)$user->firstname : null,
            $user !== null ? (string)$user->lastname : null,
            array_values($customerids),
            $hasguesthistory
        );

        return new CommerceCustomerSnapshot(
            $identity,
            $purchases,
            $allpayments,
            $allgrants,
            $this->build_metrics($purchases, $allpayments, $allgrants)
        );
    }

    /** @return \stdClass[] */
    private function find_purchase_records(?int $userid, string $email): array {
        $conditions = [];
        $params = [];
        if ($userid !== null) {
            $conditions[] = 'userid = :userid';
            $params['userid'] = $userid;
        }
        if ($email !== '') {
            $conditions[] = $this->db->sql_equal('customeremail', ':customeremail', false);
            $params['customeremail'] = $email;
        }
        if ($conditions === []) {
            return [];
        }
        return array_values($this->db->get_records_sql(
            'SELECT * FROM {' . CommercePersistenceSchema::TABLE_PURCHASE . '}
              WHERE (' . implode(' OR ', $conditions) . ')
           ORDER BY timecreated DESC, id DESC',
            $params
        ));
    }

    /** @return array<int, \stdClass[]> */
    private function load_items(array $purchaseids): array {
        return $this->group_by_purchase($this->records_for_ids(CommercePersistenceSchema::TABLE_ITEM, $purchaseids, 'position ASC, id ASC'));
    }

    /** @return array<int, \stdClass[]> */
    private function load_payments(array $purchaseids): array {
        return $this->group_by_purchase($this->records_for_ids(CommercePersistenceSchema::TABLE_PAYMENT, $purchaseids, 'sequence ASC, id ASC'));
    }

    /** @return \stdClass[] */
    private function records_for_ids(string $table, array $purchaseids, string $sort): array {
        if ($purchaseids === []) {
            return [];
        }
        [$insql, $params] = $this->db->get_in_or_equal($purchaseids, SQL_PARAMS_NAMED, 'purchase');
        return array_values($this->db->get_records_select($table, 'purchaseid ' . $insql, $params, $sort));
    }

    /** @return array<int, \stdClass[]> */
    private function group_by_purchase(array $records): array {
        $grouped = [];
        foreach ($records as $record) {
            $grouped[(int)$record->purchaseid][] = $record;
        }
        return $grouped;
    }

    /** @return \stdClass[] */
    private function load_grants(array $references, ?int $userid, string $email): array {
        $conditions = [];
        $params = [];
        if ($references !== []) {
            [$insql, $inparams] = $this->db->get_in_or_equal($references, SQL_PARAMS_NAMED, 'purchaseref');
            $conditions[] = 'purchasereference ' . $insql;
            $params += $inparams;
        }
        if ($userid !== null) {
            $conditions[] = 'beneficiaryuserid = :beneficiaryuserid';
            $params['beneficiaryuserid'] = $userid;
        }
        if ($email !== '') {
            $conditions[] = $this->db->sql_equal('beneficiaryemail', ':beneficiaryemail', false);
            $params['beneficiaryemail'] = $email;
        }
        if ($conditions === []) {
            return [];
        }
        return array_values($this->db->get_records_select(
            self::TABLE_GRANT,
            '(' . implode(' OR ', $conditions) . ')',
            $params,
            'timecreated DESC, id DESC'
        ));
    }

    private function map_payment(\stdClass $record): CommerceCustomerPayment {
        return new CommerceCustomerPayment(
            (int)$record->id,
            (int)$record->purchaseid,
            (int)$record->sequence,
            strtolower(trim((string)$record->status)),
            strtoupper((string)$record->currency),
            (int)$record->amountminor,
            $this->nullable_string($record->provider ?? null),
            $this->nullable_string($record->providerreference ?? null),
            $this->nullable_string($record->transactionid ?? null),
            $this->nullable_int($record->paidat ?? null),
            (int)$record->timecreated,
            (int)$record->timemodified
        );
    }

    private function map_grant(\stdClass $record): CommerceCustomerGrant {
        return new CommerceCustomerGrant(
            (int)$record->id,
            (string)$record->grantreference,
            (string)$record->purchasereference,
            (string)$record->itemreference,
            (string)$record->productsku,
            strtolower((string)$record->type),
            (string)$record->resourcekey,
            strtolower((string)$record->status),
            $this->nullable_int($record->beneficiaryuserid ?? null),
            $this->normalise_email((string)$record->beneficiaryemail),
            (int)$record->validfrom,
            $this->nullable_int($record->validuntil ?? null),
            $this->decode((string)$record->configurationjson),
            $this->decode((string)$record->metadatajson)
        );
    }

    /** @return array<string, mixed> */
    private function map_item(\stdClass $record): array {
        return [
            'id' => (int)$record->id,
            'position' => (int)$record->position,
            'type' => strtolower((string)$record->itemtype),
            'reference' => (string)$record->itemreference,
            'label' => (string)$record->label,
            'quantity' => (int)$record->quantity,
            'currency' => strtoupper((string)$record->currency),
            'unitminor' => (int)$record->unitminor,
            'grossminor' => (int)$record->grossminor,
            'discountminor' => (int)$record->discountminor,
            'netminor' => (int)$record->netminor,
            'pricing' => $this->decode((string)$record->pricingjson),
            'fulfillment' => $this->decode((string)$record->fulfillmentjson),
            'metadata' => $this->decode((string)$record->metadatajson),
        ];
    }

    private function resolve_purchase_type(string $fallback, array $items): string {
        $types = [];
        foreach ($items as $item) {
            $metadata = $item['metadata'] ?? [];
            $fulfillment = $item['fulfillment'] ?? [];
            if (($metadata['operation'] ?? $fulfillment['operation'] ?? null) === 'upgrade') {
                return 'upgrade';
            }
            $type = strtolower((string)($item['type'] ?? ''));
            $types[$type] = true;
        }
        if (isset($types['bundle'])) {
            return 'bundle';
        }
        if (count($types) > 1) {
            return 'mixed';
        }
        $only = array_key_first($types);
        if (in_array($only, ['digital', 'digital_download'], true)) {
            return 'digital';
        }
        if (in_array($only, ['subscription', 'course', 'course_access'], true)) {
            return 'course';
        }
        return strtolower(trim($fallback)) ?: 'unknown';
    }

    private function build_metrics(array $purchases, array $payments, array $grants): CommerceCustomerMetrics {
        $purchasebytype = $purchasebystatus = $paymentbystatus = $providerusage = $grantbytype = $revenue = [];
        $successfulpurchases = 0;
        $successfulpayments = 0;
        $activegrants = 0;
        $guestpurchases = 0;
        $first = $last = $lastsuccessful = null;

        foreach ($purchases as $purchase) {
            $purchasebytype[$purchase->type] = ($purchasebytype[$purchase->type] ?? 0) + 1;
            $purchasebystatus[$purchase->status] = ($purchasebystatus[$purchase->status] ?? 0) + 1;
            $first = $first === null ? $purchase->timecreated : min($first, $purchase->timecreated);
            $last = $last === null ? $purchase->timecreated : max($last, $purchase->timecreated);
            if ($purchase->guestorigin) {
                $guestpurchases++;
            }
            if ($purchase->has_successful_payment()) {
                $successfulpurchases++;
                $paidat = $purchase->successful_paid_at() ?? $purchase->timecreated;
                $lastsuccessful = $lastsuccessful === null ? $paidat : max($lastsuccessful, $paidat);
            }
        }
        foreach ($payments as $payment) {
            $paymentbystatus[$payment->status] = ($paymentbystatus[$payment->status] ?? 0) + 1;
            $provider = strtolower(trim((string)$payment->provider)) ?: 'unknown';
            $providerusage[$provider] = ($providerusage[$provider] ?? 0) + 1;
            if ($payment->is_successful()) {
                $successfulpayments++;
                $revenue[$payment->currency] = ($revenue[$payment->currency] ?? 0) + $payment->amountminor;
            }
        }
        foreach ($grants as $grant) {
            $grantbytype[$grant->type] = ($grantbytype[$grant->type] ?? 0) + 1;
            if ($grant->is_active()) {
                $activegrants++;
            }
        }
        ksort($purchasebytype);
        ksort($purchasebystatus);
        ksort($paymentbystatus);
        ksort($providerusage);
        ksort($grantbytype);
        ksort($revenue);
        return new CommerceCustomerMetrics(
            count($purchases),
            $successfulpurchases,
            count($payments),
            $successfulpayments,
            $activegrants,
            $guestpurchases,
            $purchasebytype,
            $purchasebystatus,
            $paymentbystatus,
            $providerusage,
            $grantbytype,
            $revenue,
            $first,
            $last,
            $lastsuccessful
        );
    }

    private function find_user_by_email(string $email): ?\stdClass {
        $sql = 'SELECT id,email,firstname,lastname,deleted
                  FROM {user}
                 WHERE deleted = 0
                   AND ' . $this->db->sql_equal('email', ':email', false) . '
              ORDER BY id ASC';
        $records = $this->db->get_records_sql($sql, ['email' => $email], 0, 2);
        if (count($records) > 1) {
            return null;
        }
        return $records === [] ? null : reset($records);
    }

    /** @return string[] */
    private function extract_customer_ids(array $customer): array {
        $values = [
            $customer['customerid'] ?? null,
            $customer['id'] ?? null,
            $customer['metadata']['customerid'] ?? null,
        ];
        $ids = [];
        foreach ($values as $value) {
            $value = trim((string)$value);
            if ($value !== '') {
                $ids[$value] = $value;
            }
        }
        return array_values($ids);
    }

    private function decode(string $json): array {
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function normalise_email(string $email): string {
        return trim(\core_text::strtolower($email));
    }

    private function nullable_string(mixed $value): ?string {
        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }

    private function nullable_int(mixed $value): ?int {
        if ($value === null || $value === '') {
            return null;
        }
        $value = (int)$value;
        return $value > 0 ? $value : null;
    }
}
