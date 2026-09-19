<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\cart\presentation\CommerceCartSeatReservationPresenter;
use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductStatus;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupConfiguration;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupOrchestrator;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotion;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionOfferRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationService;

final class commerce_797k5_cart_reservation_ux_test extends advanced_testcase {
    private function product(string $sku): CommerceProduct {
        global $DB;

        return (new CommerceProductRepository(
            $DB,
            new CommerceCatalogHydrator()
        ))->save(
            new CommerceProduct(
                $sku,
                CommerceProductType::COURSE_ACCESS,
                CommerceProductStatus::ACTIVE,
                $sku
            )
        );
    }

    private function setup_offer(
        string $sku,
        int $now
    ): CommerceProduct {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $promotion =
            CommercePedagogicalPromotionRepository::create($DB)->save(
                new CommercePedagogicalPromotion(
                    null,
                    strtolower($sku),
                    $sku,
                    (int)$course->id,
                    CommercePedagogicalPromotionStatus::OPEN,
                    true,
                    null,
                    null,
                    $now,
                    null,
                    1,
                    null,
                    null,
                    $now,
                    $now
                )
            );

        $product = $this->product($sku);

        CommercePedagogicalPromotionOfferRepository::create($DB)->link(
            (int)$promotion->get_id(),
            (int)$product->get_id(),
            1,
            null,
            $now
        );

        $groups = CommercePedagogicalGroupRepository::create($DB);
        $groups->save_configuration(
            new CommercePedagogicalGroupConfiguration(
                (int)$promotion->get_id(),
                true,
                1,
                null,
                null,
                $now,
                $now
            )
        );

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

        return $product;
    }

    public function test_active_hold_decorates_cart_line_and_summary(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $this->setup_offer('K5-ACTIVE', $now);
        $cartuuid = str_repeat('a', 32);

        CommercePedagogicalSeatReservationService::create($DB)->reserve(
            'K5-ACTIVE',
            $cartuuid,
            101,
            1,
            $now,
            120
        );

        $data = CommerceCartSeatReservationPresenter::create($DB)
            ->decorate(
                [
                    'items' => [
                        ['productsku' => 'K5-ACTIVE'],
                    ],
                ],
                $cartuuid,
                $now
            );

        self::assertTrue($data['haspedagogicalitems']);
        self::assertTrue($data['seatreservationsvalid']);
        self::assertFalse($data['seatreservationsexpired']);
        self::assertSame($now + 120, $data['seatreservationexpiresat']);
        self::assertTrue($data['items'][0]['seatreservationactive']);
        self::assertFalse($data['items'][0]['seatreservationexpired']);
    }

    public function test_expired_hold_blocks_cart_checkout_state(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $this->setup_offer('K5-EXPIRED', $now);
        $cartuuid = str_repeat('b', 32);

        CommercePedagogicalSeatReservationService::create($DB)->reserve(
            'K5-EXPIRED',
            $cartuuid,
            201,
            1,
            $now,
            30
        );

        $data = CommerceCartSeatReservationPresenter::create($DB)
            ->decorate(
                [
                    'items' => [
                        ['productsku' => 'K5-EXPIRED'],
                    ],
                ],
                $cartuuid,
                $now + 31
            );

        self::assertFalse($data['seatreservationsvalid']);
        self::assertTrue($data['seatreservationsexpired']);
        self::assertTrue($data['items'][0]['seatreservationexpired']);
        self::assertSame(0, $data['seatreservationexpiresat']);
    }

    public function test_classic_cart_lines_do_not_require_a_hold(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->product('K5-CLASSIC');

        $data = CommerceCartSeatReservationPresenter::create($DB)
            ->decorate(
                [
                    'items' => [
                        ['productsku' => 'K5-CLASSIC'],
                    ],
                ],
                str_repeat('c', 32),
                time()
            );

        self::assertFalse($data['haspedagogicalitems']);
        self::assertTrue($data['seatreservationsvalid']);
        self::assertFalse($data['items'][0]['hasseatreservation']);
    }

    public function test_cart_templates_expose_countdown_renew_and_checkout_gate(): void {
        $root = dirname(__DIR__, 3);

        $page = file_get_contents(
            $root . '/templates/cart/page.mustache'
        );
        $summary = file_get_contents(
            $root . '/templates/cart/summary.mustache'
        );
        $script = file_get_contents(
            $root . '/amd/src/cart_seat_reservation.js'
        );

        self::assertStringContainsString(
            'data-cart-seat-countdown',
            $page
        );
        self::assertStringContainsString(
            'name="action" value="renewseats"',
            $page
        );
        self::assertStringContainsString(
            'checkoutdisabledreason',
            $summary
        );
        self::assertStringContainsString(
            'window.location.reload()',
            $script
        );
        self::assertStringContainsString(
            'export const init',
            $script
        );
        self::assertStringNotContainsString(
            'define([], function()',
            $script
        );
    }
}
