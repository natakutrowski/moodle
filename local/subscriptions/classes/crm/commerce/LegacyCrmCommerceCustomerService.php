<?php

namespace local_subscriptions\crm\commerce;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\domain\CommercePurchase;

/**
 * Emergency read-only fallback for CRM Commerce customer summaries.
 *
 * This service intentionally performs simple direct aggregates over the
 * historical tables. It is only used if the new Commerce domain fails.
 */
class LegacyCrmCommerceCustomerService {

    public function build_snapshot(
        int $userid,
        ?string $email = null
    ): CrmCommerceCustomerSnapshot {
        global $DB;

        if ($userid <= 0) {
            throw new \InvalidArgumentException(
                'CRM Commerce customer identifier must be greater than zero.'
            );
        }

        $subscriptioncount = $DB->count_records(
            'user_subscription',
            [
                'userid' => $userid,
            ]
        );

        [$digitalselect, $digitalparams] =
            $this->build_digital_customer_condition(
                $userid,
                $email
            );

        $digitalpurchasecount =
            $DB->count_records_select(
                'subscription_digital_payment_request',
                $digitalselect,
                $digitalparams
            );

        $revenuebycurrency =
            $this->load_revenue_by_currency(
                $userid,
                $digitalselect,
                $digitalparams
            );

        $providerusage =
            $this->load_provider_usage(
                $userid,
                $digitalselect,
                $digitalparams
            );

        $statususage =
            $this->load_status_usage(
                $userid,
                $digitalselect,
                $digitalparams
            );

        [
            $firstpurchaseat,
            $lastpurchaseat,
        ] = $this->load_purchase_dates(
            $userid,
            $digitalselect,
            $digitalparams
        );

        return new CrmCommerceCustomerSnapshot(
            $userid,
            [],
            $subscriptioncount,
            $digitalpurchasecount,
            $revenuebycurrency,
            $providerusage,
            $statususage,
            $firstpurchaseat,
            $lastpurchaseat,
            CrmCommerceSnapshotSource::LEGACY_FALLBACK
        );
    }

    /**
     * @return array{0:string,1:array<string,mixed>}
     */
    private function build_digital_customer_condition(
        int $userid,
        ?string $email
    ): array {
        $email = trim(
            \core_text::strtolower(
                (string)$email
            )
        );

        if ($email === '') {
            return [
                'userid = :userid',
                [
                    'userid' => $userid,
                ],
            ];
        }

        return [
            '(userid = :userid OR LOWER(email) = :email)',
            [
                'userid' => $userid,
                'email' => $email,
            ],
        ];
    }

    /**
     * @return array<string,int>
     */
    private function load_revenue_by_currency(
        int $userid,
        string $digitalselect,
        array $digitalparams
    ): array {
        global $DB;

        $result = [];

        $subscriptions = $DB->get_records_sql(
            "
                SELECT
                    COALESCE(
                        NULLIF(latestrequest.currency, ''),
                        NULLIF(subscription.currency, ''),
                        'EUR'
                    ) AS currency,

                    SUM(
                        CASE
                            WHEN latestrequest.id IS NOT NULL
                                 AND latestrequest.status IN (
                                     'paid',
                                     'completed'
                                 )
                            THEN
                                CASE
                                    WHEN COALESCE(
                                        latestrequest.amount_minor,
                                        0
                                    ) > 0
                                    THEN latestrequest.amount_minor

                                    ELSE ROUND(
                                        COALESCE(
                                            latestrequest.locked_final_price,
                                            latestrequest.price,
                                            subscription.pricepaid,
                                            0
                                        ) * 100
                                    )
                                END

                            WHEN latestrequest.id IS NULL
                                 AND subscription.status IN (
                                     'active',
                                     'expired',
                                     'replaced',
                                     'queued'
                                 )
                                 AND COALESCE(
                                     subscription.payment_failed,
                                     0
                                 ) = 0
                            THEN ROUND(
                                COALESCE(
                                    subscription.pricepaid,
                                    0
                                ) * 100
                            )

                            ELSE 0
                        END
                    ) AS amountminor

                  FROM {user_subscription} subscription

             LEFT JOIN (
                        SELECT request.*
                          FROM {subscription_payment_request} request

                          JOIN (
                                    SELECT
                                        subscriptionid,
                                        MAX(id) AS latestid
                                      FROM {subscription_payment_request}
                                  GROUP BY subscriptionid
                               ) latest
                            ON latest.latestid = request.id
                       ) latestrequest
                    ON latestrequest.subscriptionid =
                       subscription.id

                 WHERE subscription.userid = :userid

              GROUP BY
                    COALESCE(
                        NULLIF(latestrequest.currency, ''),
                        NULLIF(subscription.currency, ''),
                        'EUR'
                    )
            ",
            [
                'userid' => $userid,
            ]
        );

        foreach ($subscriptions as $record) {
            $currency = $this->normalise_currency(
                $record->currency ?? null
            );

            $result[$currency] =
                ($result[$currency] ?? 0)
                + (int)$record->amountminor;
        }

        $digital = $DB->get_records_sql(
            "
                SELECT
                    currency,
                    SUM(
                        CASE
                            WHEN amount_minor > 0
                                THEN amount_minor
                            ELSE ROUND(
                                COALESCE(
                                    locked_final_price,
                                    price,
                                    0
                                ) * 100
                            )
                        END
                    ) AS amountminor
                  FROM {subscription_digital_payment_request}
                 WHERE {$digitalselect}
                   AND status IN ('paid', 'completed')
              GROUP BY currency
            ",
            $digitalparams
        );

        foreach ($digital as $record) {
            $currency = $this->normalise_currency(
                $record->currency ?? null
            );

            $result[$currency] =
                ($result[$currency] ?? 0)
                + (int)$record->amountminor;
        }

        ksort($result);

        return $result;
    }

