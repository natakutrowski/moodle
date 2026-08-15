<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\purchase\action;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseSummary;

final class CommercePurchaseAdminClosureService {
    public const TABLE = 'local_subs_commerce_purchase_admin';
    public const STATE_CLOSED = 'closed';
    private const PENDING_AGE = DAYSECS;

    public function __construct(private readonly \moodle_database $db) {}

    public function can_close(CommercePurchaseSummary $purchase, ?int $now = null): bool {
        if ($purchase->adminclosed) {
            return false;
        }

        $payment = strtolower($purchase->paymentstatus);
        if (in_array($payment, ['failed', 'error', 'declined', 'cancelled', 'canceled'], true)) {
            return true;
        }

        if (in_array($payment, [
            'created', 'redirected', 'pending', 'payment_pending', 'authorized',
        ], true)) {
            $now ??= time();
            return ($now - $purchase->timecreated) >= self::PENDING_AGE;
        }

        return false;
    }

    public function close(
        int $purchaseid,
        int $userid,
        string $reason = ''
    ): void {
        $now = time();
        $existing = $this->db->get_record(
            self::TABLE,
            ['purchaseid' => $purchaseid],
            '*',
            IGNORE_MISSING
        );

        if ($existing) {
            $existing->state = self::STATE_CLOSED;
            $existing->reason = trim($reason);
            $existing->closedat = $now;
            $existing->closedby = $userid;
            $existing->timemodified = $now;
            $this->db->update_record(self::TABLE, $existing);
            return;
        }

        $this->db->insert_record(self::TABLE, (object)[
            'purchaseid' => $purchaseid,
            'state' => self::STATE_CLOSED,
            'reason' => trim($reason),
            'closedat' => $now,
            'closedby' => $userid,
            'timemodified' => $now,
        ]);
    }

    public function reopen(int $purchaseid): void {
        $this->db->delete_records(self::TABLE, ['purchaseid' => $purchaseid]);
    }

    public function is_closed(int $purchaseid): bool {
        return $this->db->record_exists(self::TABLE, [
            'purchaseid' => $purchaseid,
            'state' => self::STATE_CLOSED,
        ]);
    }

    /** @return array<int,\stdClass> keyed by purchase id */
    public function states(array $purchaseids): array {
        $purchaseids = array_values(array_unique(array_filter(
            array_map('intval', $purchaseids),
            static fn(int $id): bool => $id > 0
        )));
        if ($purchaseids === []) {
            return [];
        }

        [$insql, $params] = $this->db->get_in_or_equal(
            $purchaseids,
            SQL_PARAMS_NAMED,
            'purchaseadmin'
        );
        $records = $this->db->get_records_select(
            self::TABLE,
            'purchaseid ' . $insql,
            $params
        );

        $result = [];
        foreach ($records as $record) {
            $result[(int)$record->purchaseid] = $record;
        }
        return $result;
    }
}
