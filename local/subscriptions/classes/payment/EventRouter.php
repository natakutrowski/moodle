<?php
namespace local_subscriptions\payment;

use local_subscriptions\payment\dto\InternalEvent;

final class EventRouter {
    public static function handle(InternalEvent $e): void {
        switch ($e->type) {
            case 'checkout_completed':
                \local_subscriptions\domain\PaymentService::on_checkout_completed($e);
                break;
            case 'checkout_expired':
                \local_subscriptions\domain\PaymentService::on_checkout_expired($e);
                break;
            case 'payment_failed':
                \local_subscriptions\domain\PaymentService::on_payment_failed($e);
                break;  
            case 'invoice_paid':
                \local_subscriptions\domain\SubscriptionService::on_invoice_paid($e);
                break;
            case 'invoice_failed':
                \local_subscriptions\domain\SubscriptionService::on_invoice_failed($e);
                break;
            case 'subscription_canceled':
                \local_subscriptions\domain\SubscriptionService::on_subscription_canceled($e);
                break;
            case 'subscription_updated':
                \local_subscriptions\domain\SubscriptionService::on_subscription_updated($e);
                break;
            default:
                \core\notification::add("Unknown payment event: {$e->type}", \core\output\notification::NOTIFY_INFO);
        }
    }
}
