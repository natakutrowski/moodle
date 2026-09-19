<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductStatus;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\education\capacity\CommercePedagogicalCapacityPresenter;
use local_subscriptions\commerce\education\capacity\CommercePedagogicalCapacityService;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupConfiguration;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupOrchestrator;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupRepository;
use local_subscriptions\commerce\education\lifecycle\CommercePedagogicalSaleException;
use local_subscriptions\commerce\education\lifecycle\CommercePedagogicalSalePolicy;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotion;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionOfferRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationService;

final class commerce_797m110b_direct_purchase_cutoff_and_sales_deadline_test extends advanced_testcase {
    /** @return array{0:CommercePedagogicalPromotion,1:CommerceProduct} */
    private function offer_with_sales_close(
        string $sku,
        int $salesopensat,
        int $salesclosesat,
        int $now
    ): array {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $promotion = CommercePedagogicalPromotionRepository::create($DB)->save(
            new CommercePedagogicalPromotion(
                null,
                strtolower($sku),
                $sku,
                (int)$course->id,
                CommercePedagogicalPromotionStatus::SCHEDULED,
                true,
                $salesopensat,
                $salesclosesat,
                $now + DAYSECS,
                null,
                10,
                null,
                null,
                $now,
                $now
            )
        );

        $product = (new CommerceProductRepository($DB, new CommerceCatalogHydrator()))
            ->save(new CommerceProduct(
                $sku,
                CommerceProductType::COURSE_ACCESS,
                CommerceProductStatus::ACTIVE,
                $sku
            ));

        CommercePedagogicalPromotionOfferRepository::create($DB)->link(
            (int)$promotion->get_id(),
            (int)$product->get_id(),
            10,
            null,
            $now
        );

        $groups = CommercePedagogicalGroupRepository::create($DB);
        $groups->save_configuration(new CommercePedagogicalGroupConfiguration(
            (int)$promotion->get_id(),
            true,
            10,
            null,
            null,
            $now,
            $now
        ));
        CommercePedagogicalGroupOrchestrator::create($DB)->create_group(
            (int)$promotion->get_id(),
            $sku . ' group',
            0,
            null,
            null,
            null,
            null,
            null,
            $now,
            null,
            (int)$product->get_id()
        );

        return [$promotion, $product];
    }

    public function test_direct_hold_does_not_bypass_sales_cutoff_and_deadline_is_presented(): void {
        global $DB;
        $this->resetAfterTest(true);

        $now = time();
        $beforeclose = $now - 10;
        $salesclose = $now - 5;
        $this->offer_with_sales_close(
            'M110B-DIRECT',
            $now - HOURSECS,
            $salesclose,
            $now
        );

        $cartuuid = str_repeat('e', 32);
        CommercePedagogicalSeatReservationService::create($DB)->reserve(
            'M110B-DIRECT',
            $cartuuid,
            0,
            1,
            $beforeclose
        );

        $data = CommercePedagogicalCapacityPresenter::create($DB)
            ->for_product('M110B-DIRECT', $now, $cartuuid);

        self::assertFalse($data['pedagogicalavailable']);
        self::assertFalse($data['pedagogicalbuyavailable']);
        self::assertFalse($data['pedagogicaldirectresume']);
        self::assertTrue($data['pedagogicalsalesclosed']);
        self::assertFalse($data['pedagogicalsoldout']);
        self::assertTrue($data['haspedagogicalsalesclose']);
        self::assertSame($salesclose, $data['pedagogicalsalesclosesat']);
        self::assertNotSame('', $data['pedagogicalsalescloselabel']);
        self::assertFalse($data['pedagogicalsalescloseurgent']);
        self::assertSame(
            get_string('commerce_capacity_sales_closed_cta', 'local_subscriptions'),
            $data['pedagogicaldisabledctlabel']
        );

        try {
            CommercePedagogicalSalePolicy::create($DB)->assert_product_available(
                'M110B-DIRECT',
                $now,
                $cartuuid
            );
            self::fail('The direct hold must not bypass the sales closing time.');
        } catch (CommercePedagogicalSaleException $exception) {
            self::assertSame(
                CommercePedagogicalCapacityService::SALES_CLOSED,
                $exception->get_code_key()
            );
        }
    }

    public function test_sales_deadline_under_24_hours_is_marked_urgent(): void {
        global $DB;
        $this->resetAfterTest(true);

        $now = time();
        $salesclose = $now + (23 * HOURSECS);
        $this->offer_with_sales_close(
            'M110B-URGENT',
            $now - HOURSECS,
            $salesclose,
            $now
        );

        $data = CommercePedagogicalCapacityPresenter::create($DB)
            ->for_product('M110B-URGENT', $now);

        self::assertTrue($data['pedagogicalsalescloseurgent']);
        self::assertSame($salesclose, $data['pedagogicalsalesclosesat']);
    }

    public function test_public_surfaces_use_sales_closed_cta_and_show_deadline(): void {
        $root = dirname(__DIR__, 3);
        $partial = file_get_contents(
            $root . '/templates/storefront/pedagogical_capacity.mustache'
        );
        $card = file_get_contents(
            $root . '/templates/storefront/product_card.mustache'
        );
        $panel = file_get_contents(
            $root . '/templates/storefront/product_commerce_panel.mustache'
        );
        $section = file_get_contents(
            $root . '/templates/storefront/product_section.mustache'
        );
        $showroom = file_get_contents(
            $root . '/templates/showroom/offer.mustache'
        );

        self::assertStringContainsString('pedagogicalsalescloselabel', $partial);
        self::assertStringContainsString('pedagogicalsalescloseurgent', $partial);
        self::assertStringContainsString('fa-fire', $partial);
        self::assertStringContainsString('pedagogicaldisabledctlabel', $card);
        self::assertStringContainsString('pedagogicaldisabledctlabel', $panel);
        self::assertStringContainsString('pedagogicaldisabledctlabel', $section);
        self::assertStringContainsString('pedagogicalsalescloselabel', $showroom);
        self::assertStringContainsString('pedagogicalsalescloseurgent', $showroom);
        self::assertStringContainsString('fa-fire', $showroom);
        self::assertStringContainsString('pedagogicaldisabledctlabel', $showroom);
    }

    public function test_checkout_action_has_business_error_for_sales_cutoff(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout_action.php'
        );

        self::assertStringContainsString(
            'CommercePedagogicalSaleException',
            $source
        );
        self::assertStringContainsString(
            'CommercePedagogicalSeatReservationException',
            $source
        );
        self::assertStringContainsString(
            '$currentexception instanceof \moodle_exception',
            $source
        );
        self::assertStringContainsString(
            'CommercePedagogicalCapacityService::SALES_CLOSED',
            $source
        );
        self::assertStringContainsString(
            "'code' => CommercePedagogicalCapacityService::SALES_CLOSED",
            $source
        );
        self::assertStringContainsString(
            "get_string(\n            'commerce_capacity_sales_closed'",
            $source
        );
    }

    public function test_ajax_payment_surfaces_show_sales_closed_business_message(): void {
        global $CFG;

        $express = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );
        $paypal = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_paypal_embedded.js'
        );
        $page = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );

        self::assertStringContainsString('data-checkout-express-wallet-error', $page);
        self::assertStringContainsString('error instanceof Error', $express);
        self::assertStringContainsString('event?.paymentFailed?.', $express);
        self::assertStringContainsString('let feedback =', $paypal);
        self::assertStringContainsString('feedback =', $paypal);
    }
}
