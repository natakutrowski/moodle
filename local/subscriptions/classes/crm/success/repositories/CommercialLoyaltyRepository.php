<?php

namespace local_subscriptions\crm\success\repositories;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\runtime\CustomerSuccessRepositoryProfiler;

/**
 * Reads commercial and loyalty facts from subscriptions and payments.
 *
 * This repository does not calculate Customer Success scores.
 */
final class CommercialLoyaltyRepository {

    public function is_available(): bool {
        global $DB;

        return $DB->get_manager()->table_exists(
            new \xmldb_table('user_subscription')
        );
    }

    /**
     * Returns normalized commercial and loyalty statistics.
     *
     * Monetary totals remain separated by currency.
     *
     * @return array<string,int|float|null>
     */
    public function get_statistics(
        int $userid,
        string $email,
        int $measuredat
    ): array {
        if ($userid <= 0) {
            throw new \InvalidArgumentException(
                'Commercial loyalty userid must be greater than zero.'
            );
        }

        if ($measuredat <= 0) {
            throw new \InvalidArgumentException(
                'Commercial loyalty timestamp must be greater than zero.'
            );
        }

        $email = trim(
            \core_text::strtolower($email)
        );

        $subscriptions =
            CustomerSuccessRepositoryProfiler::measure(
                'commercial_loyalty',
                $userid,
                'subscription_statistics',
                fn(): array =>
                    $this->get_subscription_statistics(
                        $userid,
                        $measuredat
                    )
            );

        $subscriptionpayments =
            CustomerSuccessRepositoryProfiler::measure(
                'commercial_loyalty',
                $userid,
                'subscription_payment_statistics',
                fn(): array =>
                    $this->get_subscription_payment_statistics(
                        $userid,
                        $email,
                        $measuredat
                    )
            );

        $digitalpayments =
            CustomerSuccessRepositoryProfiler::measure(
                'commercial_loyalty',
                $userid,
                'digital_payment_statistics',
                fn(): array =>
                    $this->get_digital_payment_statistics(
                        $userid,
                        $email,
                        $measuredat
                    )
            );

        return CustomerSuccessRepositoryProfiler::measure(
            'commercial_loyalty',
            $userid,
            'result_merge',
            function () use (
                $subscriptions,
                $subscriptionpayments,
                $digitalpayments,
                $measuredat
            ): array {
                $firstcommercialat =
                    $this->minimum_positive([
                        (int)$subscriptions[
                            'first_subscription_at'
                        ],
                        (int)$subscriptionpayments[
                            'first_payment_at'
                        ],
                        (int)$digitalpayments[
                            'first_digital_payment_at'
                        ],
                    ]);

                $lastcommercialat = max(
                    (int)$subscriptions[
                        'last_subscription_at'
                    ],
                    (int)$subscriptionpayments[
                        'last_payment_at'
                    ],
                    (int)$digitalpayments[
                        'last_digital_payment_at'
                    ]
                );

                return array_merge(
                    $subscriptions,
                    $subscriptionpayments,
                    $digitalpayments,
                    [
                        'first_commercial_at' =>
                            $firstcommercialat,

                        'last_commercial_at' =>
                            $lastcommercialat,

                        'customer_age_days' =>
                            $firstcommercialat > 0
                                ? max(
                                    0,
                                    (int)floor(
                                        (
                                            $measuredat -
                                            $firstcommercialat
                                        ) / DAYSECS
                                    )
                                )
                                : null,
                    ]
                );
            }
        );
    }