    /**
     * @return array<string,int>
     */
    private function load_provider_usage(
        int $userid,
        string $digitalselect,
        array $digitalparams
    ): array {
        global $DB;

        $result = [];

        $subscriptions = $DB->get_records_sql(
            "
                SELECT
                    COALESCE(
                        NULLIF(payment_provider, ''),
                        'unknown'
                    ) AS provider,
                    COUNT(1) AS total
                  FROM {user_subscription}
                 WHERE userid = :userid
              GROUP BY payment_provider
            ",
            [
                'userid' => $userid,
            ]
        );

        foreach ($subscriptions as $record) {
            $provider = strtolower(
                trim(
                    (string)$record->provider
                )
            );

            $result[$provider] =
                ($result[$provider] ?? 0)
                + (int)$record->total;
        }

        $digital = $DB->get_records_sql(
            "
                SELECT
                    COALESCE(
                        NULLIF(payment_provider, ''),
                        'unknown'
                    ) AS provider,
                    COUNT(1) AS total
                  FROM {subscription_digital_payment_request}
                 WHERE {$digitalselect}
              GROUP BY payment_provider
            ",
            $digitalparams
        );

        foreach ($digital as $record) {
            $provider = strtolower(
                trim(
                    (string)$record->provider
                )
            );

            $result[$provider] =
                ($result[$provider] ?? 0)
                + (int)$record->total;
        }

        ksort($result);

        return $result;
    }

    /**
     * @return array<string,int>
     */
    private function load_status_usage(
        int $userid,
        string $digitalselect,
        array $digitalparams
    ): array {
        global $DB;

        $result = [];

        $subscriptions = $DB->get_records_sql(
            "
                SELECT status, COUNT(1) AS total
                  FROM {user_subscription}
                 WHERE userid = :userid
              GROUP BY status
            ",
            [
                'userid' => $userid,
            ]
        );

        foreach ($subscriptions as $record) {
            $status = $this->normalise_status(
                $record->status ?? null
            );

            $result[$status] =
                ($result[$status] ?? 0)
                + (int)$record->total;
        }

        $digital = $DB->get_records_sql(
            "
                SELECT status, COUNT(1) AS total
                  FROM {subscription_digital_payment_request}
                 WHERE {$digitalselect}
              GROUP BY status
            ",
            $digitalparams
        );

        foreach ($digital as $record) {
            $status = $this->normalise_status(
                $record->status ?? null
            );

            $result[$status] =
                ($result[$status] ?? 0)
                + (int)$record->total;
        }

        ksort($result);

        return $result;
    }

    /**
     * @return array{0:?int,1:?int}
     */
    private function load_purchase_dates(
        int $userid,
        string $digitalselect,
        array $digitalparams
    ): array {
        global $DB;

        $subscriptiondates = $DB->get_record_sql(
            "
                SELECT
                    MIN(creation_date) AS firstpurchase,
                    MAX(creation_date) AS lastpurchase
                  FROM {user_subscription}
                 WHERE userid = :userid
            ",
            [
                'userid' => $userid,
            ]
        );

        $digitaldates = $DB->get_record_sql(
            "
                SELECT
                    MIN(creation_date) AS firstpurchase,
                    MAX(creation_date) AS lastpurchase
                  FROM {subscription_digital_payment_request}
                 WHERE {$digitalselect}
            ",
            $digitalparams
        );

        $firstdates = array_filter(
            [
                (int)($subscriptiondates->firstpurchase ?? 0),
                (int)($digitaldates->firstpurchase ?? 0),
            ],
            static fn(int $value): bool =>
                $value > 0
        );

        $lastdates = array_filter(
            [
                (int)($subscriptiondates->lastpurchase ?? 0),
                (int)($digitaldates->lastpurchase ?? 0),
            ],
            static fn(int $value): bool =>
                $value > 0
        );

        return [
            $firstdates !== []
                ? min($firstdates)
                : null,

            $lastdates !== []
                ? max($lastdates)
                : null,
        ];
    }

    private function normalise_currency(
        mixed $currency
    ): string {
        $currency = strtoupper(
            trim(
                (string)$currency
            )
        );

        return preg_match(
            '/^[A-Z]{3}$/',
            $currency
        )
            ? $currency
            : 'EUR';
    }

    private function normalise_status(
        mixed $status
    ): string {
        $status = strtolower(
            trim(
                (string)$status
            )
        );

        return $status !== ''
            ? $status
            : 'unknown';
    }
}