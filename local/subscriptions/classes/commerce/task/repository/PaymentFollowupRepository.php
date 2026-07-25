<?php
namespace local_subscriptions\commerce\task\repository;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\constants\Status;

final class PaymentFollowupRepository {
    public function find_pending_to_expire(int $createdbefore, int $limit): array {
        global $DB;

        return $DB->get_records_select(
            'subscription_payment_request',
            'status = :status AND creation_date <= :createdbefore',
            [
                'status' => Status::PENDING,
                'createdbefore' => $createdbefore,
            ],
            'creation_date ASC, id ASC',
            '*',
            0,
            $limit
        );
    }

    public function find_reminder_candidates(int $limit): array {
        global $DB;

        return $DB->get_records_select(
            'subscription_payment_request',
            'status IN (:pending, :failed, :expired) AND reminder_stage < 2',
            [
                'pending' => Status::PENDING,
                'failed' => Status::FAILED,
                'expired' => Status::EXPIRED,
            ],
            'creation_date ASC, id ASC',
            '*',
            0,
            $limit
        );
    }

    public function mark_expired_if_pending(int $id, int $now): bool {
        global $DB;

        $record = $DB->get_record(
            'subscription_payment_request',
            ['id' => $id],
            'id,status',
            IGNORE_MISSING
        );

        if (!$record || $record->status !== Status::PENDING) {
            return false;
        }

        $record->status = Status::EXPIRED;
        $record->last_attempt = $now;
        $record->last_update = $now;
        $DB->update_record('subscription_payment_request', $record);
        return true;
    }

    public function record_first_reminder(int $id, int $now): bool {
        global $DB;

        $record = $DB->get_record(
            'subscription_payment_request',
            ['id' => $id],
            'id,reminder_stage',
            IGNORE_MISSING
        );

        if (!$record || (int)$record->reminder_stage !== 0) {
            return false;
        }

        $record->reminder_stage = 1;
        $record->reminder1_at = $now;
        $record->last_update = $now;
        $DB->update_record('subscription_payment_request', $record);
        return true;
    }

    public function record_second_reminder(int $id, int $now): bool {
        global $DB;

        $record = $DB->get_record(
            'subscription_payment_request',
            ['id' => $id],
            'id,status,reminder_stage',
            IGNORE_MISSING
        );

        if (!$record || (int)$record->reminder_stage >= 2) {
            return false;
        }

        $record->reminder_stage = 2;
        $record->reminder2_at = $now;
        $record->last_update = $now;
        if ($record->status === Status::PENDING) {
            $record->status = Status::EXPIRED;
        }
        $DB->update_record('subscription_payment_request', $record);
        return true;
    }
}
