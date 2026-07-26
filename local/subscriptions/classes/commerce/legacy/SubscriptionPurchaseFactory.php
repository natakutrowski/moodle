<?php

namespace local_subscriptions\commerce\legacy;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\domain\CommerceItem;
use local_subscriptions\commerce\domain\CommercePayment;
use local_subscriptions\commerce\domain\CommercePurchaseStatus;
use local_subscriptions\commerce\fulfillment\CommerceFulfillmentOperation;
use local_subscriptions\commerce\fulfillment\subscription\SubscriptionEnrolmentFulfillmentHandler;
use local_subscriptions\commerce\domain\purchase\SubscriptionPurchase;

/**
 * Builds the new Commerce representation from historical subscription data.
 *
 * This factory is read-only and does not modify legacy records.
 */
final class SubscriptionPurchaseFactory {

    /**
     * Creates a Commerce purchase from existing subscription records.
     *
     * @param \stdClass $subscription Record from user_subscription.
     * @param \stdClass|null $paymentrequest Record from subscription_payment_request.
     * @param \stdClass|null $plan Record from subscription_plan.
     * @param \stdClass|null $user Record from user.
     */
    public static function from_legacy_records(
        \stdClass $subscription,
        ?\stdClass $paymentrequest = null,
        ?\stdClass $plan = null,
        ?\stdClass $user = null
    ): SubscriptionPurchase {
        self::validate_subscription($subscription);

        $subscriptionid = (int)$subscription->id;
        $planid = (int)$subscription->planid;

        $currency = self::first_non_empty([
            $paymentrequest->currency ?? null,
            $subscription->currency ?? null,
        ], 'EUR');

        $amountminor = self::resolve_amount_minor(
            $paymentrequest,
            $subscription
        );

        $paymentstatus = LegacyCommerceStatusMapper::payment_status(
            $paymentrequest->status ?? self::subscription_payment_status($subscription)
        );

        $provider = self::nullable_string(
            $paymentrequest->payment_provider
                ?? $subscription->payment_provider
                ?? null
        );

        $transactionid = self::nullable_string(
            $paymentrequest->transactionid
                ?? $subscription->transactionid
                ?? null
        );

        $paymentrequestid = !empty($paymentrequest->id)
            ? (int)$paymentrequest->id
            : null;

        $paidat = self::nullable_positive_int(
            $paymentrequest->payment_date
                ?? $subscription->creation_date
                ?? null
        );

        $itemname = self::first_non_empty([
            $plan->name ?? null,
            $plan->code ?? null,
        ], 'Subscription plan #' . $planid);

        $item = new CommerceItem(
            CommerceItem::TYPE_SUBSCRIPTION,
            'subscription-plan:' . $planid,
            $itemname,
            $planid,
            [
                'legacy_table' => 'subscription_plan',
                'legacy_id' => $planid,
                'plan_id' => $planid,
                'access_scope_id' => self::nullable_positive_int(
                    $plan->accessscopeid
                        ?? $plan->access_scope_id
                        ?? $plan->scopeid
                        ?? null
                ),
            ]
        );

        $payment = new CommercePayment(
            $amountminor,
            strtoupper($currency),
            $paymentstatus,
            $provider,
            $transactionid,
            $paymentrequestid,
            $paidat,
            [
                'legacy_table' => $paymentrequest
                    ? 'subscription_payment_request'
                    : 'user_subscription',
                'operation' => self::nullable_string(
                    $paymentrequest->operation ?? null
                ),
                'session_id' => self::nullable_string(
                    $paymentrequest->sessionid ?? null
                ),
                'reference_subscription_id' => self::nullable_positive_int(
                    $paymentrequest->reference_subscription_id ?? null
                ),
            ]
        );

        $email = self::first_valid_email([
            $paymentrequest->email ?? null,
            $user->email ?? null,
        ]);

        return new SubscriptionPurchase(
            'subscription:' . $subscriptionid,
            $item,
            $payment,
            self::nullable_positive_int($subscription->userid ?? null),
            $email,
            self::resolve_purchase_status(
                $subscription,
                $payment
            ),
            $subscriptionid,
            $planid,
            self::nullable_positive_int($subscription->start_date ?? null),
            self::nullable_positive_int($subscription->end_date ?? null),
            self::nullable_positive_int($subscription->creation_date ?? null),
            self::nullable_positive_int($subscription->last_update ?? null),
            [
                'legacy_table' => 'user_subscription',
                'legacy_status' => self::nullable_string($subscription->status ?? null),
                'plan_id' => $planid,
                'start_date' => self::nullable_positive_int($subscription->start_date ?? null),
                'end_date' => self::nullable_positive_int($subscription->end_date ?? null),
                'is_trial' => !empty(
                    $plan->is_trial ?? false
                ),

                'is_complimentary' => !empty(
                    $subscription->is_complimentary
                        ?? false
                ),

                'list_price' => (float)(
                    $paymentrequest->locked_list_price
                        ?? $paymentrequest->price
                        ?? $subscription->pricepaid
                        ?? 0
                ),                
                'provider_subscription_id' => self::nullable_string(
                    $subscription->provider_subscription_id ?? null
                ),
                'provider_customer_id' => self::nullable_string(
                    $subscription->provider_customer_id ?? null
                ),
                'payment_failed' => !empty($subscription->payment_failed),
                'discount_percent' => (int)($subscription->discount_percent ?? 0),
                'discount_amount' => (float)($subscription->discount_amount ?? 0),
                'discount_reason' => self::nullable_string(
                    $subscription->discount_reason ?? null
                ),
            ],
            self::build_fulfillments(
                $subscription,
                $plan
            )
        );
    }

