<?php

namespace local_subscriptions\digital\repositories;

defined('MOODLE_INTERNAL') || die();

final class DigitalPurchaseAdminActionRepository {

    private const TABLE = 'subscription_digital_payment_request';

    public function get_by_id(int $purchaseid): \stdClass {
        global $DB;

        $sql = "
            SELECT
                pr.*,
                p.name AS productname
            FROM {subscription_digital_payment_request} pr
            JOIN {subscription_digital_product} p
            ON p.id = pr.productid
            WHERE pr.id = :purchaseid
        ";

        return $DB->get_record_sql(
            $sql,
            ['purchaseid' => $purchaseid],
            MUST_EXIST
        );
    }

    public function update_status(
        int $purchaseid,
        string $status,
        int $timemodified
    ): void {
        global $DB;

        $DB->update_record(self::TABLE, (object)[
            'id' => $purchaseid,
            'status' => $status,
            'last_update' => $timemodified,
        ]);
    }
}