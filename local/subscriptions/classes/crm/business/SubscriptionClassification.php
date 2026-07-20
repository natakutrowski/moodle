<?php

namespace local_subscriptions\crm\business;

defined('MOODLE_INTERNAL') || die();

/**
 * Canonical business classifications for CRM subscriptions.
 */
final class SubscriptionClassification {

    public const TRIAL = 'trial';
    public const PAID = 'paid';
    public const FREE = 'free';
    public const UNCONFIRMED = 'unconfirmed';

    /**
     * Normalize a subscription status or provider value.
     */
    public static function normalize(?string $value): string {
        return strtolower(trim((string)$value));
    }

    /**
     * Whether a subscription record is clearly identified as a trial.
     *
     * This method is intended for already-loaded records. SQL repositories
     * should apply the equivalent rule using the plan's is_trial field.
     */
    public static function is_trial_record(\stdClass $subscription): bool {
        $hasplaninformation = property_exists(
            $subscription,
            'plan_is_trial'
        ) || property_exists(
            $subscription,
            'is_trial'
        );

        if ($hasplaninformation) {
            return !empty($subscription->plan_is_trial)
                || !empty($subscription->is_trial);
        }

        return self::normalize(
            $subscription->payment_provider ?? null
        ) === self::TRIAL;
    }

    /**
     * Whether a historical subscription record contains a local payment proof.
     *
     * This is a fallback for legacy, manually-created or imported records
     * without a matching subscription_payment_request.
     */
    public static function has_legacy_payment_evidence(
        \stdClass $subscription
    ): bool {
        if (self::is_trial_record($subscription)) {
            return false;
        }

        $pricepaid = (float)($subscription->pricepaid ?? 0);

        if ($pricepaid <= 0) {
            return false;
        }

        $provider = self::normalize(
            $subscription->payment_provider ?? null
        );

        return $provider !== self::TRIAL;
    }

    /**
     * Classify an already-loaded subscription record.
     *
     * @param \stdClass $subscription Subscription and optional plan fields.
     * @param bool $hassuccessfulpaymentrequest Whether a confirmed payment
     *        request exists for this subscription.
     */
    public static function classify(
        \stdClass $subscription,
        bool $hassuccessfulpaymentrequest = false
    ): string {
        if (self::is_trial_record($subscription)) {
            return self::TRIAL;
        }

        if (
            $hassuccessfulpaymentrequest
            || self::has_legacy_payment_evidence($subscription)
        ) {
            return self::PAID;
        }

        $pricepaid = (float)($subscription->pricepaid ?? 0);

        if ($pricepaid <= 0) {
            return self::FREE;
        }

        return self::UNCONFIRMED;
    }
}