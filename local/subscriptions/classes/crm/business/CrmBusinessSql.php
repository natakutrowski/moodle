<?php

namespace local_subscriptions\crm\business;

defined('MOODLE_INTERNAL') || die();

/**
 * Shared SQL business rules for CRM metrics.
 *
 * All aliases supplied to this class must be trusted aliases defined directly
 * by internal repository code.
 */
final class CrmBusinessSql {

    /**
     * SQL condition identifying a trial subscription.
     *
     * @param string $subscriptionalias Alias for user_subscription.
     * @param string $planalias Alias for subscription_plan.
     */
    public static function trial_subscription_condition(
        string $subscriptionalias = 'us',
        string $planalias = 'sp'
    ): string {
        return "(
            COALESCE({$planalias}.is_trial, 0) = 1
            OR (
                {$planalias}.id IS NULL
                AND LOWER(
                    COALESCE({$subscriptionalias}.payment_provider, '')
                ) = 'trial'
            )
        )";
    }

    /**
     * SQL condition identifying a non-trial subscription.
     */
    public static function non_trial_subscription_condition(
        string $subscriptionalias = 'us',
        string $planalias = 'sp'
    ): string {
        return 'NOT ' . self::trial_subscription_condition(
            $subscriptionalias,
            $planalias
        );
    }

    /**
     * SQL condition identifying a successful subscription payment request.
     */
    public static function successful_subscription_payment_condition(
        string $paymentalias = 'pr'
    ): string {
        return "(
            LOWER(COALESCE({$paymentalias}.status, ''))
                IN ('paid', 'completed')
            AND (
                CASE
                    WHEN COALESCE({$paymentalias}.locked_final_price, 0) > 0
                        THEN {$paymentalias}.locked_final_price
                    WHEN COALESCE({$paymentalias}.amount_minor, 0) > 0
                        THEN {$paymentalias}.amount_minor
                    ELSE COALESCE({$paymentalias}.price, 0)
                END
            ) > 0
        )";
    }

    /**
     * SQL condition identifying a successful digital payment request.
     */
    public static function successful_digital_payment_condition(
        string $paymentalias = 'dpr'
    ): string {
        return "(
            LOWER(COALESCE({$paymentalias}.status, ''))
                IN ('paid', 'completed')
            AND (
                CASE
                    WHEN COALESCE({$paymentalias}.locked_final_price, 0) > 0
                        THEN {$paymentalias}.locked_final_price
                    WHEN COALESCE({$paymentalias}.amount_minor, 0) > 0
                        THEN {$paymentalias}.amount_minor
                    ELSE COALESCE({$paymentalias}.price, 0)
                END
            ) > 0
        )";
    }

    /**
     * Historical payment evidence stored directly on user_subscription.
     */
    public static function legacy_subscription_payment_condition(
        string $subscriptionalias = 'us'
    ): string {
        return "(
            COALESCE({$subscriptionalias}.pricepaid, 0) > 0
            AND LOWER(
                COALESCE({$subscriptionalias}.payment_provider, '')
            ) <> 'trial'
        )";
    }

    /**
     * Final paid-subscription condition.
     *
     * Requires the repository query to provide:
     * - user_subscription alias;
     * - subscription_plan alias;
     * - the EXISTS query against subscription_payment_request.
     */
    public static function paid_subscription_condition(
        string $subscriptionalias = 'us',
        string $planalias = 'sp'
    ): string {
        $nontrial = self::non_trial_subscription_condition(
            $subscriptionalias,
            $planalias
        );

        $successfulpayment = self::successful_subscription_payment_condition(
            'pr'
        );

        $legacy = self::legacy_subscription_payment_condition(
            $subscriptionalias
        );

        return "(
            {$nontrial}
            AND (
                EXISTS (
                    SELECT 1
                      FROM {subscription_payment_request} pr
                     WHERE pr.subscriptionid = {$subscriptionalias}.id
                       AND {$successfulpayment}
                )
                OR {$legacy}
            )
        )";
    }

    /**
     * Canonical amount for a subscription payment request.
     */
    public static function subscription_payment_amount_expression(
        string $paymentalias = 'pr'
    ): string {
        return "(
            CASE
                WHEN COALESCE(
                    {$paymentalias}.locked_final_price,
                    0
                ) > 0
                    THEN {$paymentalias}.locked_final_price

                WHEN COALESCE(
                    {$paymentalias}.price,
                    0
                ) > 0
                    THEN {$paymentalias}.price

                ELSE COALESCE(
                    {$paymentalias}.amount_minor,
                    0
                ) / 100.0
            END
        )";
    }

    /**
     * Canonical amount for a digital payment request.
     */
    public static function digital_payment_amount_expression(
        string $paymentalias = 'dpr'
    ): string {
        return "(
            CASE
                WHEN COALESCE(
                    {$paymentalias}.locked_final_price,
                    0
                ) > 0
                    THEN {$paymentalias}.locked_final_price

                WHEN COALESCE(
                    {$paymentalias}.price,
                    0
                ) > 0
                    THEN {$paymentalias}.price

                ELSE COALESCE(
                    {$paymentalias}.amount_minor,
                    0
                ) / 100.0
            END
        )";
    }

    /**
     * Canonical payment date for subscription payment requests.
     */
    public static function subscription_payment_date_expression(
        string $paymentalias = 'pr'
    ): string {
        return "COALESCE(
            NULLIF({$paymentalias}.payment_date, 0),
            {$paymentalias}.creation_date
        )";
    }

    /**
     * Canonical payment date for digital payment requests.
     */
    public static function digital_payment_date_expression(
        string $paymentalias = 'dpr'
    ): string {
        return "COALESCE(
            NULLIF({$paymentalias}.payment_date, 0),
            {$paymentalias}.creation_date
        )";
    }

    /**
     * Normalize a currency directly in SQL.
     */
    public static function currency_expression(
        string $field
    ): string {
        return "UPPER(TRIM(COALESCE({$field}, '')))";
    }
}