<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\CommercePurchaseService;
use local_subscriptions\commerce\domain\CommercePurchaseFinancialClassifier;
use local_subscriptions\commerce\runtime\CommerceRuntime;
use local_subscriptions\commerce\runtime\CommerceRuntimeFactory;
use local_subscriptions\commerce\purchase\handler\CommercePurchaseHandlerRegistry;
use local_subscriptions\commerce\purchase\preparation\CommercePurchasePreparationOrchestrator;
use local_subscriptions\commerce\fulfillment\CommerceFulfillmentCoordinator;
use local_subscriptions\commerce\fulfillment\CommerceFulfillmentHandlerRegistry;
use local_subscriptions\commerce\payment\CommercePaymentRequestFactory;
use local_subscriptions\commerce\payment\provider\CommercePaymentProviderRegistry;
use local_subscriptions\commerce\payment\provider\alfa\AlfaCommercePaymentProvider;
use local_subscriptions\commerce\payment\provider\stripe\StripeCommercePaymentProvider;
use local_subscriptions\commerce\payment\legacy\LegacyCommercePaymentRequestFactory;
use local_subscriptions\commerce\payment\orchestration\CommercePaymentOrchestrator;
use local_subscriptions\commerce\payment\orchestration\CommercePaymentProviderContextFactory;

/**
 * Tests for the Commerce runtime.
 *
 * @covers \local_subscriptions\commerce\runtime\CommerceRuntime
 * @covers \local_subscriptions\commerce\runtime\CommerceRuntimeFactory
 */
final class commerce_runtime_test extends advanced_testcase {

    protected function tearDown(): void {
        CommerceRuntimeFactory::reset();

        parent::tearDown();
    }

    public function test_factory_returns_same_runtime_per_request(): void {
        $first = CommerceRuntimeFactory::create();
        $second = CommerceRuntimeFactory::create();

        $this->assertSame(
            $first,
            $second
        );

        $this->assertSame(
            $first->purchases(),
            $second->purchases()
        );
    }

    public function test_runtime_can_be_replaced_during_tests(): void {
        $purchaseservice = $this->getMockBuilder(
            CommercePurchaseService::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        $registry =
            new CommercePurchaseHandlerRegistry();

        $fulfillmentregistry =
            new CommerceFulfillmentHandlerRegistry();

        $paymentproviderregistry =
            new CommercePaymentProviderRegistry();

        $paymentorchestrator =
            new CommercePaymentOrchestrator(
                $paymentproviderregistry
            );

        $paymentcontextfactory =
            new CommercePaymentProviderContextFactory();

        $legacypaymentrequestfactory =
            new LegacyCommercePaymentRequestFactory();

        $runtime = new CommerceRuntime(
            $purchaseservice,
            new CommercePurchaseFinancialClassifier(),
            $registry,
            new CommercePurchasePreparationOrchestrator(
                $registry
            ),
            $fulfillmentregistry,
            new CommerceFulfillmentCoordinator(
                $fulfillmentregistry
            ),
            new CommercePaymentRequestFactory(),
            $paymentproviderregistry,
            $paymentorchestrator,
            $paymentcontextfactory,
            $legacypaymentrequestfactory
        );

        CommerceRuntimeFactory::set_for_testing(
            $runtime
        );

        $this->assertSame(
            $runtime,
            CommerceRuntimeFactory::create()
        );

        $this->assertSame(
            $purchaseservice,
            CommerceRuntimeFactory::create()
                ->purchases()
        );
    }

    public function test_default_runtime_registers_subscription_handler():
        void {
        $runtime =
            CommerceRuntimeFactory::create();

        $this->assertTrue(
            $runtime
                ->purchase_handlers()
                ->has('subscription')
        );
    }

    public function test_default_runtime_registers_digital_handler():
        void {
        $runtime =
            CommerceRuntimeFactory::create();

        $this->assertTrue(
            $runtime
                ->purchase_handlers()
                ->has('digital')
        );
    }

    public function test_runtime_exposes_purchase_preparation():
        void {
        $runtime =
            CommerceRuntimeFactory::create();

        $this->assertInstanceOf(
            CommercePurchasePreparationOrchestrator::class,
            $runtime->purchase_preparation()
        );
    }

    public function test_runtime_exposes_payment_request_factory():
        void {
        $runtime =
            CommerceRuntimeFactory::create();

        $this->assertInstanceOf(
            CommercePaymentRequestFactory::class,
            $runtime->payment_requests()
        );
    }

    public function test_runtime_exposes_payment_provider_registry():
        void {
        $this->resetAfterTest();

        $runtime =
            CommerceRuntimeFactory::create();

        $registry =
            $runtime->payment_providers();

        $this->assertInstanceOf(
            CommercePaymentProviderRegistry::class,
            $registry
        );

        $this->assertSame(
            [
                StripeCommercePaymentProvider::KEY,
                AlfaCommercePaymentProvider::KEY,
            ],
            $registry->keys()
        );

        $this->assertInstanceOf(
            StripeCommercePaymentProvider::class,
            $registry->get(
                StripeCommercePaymentProvider::KEY
            )
        );

        $this->assertInstanceOf(
            AlfaCommercePaymentProvider::class,
            $registry->get(
                AlfaCommercePaymentProvider::KEY
            )
        );
    }

    public function test_runtime_exposes_payment_orchestration_services():
        void {
        $this->resetAfterTest();

        $runtime =
            CommerceRuntimeFactory::create();

        $this->assertInstanceOf(
            CommercePaymentOrchestrator::class,
            $runtime->payment_orchestration()
        );

        $this->assertInstanceOf(
            CommercePaymentProviderContextFactory::class,
            $runtime->payment_contexts()
        );

        $this->assertInstanceOf(
            LegacyCommercePaymentRequestFactory::class,
            $runtime->legacy_payment_requests()
        );
    }


    public function test_default_runtime_registers_fulfillment_handlers(): void {
        $runtime = CommerceRuntimeFactory::create();

        $this->assertTrue(
            $runtime->fulfillment_handlers()->has('subscription_enrolment')
        );
        $this->assertTrue(
            $runtime->fulfillment_handlers()->has('digital_download')
        );
    }

}