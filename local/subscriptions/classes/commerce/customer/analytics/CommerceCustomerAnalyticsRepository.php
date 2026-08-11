<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\analytics;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\persistence\CommercePersistenceSchema;

/** Period analytics derived from Native purchases, payment attempts and fulfillment. */
final class CommerceCustomerAnalyticsRepository {
    private const SUCCESSFUL_PAYMENT_STATUSES = ['paid', 'completed'];

    public function __construct(private readonly \moodle_database $db) {}

    public function snapshot(int $start, int $end): CommerceCustomerAnalyticsSnapshot {
        if ($start <= 0 || $end <= $start) {
            throw new \InvalidArgumentException('Invalid Commerce analytics period.');
        }

        $purchases = array_values($this->db->get_records_select(
            CommercePersistenceSchema::TABLE_PURCHASE,
            'timecreated >= :periodstart AND timecreated < :periodend',
            ['periodstart' => $start, 'periodend' => $end],
            'timecreated ASC, id ASC'
        ));
        if ($purchases === []) {
            return new CommerceCustomerAnalyticsSnapshot(
                $start, $end, 0, 0, 0, 0, 0, 0, 0, 0, [], [], [], []
            );
        }

        $purchaseids = array_map(static fn(\stdClass $purchase): int => (int)$purchase->id, $purchases);
        $itemsby = $this->items_by_purchase($purchaseids);
        $successfulpayments = $this->successful_payments_by_purchase($purchaseids);

        $purchasebytype = [];
        $purchasebystatus = [];
        $revenue = [];
        $revenuebytype = [];
        $successful = 0;
        $failed = 0;
        $digitalidentities = [];
        $guestpurchases = 0;
        $attachedguests = 0;
        $fulfilled = 0;

        foreach ($purchases as $purchase) {
            $purchaseid = (int)$purchase->id;
            $type = $this->purchase_type((string)$purchase->type, $itemsby[$purchaseid] ?? []);
            $status = strtolower(trim((string)$purchase->status));
            $purchasebytype[$type] = ($purchasebytype[$type] ?? 0) + 1;
            $purchasebystatus[$status] = ($purchasebystatus[$status] ?? 0) + 1;
            if ($status === 'fulfilled') {
                $fulfilled++;
            }
            if (in_array($status, ['failed', 'cancelled'], true)) {
                $failed++;
            }
            if (empty($purchase->userid)) {
                $guestpurchases++;
            } else if ($this->was_guest_purchase($purchase)) {
                $attachedguests++;
            }

            $payment = $successfulpayments[$purchaseid] ?? null;
            if ($payment === null) {
                continue;
            }
            $successful++;
            $currency = strtoupper(trim((string)$payment->currency));
            if ($currency !== '') {
                $amountminor = (int)$payment->amountminor;
                $revenue[$currency] = ($revenue[$currency] ?? 0) + $amountminor;
                $revenuebytype[$type][$currency] = ($revenuebytype[$type][$currency] ?? 0) + $amountminor;
            }
            if (in_array($type, ['digital', 'bundle', 'mixed'], true)) {
                $digitalidentities[$this->identity_key($purchase)] = true;
            }
        }

        ksort($purchasebytype);
        ksort($purchasebystatus);
        ksort($revenue);
        ksort($revenuebytype);
        foreach ($revenuebytype as &$typecurrencies) {
            ksort($typecurrencies);
        }
        unset($typecurrencies);

        return new CommerceCustomerAnalyticsSnapshot(
            $start,
            $end,
            count($purchases),
            $successful,
            $failed,
            $this->count_new_customers($start, $end),
            count($digitalidentities),
            $guestpurchases,
            $attachedguests,
            $fulfilled,
            $purchasebytype,
            $purchasebystatus,
            $revenue,
            $revenuebytype
        );
    }

    /** @return array<int,\stdClass[]> */
    private function items_by_purchase(array $purchaseids): array {
        [$insql, $params] = $this->db->get_in_or_equal($purchaseids, SQL_PARAMS_NAMED, 'analyticsitem');
        $records = $this->db->get_records_select(
            CommercePersistenceSchema::TABLE_ITEM,
            'purchaseid ' . $insql,
            $params,
            'purchaseid ASC, position ASC, id ASC'
        );
        $grouped = [];
        foreach ($records as $record) {
            $grouped[(int)$record->purchaseid][] = $record;
        }
        return $grouped;
    }