    /**
     * @return array<string,int|float>
     */
    private function get_subscription_statistics(
        int $userid,
        int $measuredat
    ): array {
        global $DB;

        $sql = "
            SELECT
                COUNT(id) AS subscriptioncount,

                COALESCE(SUM(CASE
                    WHEN status = :active
                    THEN 1 ELSE 0
                END), 0) AS activesubscriptioncount,

                COALESCE(SUM(CASE
                    WHEN status = :trial
                    THEN 1 ELSE 0
                END), 0) AS trialsubscriptioncount,

                COALESCE(SUM(CASE
                    WHEN status = :expired
                    THEN 1 ELSE 0
                END), 0) AS expiredsubscriptioncount,

                COALESCE(SUM(CASE
                    WHEN status = :cancelled
                    THEN 1 ELSE 0
                END), 0) AS cancelledsubscriptioncount,

                COALESCE(SUM(CASE
                    WHEN status = :replaced
                    THEN 1 ELSE 0
                END), 0) AS replacedsubscriptioncount,

                COALESCE(SUM(CASE
                    WHEN currency = :eur
                     AND status IN (
                        :paidactive,
                        :paidexpired,
                        :paidcancelled,
                        :paidreplaced
                     )
                    THEN pricepaid ELSE 0
                END), 0) AS subscriptionrevenueeur,

                COALESCE(SUM(CASE
                    WHEN currency = :rub
                     AND status IN (
                        :rubactive,
                        :rubexpired,
                        :rubcancelled,
                        :rubreplaced
                     )
                    THEN pricepaid ELSE 0
                END), 0) AS subscriptionrevenuerub,

                MIN(
                    COALESCE(
                        NULLIF(creation_date, 0),
                        NULLIF(start_date, 0)
                    )
                ) AS firstsubscriptionat,

                MAX(
                    COALESCE(
                        NULLIF(last_update, 0),
                        NULLIF(creation_date, 0),
                        NULLIF(start_date, 0)
                    )
                ) AS lastsubscriptionat,

                MAX(CASE
                    WHEN status = :activeend
                    THEN end_date ELSE 0
                END) AS activeendat,

                MIN(CASE
                    WHEN status = :firstactive
                    THEN start_date ELSE NULL
                END) AS firstactiveat

              FROM {user_subscription}
             WHERE userid = :userid
        ";

        $record = $DB->get_record_sql(
            $sql,
            [
                'userid' => $userid,

                'active' => 'active',
                'trial' => 'trial',
                'expired' => 'expired',
                'cancelled' => 'cancelled',
                'replaced' => 'replaced',

                'eur' => 'EUR',
                'paidactive' => 'active',
                'paidexpired' => 'expired',
                'paidcancelled' => 'cancelled',
                'paidreplaced' => 'replaced',

                'rub' => 'RUB',
                'rubactive' => 'active',
                'rubexpired' => 'expired',
                'rubcancelled' => 'cancelled',
                'rubreplaced' => 'replaced',

                'activeend' => 'active',
                'firstactive' => 'active',
            ]
        );

        $activeendat =
            (int)($record->activeendat ?? 0);

        return [
            'subscription_count' =>
                (int)($record->subscriptioncount ?? 0),

            'active_subscription_count' =>
                (int)($record->activesubscriptioncount ?? 0),

            'trial_subscription_count' =>
                (int)($record->trialsubscriptioncount ?? 0),

            'expired_subscription_count' =>
                (int)($record->expiredsubscriptioncount ?? 0),

            'cancelled_subscription_count' =>
                (int)($record->cancelledsubscriptioncount ?? 0),

            'replaced_subscription_count' =>
                (int)($record->replacedsubscriptioncount ?? 0),

            'subscription_revenue_eur' =>
                round(
                    (float)($record->subscriptionrevenueeur ?? 0),
                    2
                ),

            'subscription_revenue_rub' =>
                round(
                    (float)($record->subscriptionrevenuerub ?? 0),
                    2
                ),

            'first_subscription_at' =>
                (int)($record->firstsubscriptionat ?? 0),

            'last_subscription_at' =>
                (int)($record->lastsubscriptionat ?? 0),

            'active_subscription_end_at' =>
                $activeendat,

            'active_subscription_remaining_days' =>
                $activeendat > 0
                    ? max(
                        0,
                        (int)floor(
                            ($activeendat - $measuredat) /
                            DAYSECS
                        )
                    )
                    : 0,

            'first_active_subscription_at' =>
                (int)($record->firstactiveat ?? 0),
        ];
    }