    /**
     * Resolve the Native lifecycle without confusing access replacement with
     * an incomplete payment or fulfillment.
     */
    private static function resolve_purchase_status(
        \stdClass $subscription,
        CommercePayment $payment
    ): string {
        $status = strtolower(trim((string)($subscription->status ?? '')));

        if (in_array($status, ['active', 'expired', 'replaced'], true)) {
            return CommercePurchaseStatus::FULFILLED;
        }

        if ($status === 'queued' && $payment->is_successful()) {
            return CommercePurchaseStatus::FULFILLMENT_PENDING;
        }

        return LegacyCommerceStatusMapper::purchase_status($status);
    }

    /**
     * Reconstruct one deterministic subscription access fulfillment from the
     * current Legacy truth. The operation records an observed outcome; it does
     * not execute enrollment again.
     *
     * @return CommerceFulfillmentOperation[]
     */
    private static function build_fulfillments(
        \stdClass $subscription,
        ?\stdClass $plan
    ): array {
        $status = strtolower(trim((string)($subscription->status ?? '')));

        if (!in_array($status, ['active', 'expired', 'replaced', 'queued'], true)) {
            return [];
        }

        $subscriptionid = (int)$subscription->id;
        $planid = (int)$subscription->planid;
        $completed = in_array($status, ['active', 'expired', 'replaced'], true);

        return [new CommerceFulfillmentOperation(
            'subscription:' . $subscriptionid . ':access',
            SubscriptionEnrolmentFulfillmentHandler::KEY,
            [
                'persistence_status' => $completed ? 'completed' : 'pending',
                'projection_source' => 'legacy_observation',
                'historical_fulfillment_inferred' => true,
                'legacy_subscription_id' => $subscriptionid,
                'legacy_status' => $status,
                'plan_id' => $planid,
                'access_scope_id' => self::nullable_positive_int(
                    $plan->accessscopeid
                        ?? $plan->access_scope_id
                        ?? $plan->scopeid
                        ?? null
                ),
            ]
        )];
    }

    private static function validate_subscription(\stdClass $subscription): void {
        if (empty($subscription->id)) {
            throw new \coding_exception('The legacy subscription record has no identifier.');
        }

        if (empty($subscription->planid)) {
            throw new \coding_exception('The legacy subscription record has no plan identifier.');
        }
    }

    private static function resolve_amount_minor(
        ?\stdClass $paymentrequest,
        \stdClass $subscription
    ): int {
        if (
            $paymentrequest !== null
            && isset($paymentrequest->amount_minor)
            && (int)$paymentrequest->amount_minor > 0
        ) {
            return (int)$paymentrequest->amount_minor;
        }

        $amount = $paymentrequest->locked_final_price
            ?? $paymentrequest->price
            ?? $subscription->pricepaid
            ?? 0;

        return max(0, (int)round(((float)$amount) * 100));
    }

    private static function subscription_payment_status(
        \stdClass $subscription
    ): string {
        if (!empty($subscription->payment_failed)) {
            return 'failed';
        }

        $status = strtolower((string)($subscription->status ?? ''));

        if (in_array($status, [
            'active',
            'expired',
            'replaced',
            'queued',
        ], true)) {
            return 'completed';
        }

        return $status;
    }

    /**
     * Returns the first non-empty valid historical email address.
     *
     * Deleted or anonymised Moodle users may retain an invalid value in the
     * email field. Such a value must not prevent the historical purchase from
     * being reconstructed when a stable Legacy user identifier is available.
     */
    private static function first_valid_email(
        array $values
    ): ?string {
        foreach ($values as $value) {
            $email = self::nullable_string($value);

            if ($email !== null && validate_email($email)) {
                return $email;
            }
        }

        return null;
    }

    private static function first_non_empty(
        array $values,
        string $default = ''
    ): string {
        foreach ($values as $value) {
            if ($value !== null && trim((string)$value) !== '') {
                return trim((string)$value);
            }
        }

        return $default;
    }

    private static function nullable_string(mixed $value): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim((string)$value);

        return $value === '' ? null : $value;
    }

    private static function nullable_positive_int(mixed $value): ?int {
        if ($value === null || $value === '') {
            return null;
        }

        $value = (int)$value;

        return $value > 0 ? $value : null;
    }
}