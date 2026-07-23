<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\purchase\domain\CommercePurchaseBuilder;
use local_subscriptions\commerce\purchase\domain\CommercePurchaseMapper;
use local_subscriptions\commerce\purchase\domain\CommercePurchaseValidator;
use local_subscriptions\commerce\purchase\repository\CommercePurchaseRepository;
use local_subscriptions\commerce\purchase\shadow\CommercePurchaseShadowService;
use local_subscriptions\commerce\runtime\CommerceRuntimeFactory;

/**
 * Tests for Commerce purchase domain services exposed by the runtime.
 *
 * @covers \local_subscriptions\commerce\runtime\CommerceRuntime
 */
final class commerce_runtime_purchase_domain_test
    extends advanced_testcase {

    protected function tearDown(): void {
        CommerceRuntimeFactory::reset();

        parent::tearDown();
    }

    public function test_runtime_exposes_purchase_domain_services():
        void {
        $runtime =
            CommerceRuntimeFactory::create();

        $this->assertInstanceOf(
            CommercePurchaseBuilder::class,
            $runtime->purchase_builder()
        );

        $this->assertInstanceOf(
            CommercePurchaseMapper::class,
            $runtime->purchase_mapper()
        );

        $this->assertInstanceOf(
            CommercePurchaseValidator::class,
            $runtime->purchase_validator()
        );

        $this->assertInstanceOf(
            CommercePurchaseRepository::class,
            $runtime->purchase_domain_repository()
        );

        $this->assertInstanceOf(
            CommercePurchaseShadowService::class,
            $runtime->purchase_shadow()
        );
    }

    public function test_runtime_reuses_purchase_domain_services():
        void {
        $runtime =
            CommerceRuntimeFactory::create();

        $this->assertSame(
            $runtime->purchase_builder(),
            $runtime->purchase_builder()
        );

        $this->assertSame(
            $runtime->purchase_mapper(),
            $runtime->purchase_mapper()
        );

        $this->assertSame(
            $runtime->purchase_validator(),
            $runtime->purchase_validator()
        );

        $this->assertSame(
            $runtime->purchase_domain_repository(),
            $runtime->purchase_domain_repository()
        );

        $this->assertSame(
            $runtime->purchase_shadow(),
            $runtime->purchase_shadow()
        );
    }
}