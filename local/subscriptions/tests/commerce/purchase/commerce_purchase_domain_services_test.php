<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\domain\CommerceItem;
use local_subscriptions\commerce\domain\CommercePayment;
use local_subscriptions\commerce\domain\CommercePurchaseIdentity;
use local_subscriptions\commerce\domain\CommercePurchaseStatus;
use local_subscriptions\commerce\domain\purchase\DigitalPurchase;
use local_subscriptions\commerce\domain\purchase\SubscriptionPurchase;
use local_subscriptions\commerce\purchase\domain\CommercePurchaseBuilder;
use local_subscriptions\commerce\purchase\domain\CommercePurchaseMapper;
use local_subscriptions\commerce\purchase\domain\CommercePurchaseValidator;
use local_subscriptions\commerce\purchase\shadow\CommercePurchaseShadowService;

/**
 * Tests for the Commerce purchase domain services.
 *
 * @covers \local_subscriptions\commerce\domain\CommercePurchaseIdentity
 * @covers \local_subscriptions\commerce\domain\CommercePurchaseStatus
 * @covers \local_subscriptions\commerce\domain\CommercePurchaseFinancialData
 * @covers \local_subscriptions\commerce\domain\CommercePurchaseCustomer
 * @covers \local_subscriptions\commerce\purchase\domain\CommercePurchaseBuilder
 * @covers \local_subscriptions\commerce\purchase\domain\CommercePurchaseMapper
 * @covers \local_subscriptions\commerce\purchase\domain\CommercePurchaseValidator
 * @covers \local_subscriptions\commerce\purchase\shadow\CommercePurchaseShadowService
 */
final class commerce_purchase_domain_services_test
    extends advanced_testcase {

    public function test_builder_creates_subscription_purchase(): void {
        $item = new CommerceItem(
            CommerceItem::TYPE_SUBSCRIPTION,
            'subscription-plan:14',
            'Annual plan',
            14
        );

        $payment = new CommercePayment(
            12000,
            'EUR',
            CommercePayment::STATUS_PAID,
            'stripe',
            'pi_test_123',
            91,
            1700000000
        );

        $purchase =
            (new CommercePurchaseBuilder())
                ->type('subscription')
                ->reference('subscription:41')
                ->item($item)
                ->payment($payment)
                ->customer(
                    96,
                    'student@example.com'
                )
                ->status('active')
                ->legacy_id(41)
                ->catalog_id(14)
                ->subscription_period(
                    1700000000,
                    1731536000
                )
                ->created_at(1700000000)
                ->updated_at(1700000100)
                ->build();

        $this->assertInstanceOf(
            SubscriptionPurchase::class,
            $purchase
        );

        $this->assertSame(
            41,
            $purchase->get_legacy_subscription_id()
        );

        $this->assertSame(
            14,
            $purchase->get_plan_id()
        );
    }

    public function test_builder_creates_digital_purchase(): void {
        $item = new CommerceItem(
            CommerceItem::TYPE_DIGITAL,
            'digital-product:7',
            'French verb guide',
            7
        );

        $payment = new CommercePayment(
            2900,
            'EUR',
            CommercePayment::STATUS_COMPLETED,
            'stripe'
        );

        $purchase =
            (new CommercePurchaseBuilder())
                ->type('digital')
                ->reference('digital:84')
                ->item($item)
                ->payment($payment)
                ->customer(
                    null,
                    'guest@example.com'
                )
                ->status('completed')
                ->legacy_id(84)
                ->catalog_id(7)
                ->download_access(
                    'download-token',
                    1800000000
                )
                ->build();

        $this->assertInstanceOf(
            DigitalPurchase::class,
            $purchase
        );

        $this->assertSame(
            84,
            $purchase->get_legacy_purchase_id()
        );

        $this->assertSame(
            7,
            $purchase->get_product_id()
        );
    }

    public function test_identity_is_deterministic(): void {
        $first =
            CommercePurchaseIdentity::for_subscription(
                41
            );

        $second =
            CommercePurchaseIdentity::for_subscription(
                41
            );

        $this->assertSame(
            'subscription:41',
            $first->get_key()
        );

        $this->assertSame(
            $first->get_public_reference(),
            $second->get_public_reference()
        );
    }

    public function test_status_normalises_legacy_values(): void {
        $this->assertSame(
            CommercePurchaseStatus::FULFILLED,
            CommercePurchaseStatus::normalise(
                'active'
            )
        );

        $this->assertSame(
            CommercePurchaseStatus::PAYMENT_PENDING,
            CommercePurchaseStatus::normalise(
                'queued'
            )
        );

        $this->assertSame(
            CommercePurchaseStatus::FULFILLED,
            CommercePurchaseStatus::normalise(
                'expired'
            )
        );
    }

    public function test_validator_accepts_consistent_purchase(): void {
        $purchase = $this->create_subscription_purchase();

        $result =
            (new CommercePurchaseValidator())
                ->validate(
                    $purchase
                );

        $this->assertTrue(
            $result->is_valid(),
            json_encode(
                $result->to_array()
            )
        );
    }

    public function test_mapper_builds_common_snapshot(): void {
        $purchase = $this->create_subscription_purchase();

        $snapshot =
            (new CommercePurchaseMapper())
                ->to_array(
                    $purchase
                );

        $this->assertSame(
            'subscription:41',
            $snapshot['identity']['key']
        );

        $this->assertSame(
            'subscription',
            $snapshot['type']
        );

        $this->assertSame(
            'fulfilled',
            $snapshot['status']
        );

        $this->assertSame(
            12000,
            $snapshot['financial']['total_minor']
        );

        $this->assertSame(
            14,
            $snapshot['access']['plan_id']
        );
    }

    public function test_shadow_service_reports_compatible_purchase():
        void {
        $service =
            new CommercePurchaseShadowService(
                new CommercePurchaseValidator(),
                new CommercePurchaseMapper()
            );

        $report =
            $service->evaluate(
                $this->create_subscription_purchase()
            );

        $this->assertTrue(
            $report->is_compatible(),
            json_encode(
                $report->to_array()
            )
        );

        $this->assertSame(
            'subscription:41',
            $report->get_purchase_key()
        );

        $this->assertNotEmpty(
            $report->get_snapshot()
        );
    }

    /**
     * Create a valid subscription purchase.
     *
     * @return SubscriptionPurchase
     */
    private function create_subscription_purchase():
        SubscriptionPurchase {
        return new SubscriptionPurchase(
            'subscription:41',
            new CommerceItem(
                CommerceItem::TYPE_SUBSCRIPTION,
                'subscription-plan:14',
                'Annual plan',
                14
            ),
            new CommercePayment(
                12000,
                'EUR',
                CommercePayment::STATUS_PAID,
                'stripe',
                'pi_test_123',
                91,
                1700000000
            ),
            96,
            'student@example.com',
            'active',
            41,
            14,
            1700000000,
            1731536000,
            1700000000,
            1700000100,
            [
                'firstname' => 'Student',
                'lastname' => 'Example',
            ]
        );
    }
}