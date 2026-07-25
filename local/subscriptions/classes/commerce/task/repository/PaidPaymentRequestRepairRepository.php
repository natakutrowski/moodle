<?php

namespace local_subscriptions\commerce\task\repository;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\constants\Status;

final class PaidPaymentRequestRepairRepository {

    public const QUARANTINE_PREFIX = '[commerce_repair_quarantine]';

    public function find_candidates(int $before, int $limit): array {
        global $DB;

        $sql = "SELECT pr.*,
                       p.id AS repair_plan_exists,
                       p.is_active AS repair_plan_active,
                       u.id AS repair_user_exists,
                       u.deleted AS repair_user_deleted,
                       u.email AS repair_user_email
                  FROM {subscription_payment_request} pr
             LEFT JOIN {subscription_plan} p
                    ON p.id = pr.planid
             LEFT JOIN {user} u
                    ON u.id = pr.userid
                 WHERE pr.status IN (:paid, :completed)
                   AND (pr.subscriptionid IS NULL OR pr.subscriptionid = 0)
                   AND pr.payment_date <= :before
                   AND (
                        pr.last_error IS NULL
                        OR pr.last_error NOT LIKE :quarantineprefix
                   )
              ORDER BY pr.payment_date ASC,
                       pr.id ASC";

        return $DB->get_records_sql(
            $sql,
            [
                'paid' => Status::PAID,
                'completed' => Status::COMPLETED,
                'before' => $before,
                'quarantineprefix' => self::QUARANTINE_PREFIX . '%',
            ],
            0,
            $limit,
        );
    }

    public function count_quarantined(): int {
        global $DB;

        return (int) $DB->count_records_select(
            'subscription_payment_request',
            'last_error LIKE :quarantineprefix',
            ['quarantineprefix' => self::QUARANTINE_PREFIX . '%'],
        );
    }

    public function quarantine(int $paymentrequestid, string $reason): void {
        global $DB;

        $message = self::QUARANTINE_PREFIX . ' ' . trim($reason);

        $DB->set_field(
            'subscription_payment_request',
            'last_error',
            $message,
            ['id' => $paymentrequestid],
        );

        $DB->set_field(
            'subscription_payment_request',
            'last_update',
            time(),
            ['id' => $paymentrequestid],
        );
    }

    public function find(int $paymentrequestid): ?\stdClass {
        global $DB;

        return $DB->get_record(
            'subscription_payment_request',
            ['id' => $paymentrequestid],
            '*',
            IGNORE_MISSING,
        ) ?: null;
    }
}
