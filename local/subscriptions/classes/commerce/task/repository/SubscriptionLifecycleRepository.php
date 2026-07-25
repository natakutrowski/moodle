<?php

namespace local_subscriptions\commerce\task\repository;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\constants\Status;

final class SubscriptionLifecycleRepository {

    public function find_due_activation_ids(int $now, int $limit): array {
        global $DB;

        $records = $DB->get_records_select(
            'user_subscription',
            'status = :status AND start_date IS NOT NULL AND start_date <= :now',
            [
                'status' => Status::QUEUED,
                'now' => $now,
            ],
            'start_date ASC, id ASC',
            'id',
            0,
            $limit,
        );

        return array_map('intval', array_keys($records));
    }

    public function find_due_expiration_ids(int $now, int $limit): array {
        global $DB;

        $records = $DB->get_records_select(
            'user_subscription',
            'status = :status AND end_date IS NOT NULL AND end_date > 0 AND end_date < :now',
            [
                'status' => Status::ACTIVE,
                'now' => $now,
            ],
            'end_date ASC, id ASC',
            'id',
            0,
            $limit,
        );

        return array_map('intval', array_keys($records));
    }
}
