<?php

namespace local_subscriptions\commerce\runtime;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\CommercePurchaseService;
use local_subscriptions\commerce\domain\CommercePurchaseFinancialClassifier;
use local_subscriptions\commerce\legacy\repository\DigitalPurchaseRepository;
use local_subscriptions\commerce\legacy\repository\SubscriptionPurchaseRepository;
use local_subscriptions\commerce\purchase\handler\CommercePurchaseHandlerRegistry;
use local_subscriptions\commerce\purchase\subscription\SubscriptionPurchaseHandler;
use local_subscriptions\commerce\purchase\digital\DigitalPurchaseHandler;
use local_subscriptions\commerce\purchase\preparation\CommercePurchasePreparationOrchestrator;
use local_subscriptions\commerce\fulfillment\CommerceFulfillmentCoordinator;
use local_subscriptions\commerce\fulfillment\CommerceFulfillmentHandlerRegistry;
use local_subscriptions\commerce\fulfillment\subscription\SubscriptionEnrolmentFulfillmentHandler;
use local_subscriptions\commerce\fulfillment\digital\DigitalDownloadFulfillmentHandler;
use local_subscriptions\commerce\payment\CommercePaymentRequestFactory;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderRegistryFactory;
use local_subscriptions\commerce\payment\legacy\LegacyCommercePaymentRequestFactory;
use local_subscriptions\commerce\payment\orchestration\CommercePaymentOrchestrator;
use local_subscriptions\commerce\payment\orchestration\CommercePaymentProviderContextFactory;

/**
 * Central construction point for Commerce services.
 *
 * No caller should need to know which legacy repositories currently back
 * the Commerce domain.
 */
final class CommerceRuntimeFactory {

    private static ?CommerceRuntime $runtime = null;

    public static function create(): CommerceRuntime {
        if (self::$runtime !== null) {
            return self::$runtime;
        }

        $subscriptionrepository =
            new SubscriptionPurchaseRepository();

        $digitalrepository =
            new DigitalPurchaseRepository();

        $purchaseservice =
            new CommercePurchaseService(
                $subscriptionrepository,
                $digitalrepository
            );

        $purchasehandlerregistry =
            new CommercePurchaseHandlerRegistry([
                new SubscriptionPurchaseHandler(),
                new DigitalPurchaseHandler(),
            ]);        

        $purchasepreparationorchestrator =
            new CommercePurchasePreparationOrchestrator(
                $purchasehandlerregistry
            );

        $fulfillmenthandlerregistry =
            new CommerceFulfillmentHandlerRegistry([
                new SubscriptionEnrolmentFulfillmentHandler(),
                new DigitalDownloadFulfillmentHandler(),
            ]);

        $fulfillmentcoordinator =
            new CommerceFulfillmentCoordinator(
                $fulfillmenthandlerregistry
            );
            
        $paymentproviderregistry =
            CommercePaymentProviderRegistryFactory::create();

        $paymentorchestrator =
            new CommercePaymentOrchestrator(
                $paymentproviderregistry
            );

        $paymentcontextfactory =
            new CommercePaymentProviderContextFactory();

        $legacypaymentrequestfactory =
            new LegacyCommercePaymentRequestFactory();

        self::$runtime =
            new CommerceRuntime(
                $purchaseservice,
                new CommercePurchaseFinancialClassifier(),
                $purchasehandlerregistry,
                $purchasepreparationorchestrator,
                $fulfillmenthandlerregistry,
                $fulfillmentcoordinator,
                new CommercePaymentRequestFactory(),
                $paymentproviderregistry,
                $paymentorchestrator,
                $paymentcontextfactory,
                $legacypaymentrequestfactory
            );

        return self::$runtime;
    }

    /**
     * Replaces the runtime for tests or controlled integrations.
     */
    public static function set_for_testing(
        CommerceRuntime $runtime
    ): void {
        if (!defined('PHPUNIT_TEST') || !PHPUNIT_TEST) {
            throw new \coding_exception(
                'The Commerce runtime can only be replaced during PHPUnit tests.'
            );
        }

        self::$runtime = $runtime;
    }

    /**
     * Clears the request-local runtime.
     */
    public static function reset(): void {
        self::$runtime = null;
    }
}