<?php

namespace local_subscriptions\digital\repositories;

defined('MOODLE_INTERNAL') || die();

final class DigitalPurchaseAdminRepository {

    public function count(
        string $lang,
        string $status,
        string $issue,
        string $campususer,
        bool $dbpaidonly
    ): int {
        global $DB;

        [$where, $params] = $this->build_filters($status, $issue, $campususer, $dbpaidonly);

        $sql = "
            SELECT COUNT(1)
              FROM {subscription_digital_payment_request} pr
              JOIN {subscription_digital_product} p ON p.id = pr.productid
             WHERE {$where}
        ";

        return (int)$DB->count_records_sql($sql, $params);
    }

    public function get_records(
        string $lang,
        string $status,
        string $issue,
        string $campususer,
        bool $dbpaidonly,
        int $offset = 0,
        int $limit = 0
    ): array {
        global $DB;

        [$where, $params] = $this->build_filters($status, $issue, $campususer, $dbpaidonly);
        $params['lang'] = $lang;

        $campususerexists = $this->campus_user_exists_sql();

        $sql = "
            SELECT
                pr.id,
                pr.productid,
                COALESCE(NULLIF(tcur.title, ''), NULLIF(tfr.title, ''), p.name) AS productname,
                p.slug,
                p.filename,
                p.mobile_filename,

                pr.firstname,
                pr.lastname,
                pr.email,
                pr.buyer_lang,
                pr.userid,
                COALESCE(pr.userid, uemail.id) AS crmuserid,
                CASE
                    WHEN ({$campususerexists}) THEN 1
                    ELSE 0
                END AS hascampususer,

                pr.price,
                pr.currency,
                pr.payment_provider,
                pr.status,
                pr.transactionid,
                pr.sessionid,

                pr.emailsent,
                pr.receipt_sent,

                pr.creation_date,
                pr.payment_date,
                pr.last_update,

                pr.download_token,
                pr.download_token_expires,
                pr.last_error
            FROM {subscription_digital_payment_request} pr
            JOIN {subscription_digital_product} p ON p.id = pr.productid
            LEFT JOIN {user} uemail
                ON " . $DB->sql_compare_text('uemail.email') . " = " . $DB->sql_compare_text('pr.email') . "
                AND uemail.deleted = 0
                AND uemail.suspended = 0
            LEFT JOIN {subscription_digital_product_lang} tcur
                ON tcur.productid = p.id
                AND tcur.lang = :lang
            LEFT JOIN {subscription_digital_product_lang} tfr
                ON tfr.productid = p.id
                AND tfr.lang = 'fr'
            WHERE {$where}
            ORDER BY pr.creation_date DESC, pr.id DESC
        ";

        return $limit > 0
            ? $DB->get_records_sql($sql, $params, $offset, $limit)
            : $DB->get_records_sql($sql, $params);
    }

    private function build_filters(string $status, string $issue, string $campususer, bool $dbpaidonly): array {
        $where = '1 = 1';
        $params = [];

        if ($dbpaidonly) {
            $where .= " AND pr.status IN ('paid', 'completed', 'PAID', 'COMPLETED')";
        } else if ($status !== '') {
            $where .= " AND LOWER(pr.status) = :statusfilter";
            $params['statusfilter'] = strtolower($status);
        }

        if ($issue === 'email_error') {
            $where .= "
                AND LOWER(pr.status) IN (:issuepaid, :issuecompleted)
                AND pr.last_error IS NOT NULL
                AND pr.last_error <> ''
            ";

            $params['issuepaid'] = 'paid';
            $params['issuecompleted'] = 'completed';
        }

        if ($issue === 'expired_token') {
            $where .= "
                AND LOWER(pr.status) IN (:tokenpaid, :tokencompleted)
                AND pr.download_token IS NOT NULL
                AND pr.download_token <> ''
                AND pr.download_token_expires IS NOT NULL
                AND pr.download_token_expires > 0
                AND pr.download_token_expires < :now
            ";

            $params['tokenpaid'] = 'paid';
            $params['tokencompleted'] = 'completed';
            $params['now'] = time();
        }

        $campususerexists = $this->campus_user_exists_sql();

        if ($campususer === 'registered') {
            $where .= " AND ({$campususerexists})";
        } else if ($campususer === 'guest') {
            $where .= " AND NOT ({$campususerexists})";
        }

        return [$where, $params];
    }

    private function campus_user_exists_sql(): string {
        global $DB;

        return "
            (
                pr.userid IS NOT NULL
                AND EXISTS (
                    SELECT 1
                      FROM {user} u1
                     WHERE u1.id = pr.userid
                       AND u1.deleted = 0
                       AND u1.suspended = 0
                )
            )
            OR EXISTS (
                SELECT 1
                  FROM {user} u2
                 WHERE " . $DB->sql_compare_text('u2.email') . " = " . $DB->sql_compare_text('pr.email') . "
                   AND u2.deleted = 0
                   AND u2.suspended = 0
            )
        ";
    }
}