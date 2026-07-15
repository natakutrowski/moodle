<?php

namespace local_subscriptions\crm\inbox\repositories;

defined('MOODLE_INTERNAL') || die();

final class InboxUserMatchRepository {

    public function find_userids_by_email(
        string $email
    ): array {
        global $DB;

        $normalized = \core_text::strtolower(
            trim($email)
        );

        $sql = "
            SELECT u.id
              FROM {user} u
             WHERE u.deleted = 0
               AND u.email <> ''
               AND " . $DB->sql_compare_text(
                    'LOWER(u.email)'
                ) . " = " . $DB->sql_compare_text(':email') . "
          ORDER BY u.id ASC
        ";

        return array_map(
            'intval',
            array_keys(
                $DB->get_records_sql(
                    $sql,
                    ['email' => $normalized]
                )
            )
        );
    }

    public function find_userids_by_purchase_email(
        string $email
    ): array {
        global $DB;

        $normalized = \core_text::strtolower(
            trim($email)
        );

        $sql = "
            SELECT DISTINCT pr.userid AS id
              FROM {subscription_digital_payment_request} pr
             WHERE pr.userid IS NOT NULL
               AND pr.userid > 0
               AND " . $DB->sql_compare_text(
                    'LOWER(pr.email)'
                ) . " = " . $DB->sql_compare_text(':email') . "
        ";

        return array_map(
            'intval',
            array_keys(
                $DB->get_records_sql(
                    $sql,
                    ['email' => $normalized]
                )
            )
        );
    }

    public function get_user_email(
        int $userid
    ): ?string {
        global $DB;

        $user = $DB->get_record(
            'user',
            [
                'id' => $userid,
                'deleted' => 0,
            ],
            'id,email',
            IGNORE_MISSING
        );

        if (!$user) {
            return null;
        }

        $email = trim((string)$user->email);

        return $email !== ''
            ? $email
            : null;
    }
}