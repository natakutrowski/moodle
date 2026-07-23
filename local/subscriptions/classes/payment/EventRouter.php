<?php

namespace local_subscriptions\payment;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\postpayment\DigitalPostPaymentProcessor;
use local_subscriptions\commerce\postpayment\SubscriptionPostPaymentProcessor;
use local_subscriptions\digital\digital_payment_service;
use local_subscriptions\domain\PaymentService;
use local_subscriptions\domain\SubscriptionService;
use local_subscriptions\payment\dto\InternalEvent;

/**
 * Routes normalized provider events to Commerce or Legacy business services.
 */
final class EventRouter {

    public static function handle(InternalEvent $event): void {
        if (self::is_digital_event($event)) {
            self::handle_digital($event);
            return;
        }

        self::handle_subscription($event);
    }

    private static function handle_digital(
        InternalEvent $event
    ): void {
        switch ($event->type) {
            case 'checkout_completed':
                $result = (new DigitalPostPaymentProcessor())
                    ->process($event);

                if ($result->requires_legacy()) {
                    digital_payment_service::on_checkout_completed(
                        $event
                    );
                }
                return;

            case 'payment_failed':
            case 'checkout_expired':
                digital_payment_service::on_payment_failed($event);
                return;

            default:
                self::notify_unknown_event($event);
                return;
        }
    }

    private static function handle_subscription(
        InternalEvent $event
    ): void {
        switch ($event->type) {
            case 'checkout_completed':
                $result = (new SubscriptionPostPaymentProcessor())
                    ->process($event);

                if ($result->requires_legacy()) {
                    PaymentService::on_checkout_completed($event);
                }
                return;

            case 'checkout_expired':
                PaymentService::on_checkout_expired($event);
                return;

            case 'payment_failed':
                PaymentService::on_payment_failed($event);
                return;

            case 'invoice_paid':
                SubscriptionService::on_invoice_paid($event);
                return;

            case 'invoice_failed':
                SubscriptionService::on_invoice_failed($event);
                return;

            case 'subscription_canceled':
                SubscriptionService::on_subscription_canceled(
                    $event
                );
                return;

            case 'subscription_updated':
                SubscriptionService::on_subscription_updated(
                    $event
                );
                return;

            default:
                self::notify_unknown_event($event);
                return;
        }
    }

    private static function is_digital_event(
        InternalEvent $event
    ): bool {
        return strtolower((string)(
            $event->meta['payment_context'] ?? ''
        )) === 'digital_product';
    }

    private static function notify_unknown_event(
        InternalEvent $event
    ): void {
        \core\notification::add(
            get_string(
                'unknown_payment_event',
                'local_subscriptions',
                $event->type
            ),
            \core\output\notification::NOTIFY_INFO
        );
    }
}