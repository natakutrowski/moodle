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
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupConfiguration;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupOrchestrator;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotion;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionOfferRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationService;

final class commerce_797k4_public_capacity_presentation_test extends advanced_testcase {
    private function product(string $sku): CommerceProduct {
        global $DB;
        return (new CommerceProductRepository($DB, new CommerceCatalogHydrator()))
            ->save(new CommerceProduct(
                $sku,
                CommerceProductType::COURSE_ACCESS,
                CommerceProductStatus::ACTIVE,
                $sku
            ));
    }

    /** @return array{0:CommercePedagogicalPromotion,1:CommerceProduct} */
    private function offer(string $sku, int $capacity, int $now): array {
        global $DB;
        $course = $this->getDataGenerator()->create_course();
        $promotion = CommercePedagogicalPromotionRepository::create($DB)->save(
            new CommercePedagogicalPromotion(
                null, strtolower($sku), $sku, (int)$course->id,
                CommercePedagogicalPromotionStatus::OPEN, true,
                null, null, $now, null, $capacity, null, null, $now, $now
            )
        );
        $product = $this->product($sku);
        CommercePedagogicalPromotionOfferRepository::create($DB)->link(
            (int)$promotion->get_id(), (int)$product->get_id(),
            $capacity, null, $now
        );
        $groups = CommercePedagogicalGroupRepository::create($DB);
        $groups->save_configuration(new CommercePedagogicalGroupConfiguration(
            (int)$promotion->get_id(), true, $capacity,
            null, null, $now, $now
        ));
        CommercePedagogicalGroupOrchestrator::create($DB)->create_group(
            (int)$promotion->get_id(), $sku . ' group', 0, null,
            null, null, null, null, $now, null, (int)$product->get_id()
        );
        return [$promotion, $product];
    }

    public function test_public_label_tracks_live_remaining_capacity(): void {
        global $DB;
        $this->resetAfterTest(true);
        $now = time();
        $this->offer('K4-LIVE', 3, $now);

        $presenter = CommercePedagogicalCapacityPresenter::create($DB);
        $before = $presenter->for_product('K4-LIVE', $now);
        self::assertTrue($before['pedagogicalavailable']);
        self::assertTrue($before['pedagogicalbuyavailable']);
        self::assertFalse($before['pedagogicaldirectresume']);
        self::assertSame(3, $before['pedagogicalremaining']);
        self::assertSame(
            get_string('commerce_capacity_places_left', 'local_subscriptions', 3),
            $before['pedagogicalcapacitylabel']
        );

        CommercePedagogicalSeatReservationService::create($DB)->reserve(
            'K4-LIVE', str_repeat('a', 32), 101, 1, $now
        );
        $after = $presenter->for_product('K4-LIVE', $now);
        self::assertSame(2, $after['pedagogicalremaining']);
    }

    public function test_last_reserved_seat_is_presented_as_sold_out(): void {
        global $DB;
        $this->resetAfterTest(true);
        $now = time();
        $this->offer('K4-LAST', 1, $now);

        CommercePedagogicalSeatReservationService::create($DB)->reserve(
            'K4-LAST', str_repeat('b', 32), 201, 1, $now
        );

        $data = CommercePedagogicalCapacityPresenter::create($DB)
            ->for_product('K4-LAST', $now);

        self::assertFalse($data['pedagogicalavailable']);
        self::assertFalse($data['pedagogicalbuyavailable']);
        self::assertFalse($data['pedagogicaldirectresume']);
        self::assertTrue($data['pedagogicalsoldout']);
        self::assertSame(0, $data['pedagogicalremaining']);
        self::assertSame(
            get_string('commerce_capacity_sold_out', 'local_subscriptions'),
            $data['pedagogicalcapacitylabel']
        );
    }

    public function test_direct_purchase_hold_allows_only_buy_now_resume(): void {
        global $DB;
        $this->resetAfterTest(true);
        $now = time();
        $this->offer('K4-DIRECT-RESUME', 1, $now);
        $cartuuid = str_repeat('d', 32);

        CommercePedagogicalSeatReservationService::create($DB)->reserve(
            'K4-DIRECT-RESUME',
            $cartuuid,
            0,
            1,
            $now
        );

        $data = CommercePedagogicalCapacityPresenter::create($DB)
            ->for_product('K4-DIRECT-RESUME', $now, $cartuuid);

        self::assertFalse($data['pedagogicalavailable']);
        self::assertTrue($data['pedagogicalbuyavailable']);
        self::assertTrue($data['pedagogicaldirectresume']);
        self::assertFalse($data['pedagogicalsoldout']);
        self::assertSame(0, $data['pedagogicalremaining']);
        self::assertSame(
            get_string('commerce_cart_seat_reserved', 'local_subscriptions'),
            $data['pedagogicalcapacitylabel']
        );
    }

    public function test_classic_product_has_no_capacity_ui(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->product('K4-CLASSIC');

        $data = CommercePedagogicalCapacityPresenter::create($DB)
            ->for_product('K4-CLASSIC', time());

        self::assertFalse($data['haspedagogicalcapacity']);
        self::assertTrue($data['pedagogicalavailable']);
        self::assertTrue($data['pedagogicalbuyavailable']);
    }

    public function test_public_templates_gate_purchase_ctas_on_capacity(): void {
        $root = dirname(__DIR__, 3);
        $card = file_get_contents($root . '/templates/storefront/product_card.mustache');
        $panel = file_get_contents($root . '/templates/storefront/product_commerce_panel.mustache');
        $section = file_get_contents($root . '/templates/storefront/product_section.mustache');
        $showroom = file_get_contents($root . '/templates/showroom/offer.mustache');

        self::assertStringContainsString(
            'local_subscriptions/storefront/pedagogical_capacity',
            $card
        );
        self::assertStringContainsString('{{#pedagogicalavailable}}', $card);
        self::assertStringContainsString('{{#pedagogicalbuyavailable}}', $card);
        self::assertStringContainsString('{{^pedagogicalbuyavailable}}', $card);

        self::assertStringContainsString(
            'local_subscriptions/storefront/pedagogical_capacity',
            $panel
        );
        self::assertStringContainsString('{{#pedagogicalavailable}}', $panel);
        self::assertStringContainsString('{{#pedagogicalbuyavailable}}', $panel);
        self::assertStringContainsString('{{#pedagogicalavailable}}', $section);
        self::assertStringContainsString('{{#pedagogicalbuyavailable}}', $section);

        self::assertStringContainsString('pedagogicalcapacitylabel', $showroom);
        self::assertStringContainsString('commerce-showroom-btn--disabled', $showroom);
    }
}