    /** @return array<int,\stdClass> Latest successful payment per purchase. */
    private function successful_payments_by_purchase(array $purchaseids): array {
        [$insql, $params] = $this->db->get_in_or_equal($purchaseids, SQL_PARAMS_NAMED, 'analyticspayment');
        [$statussql, $statusparams] = $this->db->get_in_or_equal(
            self::SUCCESSFUL_PAYMENT_STATUSES,
            SQL_PARAMS_NAMED,
            'analyticsstatus'
        );
        $records = $this->db->get_records_select(
            CommercePersistenceSchema::TABLE_PAYMENT,
            'purchaseid ' . $insql . ' AND status ' . $statussql,
            $params + $statusparams,
            'purchaseid ASC, sequence DESC, id DESC'
        );
        $result = [];
        foreach ($records as $record) {
            $purchaseid = (int)$record->purchaseid;
            if (!isset($result[$purchaseid])) {
                $result[$purchaseid] = $record;
            }
        }
        return $result;
    }

    private function count_new_customers(int $start, int $end): int {
        [$statussql, $params] = $this->db->get_in_or_equal(
            self::SUCCESSFUL_PAYMENT_STATUSES,
            SQL_PARAMS_NAMED,
            'firststatus'
        );
        $records = $this->db->get_records_sql(
            'SELECT p.id, p.userid, p.customeremail, pay.paidat, pay.timecreated
               FROM {' . CommercePersistenceSchema::TABLE_PURCHASE . '} p
               JOIN {' . CommercePersistenceSchema::TABLE_PAYMENT . '} pay ON pay.purchaseid = p.id
              WHERE pay.status ' . $statussql . '
           ORDER BY COALESCE(pay.paidat, pay.timecreated) ASC, pay.id ASC',
            $params
        );
        $emails = [];
        foreach ($records as $record) {
            $email = strtolower(trim((string)($record->customeremail ?? '')));
            if ($email !== '') {
                $emails[$email] = $email;
            }
        }
        $usersbyemail = [];
        if ($emails !== []) {
            foreach ($this->db->get_records_list('user', 'email', array_values($emails), '', 'id,email,deleted') as $user) {
                if (empty($user->deleted)) {
                    $usersbyemail[strtolower(trim((string)$user->email))] = (int)$user->id;
                }
            }
        }

        $first = [];
        foreach ($records as $record) {
            $email = strtolower(trim((string)($record->customeremail ?? '')));
            $key = !empty($record->userid)
                ? 'user:' . (int)$record->userid
                : (isset($usersbyemail[$email]) ? 'user:' . $usersbyemail[$email] : ($email !== '' ? 'email:' . $email : ''));
            if ($key === '') {
                continue;
            }
            $paidat = !empty($record->paidat) ? (int)$record->paidat : (int)$record->timecreated;
            if (!isset($first[$key]) || $paidat < $first[$key]) {
                $first[$key] = $paidat;
            }
        }
        return count(array_filter(
            $first,
            static fn(int $paidat): bool => $paidat >= $start && $paidat < $end
        ));
    }

    /** @param \stdClass[] $items */
    private function purchase_type(string $declared, array $items): string {
        $types = [];
        foreach ($items as $item) {
            $metadata = json_decode((string)($item->metadatajson ?? ''), true);
            if (is_array($metadata) && (($metadata['operation'] ?? '') === 'upgrade')) {
                return 'upgrade';
            }
            $type = strtolower(trim((string)($item->itemtype ?? '')));
            if ($type !== '') {
                $types[$type] = true;
            }
        }
        if (count($types) > 1) {
            return 'mixed';
        }
        $type = array_key_first($types) ?: strtolower(trim($declared));
        return match ($type) {
            'subscription', 'course', 'course_access' => 'course',
            'digital', 'digital_download' => 'digital',
            'bundle' => 'bundle',
            'upgrade' => 'upgrade',
            default => $type !== '' ? $type : 'unknown',
        };
    }

    private function identity_key(\stdClass $purchase): string {
        if (!empty($purchase->userid)) {
            return 'user:' . (int)$purchase->userid;
        }
        $email = strtolower(trim((string)($purchase->customeremail ?? '')));
        return $email !== '' ? 'email:' . $email : '';
    }

    private function was_guest_purchase(\stdClass $purchase): bool {
        $customer = json_decode((string)($purchase->customerjson ?? ''), true);
        if (!is_array($customer)) {
            return false;
        }
        return !empty($customer['guest']) || !empty($customer['wasguest']);
    }
}