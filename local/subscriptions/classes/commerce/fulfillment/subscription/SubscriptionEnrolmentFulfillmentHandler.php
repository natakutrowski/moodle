<?php

namespace local_subscriptions\commerce\fulfillment\subscription;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\fulfillment\CommerceFulfillmentContext;
use local_subscriptions\commerce\fulfillment\CommerceFulfillmentHandler;
use local_subscriptions\commerce\fulfillment\CommerceFulfillmentOperation;
use local_subscriptions\commerce\fulfillment\CommerceFulfillmentResult;

/**
 * Fulfills subscription purchases by creating access and Moodle enrolments.
 */
final class SubscriptionEnrolmentFulfillmentHandler
    implements CommerceFulfillmentHandler {

    public const KEY = 'subscription_enrolment';

    public function __construct(
        private readonly ?SubscriptionFulfillmentGateway $gateway = null
    ) {
    }

    public function get_key(): string {
        return self::KEY;
    }

    public function supports(
        CommerceFulfillmentOperation $operation
    ): bool {
        return $operation->get_key() === self::KEY;
    }

    public function fulfill(
        CommerceFulfillmentOperation $operation,
        CommerceFulfillmentContext $context
    ): CommerceFulfillmentResult {
        $gateway = $this->gateway
            ?? new LegacySubscriptionFulfillmentGateway();

        $existing = $gateway->find_by_transaction(
            $context->get_transaction_id()
        );

        if ($existing !== null) {
            return new CommerceFulfillmentResult(
                $operation,
                CommerceFulfillmentResult::STATUS_SKIPPED,
                'The subscription payment was already fulfilled.',
                [
                    'subscriptionid' => (int)$existing->id,
                    'idempotency_key' =>
                        $operation->get_idempotency_key(),
                ]
            );
        }

        $result = $gateway->fulfill(
            $operation,
            $context
        );

        $subscription = $result['subscription'] ?? null;

        return new CommerceFulfillmentResult(
            $operation,
            CommerceFulfillmentResult::STATUS_COMPLETED,
            'Subscription access was granted.',
            [
                'legacy_status' => $result['status'] ?? null,
                'subscriptionid' =>
                    $subscription instanceof \stdClass
                        ? (int)($subscription->id ?? 0)
                        : null,
                'userid' => $result['userid'] ?? null,
                'planid' => $result['planid'] ?? null,
                'start_date' => $result['start_date'] ?? null,
                'end_date' => $result['end_date'] ?? null,
                'idempotency_key' =>
                    $operation->get_idempotency_key(),
            ]
        );
    }
}
