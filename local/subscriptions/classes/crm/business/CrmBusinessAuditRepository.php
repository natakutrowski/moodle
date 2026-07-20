<?php

namespace local_subscriptions\crm\business;

defined('MOODLE_INTERNAL') || die();

/**
 * Audits the existing CRM subscription and payment data.
 */
final class CrmBusinessAuditRepository {

    public function build_report(): CrmBusinessAuditReport {
        global $DB;

        $trialcondition =
            CrmBusinessSql::trial_subscription_condition('us', 'sp');

        $nontrialcondition =
            CrmBusinessSql::non_trial_subscription_condition('us', 'sp');

        $paidcondition =
            CrmBusinessSql::paid_subscription_condition('us', 'sp');

        $legacypaymentcondition =
            CrmBusinessSql::legacy_subscription_payment_condition('us');

        $successfulsubscriptionpayment =
            CrmBusinessSql::successful_subscription_payment_condition('pr');

        $successfuldigitalpayment =
            CrmBusinessSql::successful_digital_payment_condition('dpr');

        $subscriptions = (int)$DB->count_records('user_subscription');

        $trialsubscriptions = (int)$DB->count_records_sql("
            SELECT COUNT(DISTINCT us.id)
              FROM {user_subscription} us
         LEFT JOIN {subscription_plan} sp
                ON sp.id = us.planid
             WHERE {$trialcondition}
        ");

        $trialusers = (int)$DB->count_records_sql("
            SELECT COUNT(DISTINCT us.userid)
              FROM {user_subscription} us
         LEFT JOIN {subscription_plan} sp
                ON sp.id = us.planid
             WHERE {$trialcondition}
        ");

        $paidsubscriptions = (int)$DB->count_records_sql("
            SELECT COUNT(DISTINCT us.id)
              FROM {user_subscription} us
         LEFT JOIN {subscription_plan} sp
                ON sp.id = us.planid
             WHERE {$paidcondition}
        ");

        $paidcustomers = (int)$DB->count_records_sql("
            SELECT COUNT(DISTINCT us.userid)
              FROM {user_subscription} us
         LEFT JOIN {subscription_plan} sp
                ON sp.id = us.planid
             WHERE {$paidcondition}
        ");

        $legacypaidsubscriptions = (int)$DB->count_records_sql("
            SELECT COUNT(DISTINCT us.id)
              FROM {user_subscription} us
         LEFT JOIN {subscription_plan} sp
                ON sp.id = us.planid
             WHERE {$nontrialcondition}
               AND {$legacypaymentcondition}
               AND NOT EXISTS (
                    SELECT 1
                      FROM {subscription_payment_request} pr
                     WHERE pr.subscriptionid = us.id
                       AND {$successfulsubscriptionpayment}
               )
        ");

        $unconfirmedsubscriptions = (int)$DB->count_records_sql("
            SELECT COUNT(DISTINCT us.id)
              FROM {user_subscription} us
         LEFT JOIN {subscription_plan} sp
                ON sp.id = us.planid
             WHERE {$nontrialcondition}
               AND COALESCE(us.pricepaid, 0) > 0
               AND NOT EXISTS (
                    SELECT 1
                      FROM {subscription_payment_request} pr
                     WHERE pr.subscriptionid = us.id
                       AND {$successfulsubscriptionpayment}
               )
               AND LOWER(
                    COALESCE(us.payment_provider, '')
               ) = 'trial'
        ");

        $successfulsubscriptionpayments =
            (int)$DB->count_records_sql("
                SELECT COUNT(DISTINCT pr.id)
                  FROM {subscription_payment_request} pr
                 WHERE {$successfulsubscriptionpayment}
            ");

        $unlinkedsuccessfulsubscriptionpayments =
            (int)$DB->count_records_sql("
                SELECT COUNT(DISTINCT pr.id)
                FROM {subscription_payment_request} pr
                WHERE {$successfulsubscriptionpayment}
                AND (
                        pr.subscriptionid IS NULL
                        OR pr.subscriptionid <= 0
                )
            ");

        $successfuldigitalpayments = 0;
        $digitalcustomers = 0;
        $digitalpaymentstatuses = [];
        $digitalcurrencies = [];
        $digitalpaymentswithoutpaymentdate = 0;
        $digitalpaymentswithoutcurrency = 0;

        if (
            $DB->get_manager()->table_exists(
                'subscription_digital_payment_request'
            )
        ) {
            $successfuldigitalpayments =
                (int)$DB->count_records_sql("
                    SELECT COUNT(DISTINCT dpr.id)
                      FROM {subscription_digital_payment_request} dpr
                     WHERE {$successfuldigitalpayment}
                ");

            $digitalregisteredcustomers =
                (int)$DB->count_records_sql("
                    SELECT COUNT(DISTINCT dpr.userid)
                    FROM {subscription_digital_payment_request} dpr
                    WHERE {$successfuldigitalpayment}
                    AND dpr.userid IS NOT NULL
                    AND dpr.userid > 0
                ");

            $digitalguestcustomers =
                (int)$DB->count_records_sql("
                    SELECT COUNT(DISTINCT LOWER(TRIM(dpr.email)))
                    FROM {subscription_digital_payment_request} dpr
                    WHERE {$successfuldigitalpayment}
                    AND (
                            dpr.userid IS NULL
                            OR dpr.userid <= 0
                    )
                    AND TRIM(COALESCE(dpr.email, '')) <> ''
                ");

            $digitalcustomers =
                $digitalregisteredcustomers + $digitalguestcustomers;

            $digitalpaymentswithoutpaymentdate =
                (int)$DB->count_records_sql("
                    SELECT COUNT(DISTINCT dpr.id)
                      FROM {subscription_digital_payment_request} dpr
                     WHERE {$successfuldigitalpayment}
                       AND COALESCE(dpr.payment_date, 0) = 0
                ");

            $digitalpaymentswithoutcurrency =
                (int)$DB->count_records_sql("
                    SELECT COUNT(DISTINCT dpr.id)
                      FROM {subscription_digital_payment_request} dpr
                     WHERE {$successfuldigitalpayment}
                       AND TRIM(COALESCE(dpr.currency, '')) = ''
                ");

            $digitalpaymentstatuses = $this->get_grouped_counts(
                'subscription_digital_payment_request',
                'status'
            );

            $digitalcurrencies = $this->get_distinct_values(
                'subscription_digital_payment_request',
                'currency',
                true
            );
        }

        $trialplanstatusmismatches = (int)$DB->count_records_sql("
            SELECT COUNT(DISTINCT us.id)
            FROM {user_subscription} us
            JOIN {subscription_plan} sp
                ON sp.id = us.planid
            WHERE COALESCE(sp.is_trial, 0) = 0
            AND LOWER(COALESCE(us.status, '')) = 'trial'
        ");

        $trialprovidermismatches = (int)$DB->count_records_sql("
            SELECT COUNT(DISTINCT us.id)
              FROM {user_subscription} us
         LEFT JOIN {subscription_plan} sp
                ON sp.id = us.planid
             WHERE (
                    {$trialcondition}
                    AND LOWER(
                        COALESCE(us.payment_provider, '')
                    ) <> 'trial'
             )
                OR (
                    {$nontrialcondition}
                    AND LOWER(
                        COALESCE(us.payment_provider, '')
                    ) = 'trial'
             )
        ");

        $paidtrialsubscriptions = (int)$DB->count_records_sql("
            SELECT COUNT(DISTINCT us.id)
              FROM {user_subscription} us
         LEFT JOIN {subscription_plan} sp
                ON sp.id = us.planid
             WHERE {$trialcondition}
               AND (
                    COALESCE(us.pricepaid, 0) > 0
                    OR EXISTS (
                        SELECT 1
                          FROM {subscription_payment_request} pr
                         WHERE pr.subscriptionid = us.id
                           AND {$successfulsubscriptionpayment}
                    )
               )
        ");

        $paidrequestswithoutsubscription =
            (int)$DB->count_records_sql("
                SELECT COUNT(DISTINCT pr.id)
                FROM {subscription_payment_request} pr
            LEFT JOIN {user_subscription} us
                    ON us.id = pr.subscriptionid
                WHERE {$successfulsubscriptionpayment}
                AND pr.subscriptionid IS NOT NULL
                AND pr.subscriptionid > 0
                AND us.id IS NULL
            ");

        $paidrequestswithoutpaymentdate =
            (int)$DB->count_records_sql("
                SELECT COUNT(DISTINCT pr.id)
                  FROM {subscription_payment_request} pr
                 WHERE {$successfulsubscriptionpayment}
                   AND COALESCE(pr.payment_date, 0) = 0
            ");

        $paidrequestswithoutcurrency =
            (int)$DB->count_records_sql("
                SELECT COUNT(DISTINCT pr.id)
                  FROM {subscription_payment_request} pr
                 WHERE {$successfulsubscriptionpayment}
                   AND TRIM(COALESCE(pr.currency, '')) = ''
            ");

        $subscriptioncurrencymismatches =
            (int)$DB->count_records_sql("
                SELECT COUNT(DISTINCT pr.id)
                  FROM {subscription_payment_request} pr
                  JOIN {user_subscription} us
                    ON us.id = pr.subscriptionid
                 WHERE {$successfulsubscriptionpayment}
                   AND TRIM(COALESCE(pr.currency, '')) <> ''
                   AND TRIM(COALESCE(us.currency, '')) <> ''
                   AND UPPER(TRIM(pr.currency))
                       <> UPPER(TRIM(us.currency))
            ");

        return new CrmBusinessAuditReport(
            $subscriptions,
            $trialsubscriptions,
            $trialusers,
            $paidsubscriptions,
            $paidcustomers,
            $legacypaidsubscriptions,
            $unconfirmedsubscriptions,
            $successfulsubscriptionpayments,
            $unlinkedsuccessfulsubscriptionpayments,
            $successfuldigitalpayments,
            $digitalcustomers,
            $trialplanstatusmismatches,
            $trialprovidermismatches,
            $paidtrialsubscriptions,
            $paidrequestswithoutsubscription,
            $paidrequestswithoutpaymentdate,
            $paidrequestswithoutcurrency,
            $digitalpaymentswithoutpaymentdate,
            $digitalpaymentswithoutcurrency,
            $subscriptioncurrencymismatches,
            $this->get_grouped_counts(
                'user_subscription',
                'status'
            ),
            $this->get_grouped_counts(
                'subscription_payment_request',
                'status'
            ),
            $digitalpaymentstatuses,
            $this->get_distinct_values(
                'subscription_payment_request',
                'currency',
                true
            ),
            $digitalcurrencies
        );
    }

    /**
     * Return normalized values with their occurrence counts.
     *
     * Table and field names are hardcoded by internal calls only.
     *
     * @return array<string, int>
     */
    private function get_grouped_counts(
        string $table,
        string $field
    ): array {
        global $DB;

        if (!$DB->get_manager()->table_exists($table)) {
            return [];
        }

        $records = $DB->get_records_sql("
            SELECT
                LOWER(TRIM(COALESCE({$field}, ''))) AS normalizedvalue,
                COUNT(*) AS total
              FROM {{$table}}
          GROUP BY LOWER(TRIM(COALESCE({$field}, '')))
          ORDER BY normalizedvalue ASC
        ");

        $result = [];

        foreach ($records as $record) {
            $key = trim((string)$record->normalizedvalue);

            if ($key === '') {
                $key = '(empty)';
            }

            $result[$key] = (int)$record->total;
        }

        return $result;
    }

    /**
     * Return distinct normalized values from a database field.
     *
     * @return string[]
     */
    private function get_distinct_values(
        string $table,
        string $field,
        bool $uppercase = false
    ): array {
        global $DB;

        if (!$DB->get_manager()->table_exists($table)) {
            return [];
        }

        $expression = $uppercase
            ? "UPPER(TRIM(COALESCE({$field}, '')))"
            : "LOWER(TRIM(COALESCE({$field}, '')))";

        $records = $DB->get_records_sql("
            SELECT {$expression} AS normalizedvalue
              FROM {{$table}}
          GROUP BY {$expression}
          ORDER BY normalizedvalue ASC
        ");

        $result = [];

        foreach ($records as $record) {
            $value = trim((string)$record->normalizedvalue);

            if ($value !== '') {
                $result[] = $value;
            }
        }

        return $result;
    }
}