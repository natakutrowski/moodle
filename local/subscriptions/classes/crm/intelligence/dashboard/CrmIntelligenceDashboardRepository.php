<?php

namespace local_subscriptions\crm\intelligence\dashboard;

defined('MOODLE_INTERNAL') || die();

final class CrmIntelligenceDashboardRepository {

    public function get_candidate_users(int $limit = 50): array {
        global $DB;

        $recent = time() - (60 * DAYSECS);

        $digitalexists = $DB->get_manager()->table_exists('subscription_digital_payment_request');

        $digitalcondition = $digitalexists
            ? "OR EXISTS (
                   SELECT 1
                     FROM {subscription_digital_payment_request} dpr
                    WHERE dpr.userid = u.id
               )"
            : "";

        return array_values($DB->get_records_sql("
            SELECT u.*
              FROM {user} u
             WHERE u.deleted = 0
               AND u.id > 2
               AND (
                    u.lastaccess >= :recent
                    OR EXISTS (
                        SELECT 1
                          FROM {user_subscription} us
                         WHERE us.userid = u.id
                    )
                    $digitalcondition
               )
          ORDER BY u.lastaccess DESC, u.id DESC
        ", ['recent' => $recent], 0, $limit));
    }
}