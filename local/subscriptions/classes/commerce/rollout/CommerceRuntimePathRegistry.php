<?php

declare(strict_types=1);
namespace local_subscriptions\commerce\rollout;
defined('MOODLE_INTERNAL') || die();
final class CommerceRuntimePathRegistry {
    public function all(): array {
        return [
            new CommerceRuntimePath('digital_checkout', 'Digital checkout persistence', 'digital', 'critical', ['classes/commerce/checkout/CommerceCheckoutPersistenceService.php']),
            new CommerceRuntimePath('digital_postpayment', 'Digital post-payment', 'digital', 'critical', ['classes/commerce/postpayment/DigitalPostPaymentProcessor.php']),
            new CommerceRuntimePath('subscription_postpayment', 'Subscription post-payment', 'subscription', 'critical', ['classes/commerce/postpayment/SubscriptionPostPaymentProcessor.php']),
            new CommerceRuntimePath('digital_service', 'Legacy digital payment service bridge', 'digital', 'high', ['classes/digital/digital_payment_service.php']),
            new CommerceRuntimePath('subscription_service', 'Subscription payment service bridge', 'subscription', 'high', ['classes/domain/PaymentService.php']),
            new CommerceRuntimePath('fulfillment_email', 'Digital fulfillment email action', 'digital', 'medium', ['classes/commerce/fulfillment/postaction/DigitalEmailPostFulfillmentAction.php']),
            new CommerceRuntimePath('repair_job', 'Paid payment-request repair job', 'both', 'high', ['classes/commerce/task/job/PaidPaymentRequestRepairJob.php']),
        ];
    }
}