    /**
     * @return array<string,int|float>
     */
    private function get_subscription_payment_statistics(
        int $userid,
        string $email,
        int $measuredat
    ): array {
        global $DB;

        $manager = $DB->get_manager();

        $table = new \xmldb_table(
            'subscription_payment_request'
        );

        $schema = CustomerSuccessRepositoryProfiler::measure(
            'commercial_loyalty',
            $userid,
            'subscription_payment_schema_detection',
            function () use (
                $manager,
                $table
            ): array {
                if (!$manager->table_exists($table)) {
                    return [
                        'tableexists' => false,
                        'hasuserid' => false,
                        'hasemail' => false,
                        'hasstatus' => false,
                    ];
                }

                return [
                    'tableexists' => true,

                    'hasuserid' =>
                        $manager->field_exists(
                            $table,
                            new \xmldb_field('userid')
                        ),

                    'hasemail' =>
                        $manager->field_exists(
                            $table,
                            new \xmldb_field('email')
                        ),

                    'hasstatus' =>
                        $manager->field_exists(
                            $table,
                            new \xmldb_field('status')
                        ),
                ];
            }
        );

        if (!$schema['tableexists']) {
            return $this->empty_subscription_payment_statistics();
        }

        $hasuserid = $schema['hasuserid'];
        $hasemail = $schema['hasemail'];
        $hasstatus = $schema['hasstatus'];

        if (!$hasstatus || (!$hasuserid && !$hasemail)) {
            return $this->empty_subscription_payment_statistics();
        }

        $from30days = $measuredat - (30 * DAYSECS);

        $whereparts = [];
        $params = [];

        if ($hasuserid) {
            $whereparts[] = 'userid = :userid';
            $params['userid'] = $userid;
        }

        if ($hasemail && $email !== '') {
            $whereparts[] = 'LOWER(email) = :email';
            $params['email'] = $email;
        }

        if ($whereparts === []) {
            return $this->empty_subscription_payment_statistics();
        }

        $where = '(' . implode(
            ' OR ',
            $whereparts
        ) . ')';

        $expressions = CustomerSuccessRepositoryProfiler::measure(
            'commercial_loyalty',
            $userid,
            'subscription_payment_expression_build',
            fn(): array => [
                'activity' =>
                    $this->payment_request_activity_expression(
                        $table
                    ),

                'payment' =>
                    $this->payment_request_payment_expression(
                        $table
                    ),
            ]
        );

        $activityexpression = $expressions['activity'];
        $paymentexpression = $expressions['payment'];

        $sql = "
            SELECT
                COUNT(id) AS paymentcount,

                COALESCE(SUM(CASE
                    WHEN UPPER(status) IN (
                        :paid,
                        :completed
                    )
                    THEN 1 ELSE 0
                END), 0) AS successfulcount,

                COALESCE(SUM(CASE
                    WHEN UPPER(status) IN (
                        :failed,
                        :cancelled
                    )
                    THEN 1 ELSE 0
                END), 0) AS failedcount,

                COALESCE(SUM(CASE
                    WHEN UPPER(status) = :pending
                    THEN 1 ELSE 0
                END), 0) AS pendingcount,

                COALESCE(SUM(CASE
                    WHEN UPPER(status) IN (
                        :recentpaid,
                        :recentcompleted
                    )
                    AND {$activityexpression} >= :from30paid
                    THEN 1 ELSE 0
                END), 0) AS successful30d,

                COALESCE(SUM(CASE
                    WHEN UPPER(status) IN (
                        :recentfailed,
                        :recentcancelled
                    )
                    AND {$activityexpression} >= :from30failed
                    THEN 1 ELSE 0
                END), 0) AS failed30d,

                MIN({$paymentexpression}) AS firstpaymentat,

                MAX({$activityexpression}) AS lastpaymentat,

                MAX(CASE
                    WHEN UPPER(status) IN (
                        :lastfailed,
                        :lastcancelled
                    )
                    THEN {$activityexpression}
                    ELSE 0
                END) AS lastfailedpaymentat

            FROM {subscription_payment_request}
            WHERE {$where}
        ";

        $params += [
            'paid' => 'PAID',
            'completed' => 'COMPLETED',
            'failed' => 'FAILED',
            'cancelled' => 'CANCELLED',
            'pending' => 'PENDING',

            'recentpaid' => 'PAID',
            'recentcompleted' => 'COMPLETED',
            'from30paid' => $from30days,

            'recentfailed' => 'FAILED',
            'recentcancelled' => 'CANCELLED',
            'from30failed' => $from30days,

            'lastfailed' => 'FAILED',
            'lastcancelled' => 'CANCELLED',
        ];

        $record = CustomerSuccessRepositoryProfiler::measure(
            'commercial_loyalty',
            $userid,
            'subscription_payment_query',
            fn(): \stdClass =>
                $DB->get_record_sql(
                    $sql,
                    $params
                )
        );

        return CustomerSuccessRepositoryProfiler::measure(
            'commercial_loyalty',
            $userid,
            'subscription_payment_normalization',
            fn(): array => [
                'subscription_payment_count' =>
                    (int)($record->paymentcount ?? 0),

                'successful_subscription_payment_count' =>
                    (int)($record->successfulcount ?? 0),

                'failed_subscription_payment_count' =>
                    (int)($record->failedcount ?? 0),

                'pending_subscription_payment_count' =>
                    (int)($record->pendingcount ?? 0),

                'successful_subscription_payments_30d' =>
                    (int)($record->successful30d ?? 0),

                'failed_subscription_payments_30d' =>
                    (int)($record->failed30d ?? 0),

                'first_payment_at' =>
                    (int)($record->firstpaymentat ?? 0),

                'last_payment_at' =>
                    (int)($record->lastpaymentat ?? 0),

                'last_failed_payment_at' =>
                    (int)($record->lastfailedpaymentat ?? 0),
            ]
        );
    }

