<?php

namespace local_subscriptions\payment;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\postpayment\DigitalPostPaymentProcessor;
use local_subscriptions\commerce\postpayment\SubscriptionPostPaymentProcessor;
use local_subscriptions\commerce\payment\repository\CommercePaymentRepository;
use local_subscriptions\commerce\payment\returnflow\CommercePaymentEventSynchronizer;
use local_subscriptions\commerce\fulfillment\native\checkout\CommerceNativePaidPurchaseCompleter;
use local_subscriptions\commerce\checkout\guest\CommerceGuestAccountActivator;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutSessionRepository;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutLifecycleService;
use local_subscriptions\commerce\persistence\CommercePersistenceSchema;
use local_subscriptions\commerce\runtime\switching\CommerceRuntimeDispatcher;
use local_subscriptions\digital\digital_payment_service;
use local_subscriptions\domain\PaymentService;
use local_subscriptions\domain\SubscriptionService;
use local_subscriptions\payment\dto\InternalEvent;

/**
 * Routes normalized provider events to Commerce or Legacy business services.
 */
final class EventRouter {

    public static function handle(InternalEvent $event): void {
        global $DB;

        $synchronized = (new CommercePaymentEventSynchronizer(
            new CommercePaymentRepository($DB)
        ))->synchronize($event);

        if (self::requires_native_payment_sync($event) && !$synchronized) {
            throw new \RuntimeException(
                'A Commerce provider event could not be matched to a Native payment attempt.'
            );
        }

        if ($event->type === 'checkout_completed' && $synchronized) {
            (new CommerceNativePaidPurchaseCompleter(
                $DB,
                new CommercePaymentRepository($DB)
            ))->complete($event);
            self::activate_guest_account($event);
            return;
        }

        if ($synchronized && in_array($event->type, ['payment_failed', 'checkout_expired'], true)) {
            self::update_guest_checkout_failure($event);
        }

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
                (new CommerceRuntimeDispatcher())->checkout_completed(
                    $event,
                    'event_router.digital',
                    static function () use ($event): void {
                        $result = (new DigitalPostPaymentProcessor())->process($event);
                        if ($result->requires_legacy()) {
                            digital_payment_service::on_checkout_completed($event);
                        }
                    }
                );
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
                (new CommerceRuntimeDispatcher())->checkout_completed(
                    $event,
                    'event_router.subscription',
                    static function () use ($event): void {
                        PaymentService::on_checkout_completed($event);
                    },
                    static function () use ($event): void {
                        $result = (new SubscriptionPostPaymentProcessor())->process($event);
                        if ($result->requires_legacy()) {
                            throw new \RuntimeException(
                                'Commerce Subscription post-payment processing requested Legacy fallback.'
                            );
                        }
                    }
                );
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



    private static function update_guest_checkout_failure(InternalEvent $event): void {
        global $DB;

        $reference = self::resolve_purchase_reference($event);
        if ($reference === null) {
            return;
        }

        $sessions = new CommerceGuestCheckoutSessionRepository($DB);
        $lifecycle = new CommerceGuestCheckoutLifecycleService($sessions);
        if ($event->type === 'checkout_expired') {
            $lifecycle->mark_checkout_expired($reference);
            return;
        }
        $lifecycle->mark_payment_failed($reference, $event->type);
    }

    private static function resolve_purchase_reference(InternalEvent $event): ?string {
        global $DB;

        $directreference = trim((string) ($event->meta['commerce_reference'] ?? ''));
        if ($directreference !== '') {
            return $directreference;
        }

        $purchaseuuid = trim((string) ($event->meta['commerce_purchase_uuid'] ?? ''));
        if ($purchaseuuid === '') {
            return null;
        }
        $reference = $DB->get_field(
            CommercePersistenceSchema::TABLE_PURCHASE,
            'reference',
            ['purchaseuuid' => $purchaseuuid],
            IGNORE_MISSING
        );
        if ($reference === false || trim((string) $reference) === '') {
            return null;
        }
        return trim((string) $reference);
    }

    private static function activate_guest_account(InternalEvent $event): void {
        global $DB;

        $purchaseuuid = trim((string) ($event->meta['commerce_purchase_uuid'] ?? ''));
        if ($purchaseuuid === '') {
            return;
        }
        $reference = $DB->get_field(
            CommercePersistenceSchema::TABLE_PURCHASE,
            'reference',
            ['purchaseuuid' => $purchaseuuid],
            IGNORE_MISSING
        );
        if ($reference === false || trim((string) $reference) === '') {
            return;
        }
        $sessions = new CommerceGuestCheckoutSessionRepository($DB);
        (new CommerceGuestAccountActivator($DB, $sessions))->activate_for_purchase((string) $reference);
    }

    private static function requires_native_payment_sync(
        InternalEvent $event
    ): bool {
        if (!in_array($event->type, [
            'checkout_completed',
            'checkout_expired',
            'payment_failed',
            'invoice_paid',
            'invoice_failed',
        ], true)) {
            return false;
        }

        return trim((string)(
            $event->meta['commerce_payment_id']
                ?? $event->meta['commerce_purchase_uuid']
                ?? $event->meta['commerce_reference']
                ?? ''
        )) !== '';
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