    /**
     * @return array<string,int|float>
     */
    private function get_digital_payment_statistics(
        int $userid,
        string $email,
        int $measuredat
    ): array {
        global $DB;

        if (
            !$DB->get_manager()->table_exists(
                new \xmldb_table(
                    'subscription_digital_payment_request'
                )
            )
        ) {
            return [
                'digital_purchase_count' => 0,
                'successful_digital_purchase_count' => 0,
                'failed_digital_purchase_count' => 0,
                'digital_revenue_eur' => 0.0,
                'digital_revenue_rub' => 0.0,
                'digital_purchases_30d' => 0,
                'first_digital_payment_at' => 0,
                'last_digital_payment_at' => 0,
            ];
        }

        $from30days = $measuredat - (30 * DAYSECS);

        $sql = "
            SELECT
                COUNT(id) AS purchasecount,

                COALESCE(SUM(CASE
                    WHEN UPPER(status) IN (
                        :paid,
                        :completed
                    )
                    THEN 1 ELSE 0
                END), 0) AS successfulcount,

                COALESCE(SUM(CASE
                    WHEN UPPER(status) IN (
                        :failed,
                        :cancelled
                    )
                    THEN 1 ELSE 0
                END), 0) AS failedcount,

                COALESCE(SUM(CASE
                    WHEN UPPER(status) IN (
                        :eurpaid,
                        :eurcompleted
                    )
                     AND currency = :eur
                    THEN price ELSE 0
                END), 0) AS revenueeur,

                COALESCE(SUM(CASE
                    WHEN UPPER(status) IN (
                        :rubpaid,
                        :rubcompleted
                    )
                     AND currency = :rub
                    THEN price ELSE 0
                END), 0) AS revenuerub,

                COALESCE(SUM(CASE
                    WHEN UPPER(status) IN (
                        :recentpaid,
                        :recentcompleted
                    )
                     AND COALESCE(
                        payment_date,
                        last_update,
                        creation_date
                     ) >= :from30
                    THEN 1 ELSE 0
                END), 0) AS purchases30d,

                MIN(
                    COALESCE(
                        payment_date,
                        creation_date
                    )
                ) AS firstpaymentat,

                MAX(
                    COALESCE(
                        payment_date,
                        last_update,
                        creation_date
                    )
                ) AS lastpaymentat

              FROM {subscription_digital_payment_request}
             WHERE userid = :userid
                OR LOWER(email) = :email
        ";

        $record = $DB->get_record_sql(
            $sql,
            [
                'userid' => $userid,
                'email' => $email,

                'paid' => 'PAID',
                'completed' => 'COMPLETED',
                'failed' => 'FAILED',
                'cancelled' => 'CANCELLED',

                'eurpaid' => 'PAID',
                'eurcompleted' => 'COMPLETED',
                'eur' => 'EUR',

                'rubpaid' => 'PAID',
                'rubcompleted' => 'COMPLETED',
                'rub' => 'RUB',

                'recentpaid' => 'PAID',
                'recentcompleted' => 'COMPLETED',
                'from30' => $from30days,
            ]
        );

        return [
            'digital_purchase_count' =>
                (int)($record->purchasecount ?? 0),

            'successful_digital_purchase_count' =>
                (int)($record->successfulcount ?? 0),

            'failed_digital_purchase_count' =>
                (int)($record->failedcount ?? 0),

            'digital_revenue_eur' =>
                round(
                    (float)($record->revenueeur ?? 0),
                    2
                ),

            'digital_revenue_rub' =>
                round(
                    (float)($record->revenuerub ?? 0),
                    2
                ),

            'digital_purchases_30d' =>
                (int)($record->purchases30d ?? 0),

            'first_digital_payment_at' =>
                (int)($record->firstpaymentat ?? 0),

            'last_digital_payment_at' =>
                (int)($record->lastpaymentat ?? 0),
        ];
    }

    /**
     * Returns the oldest strictly positive timestamp.
     *
     * @param int[] $values
     */
    private function minimum_positive(
        array $values
    ): int {
        $values = array_values(
            array_filter(
                $values,
                static fn(int $value): bool => $value > 0
            )
        );

        return $values !== []
            ? min($values)
            : 0;
    }

    /**
     * @return array<string,int>
     */
    private function empty_subscription_payment_statistics(): array {
        return [
            'subscription_payment_count' => 0,
            'successful_subscription_payment_count' => 0,
            'failed_subscription_payment_count' => 0,
            'pending_subscription_payment_count' => 0,
            'successful_subscription_payments_30d' => 0,
            'failed_subscription_payments_30d' => 0,
            'first_payment_at' => 0,
            'last_payment_at' => 0,
            'last_failed_payment_at' => 0,
        ];
    }

    /**
     * Returns a safe SQL expression for the most recent known update.
     *
     * Field names are selected exclusively from a fixed whitelist after
     * checking the real database schema.
     */
    private function payment_request_activity_expression(
        \xmldb_table $table
    ): string {
        global $DB;

        $manager = $DB->get_manager();
        $fields = [];

        foreach ([
            'payment_date',
            'last_update',
            'last_attempt',
            'creation_date',
            'timecreated',
        ] as $fieldname) {
            if (
                $manager->field_exists(
                    $table,
                    new \xmldb_field($fieldname)
                )
            ) {
                $fields[] = $fieldname;
            }
        }

        if ($fields === []) {
            return '0';
        }

        if (count($fields) === 1) {
            return 'COALESCE(' . $fields[0] . ', 0)';
        }

        return 'COALESCE(' .
            implode(', ', $fields) .
            ', 0)';
    }

    /**
     * Returns a safe SQL expression for the original payment timestamp.
     */
    private function payment_request_payment_expression(
        \xmldb_table $table
    ): string {
        global $DB;

        $manager = $DB->get_manager();
        $fields = [];

        foreach ([
            'payment_date',
            'creation_date',
            'timecreated',
        ] as $fieldname) {
            if (
                $manager->field_exists(
                    $table,
                    new \xmldb_field($fieldname)
                )
            ) {
                $fields[] = $fieldname;
            }
        }

        if ($fields === []) {
            return '0';
        }

        if (count($fields) === 1) {
            return 'COALESCE(' . $fields[0] . ', 0)';
        }

        return 'COALESCE(' .
            implode(', ', $fields) .
            ', 0)';
    }

    public function get_user_email(
        int $userid
    ): string {
        global $DB;

        if ($userid <= 0) {
            throw new \InvalidArgumentException(
                'Commercial loyalty userid must be greater than zero.'
            );
        }

        return (string)$DB->get_field(
            'user',
            'email',
            [
                'id' => $userid,
                'deleted' => 0,
            ],
            MUST_EXIST
        );
    }

}