<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\cart\catalog\CommerceCartCatalogGateway;
use local_subscriptions\commerce\cart\catalog\CommerceCartProductQuote;
use local_subscriptions\commerce\cart\policy\CommerceSingleQuantityPolicy;
use local_subscriptions\commerce\cart\repository\CommerceInMemoryCartRepository;
use local_subscriptions\commerce\cart\service\CommerceCartCalculator;
use local_subscriptions\commerce\cart\service\CommerceCartFactory;
use local_subscriptions\commerce\cart\service\CommerceCartService;
use local_subscriptions\commerce\cart\service\CommerceCartSessionKeyResolver;
use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductStatus;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\catalog\persistence\CommerceCatalogHydrator;
use local_subscriptions\commerce\catalog\repository\CommerceProductRepository;
use local_subscriptions\commerce\checkout\flow\CommerceDirectPurchaseSession;
use local_subscriptions\commerce\checkout\unified\CommerceCheckoutSeatReservationCoordinator;
use local_subscriptions\commerce\domain\value\CommerceMoney;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupConfiguration;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupOrchestrator;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotion;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionOfferRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationRepository;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationService;

final class commerce_797k6_checkout_reservation_lease_test extends advanced_testcase {
    private function product(string $sku): CommerceProduct {
        global $DB;

        return (new CommerceProductRepository(
            $DB,
            new CommerceCatalogHydrator()
        ))->save(new CommerceProduct(
            $sku,
            CommerceProductType::COURSE_ACCESS,
            CommerceProductStatus::ACTIVE,
            $sku
        ));
    }

    private function offer(string $sku, int $now): CommerceProduct {
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

    private function carts(): CommerceCartService {
        global $DB;

        $catalog = new class implements CommerceCartCatalogGateway {
            public function quote(
                string $productsku,
                int $priceid,
                string $currency,
                string $language,
                ?int $at = null
            ): CommerceCartProductQuote {
                return new CommerceCartProductQuote(
                    strtoupper($productsku),
                    $priceid,
                    strtoupper($productsku),
                    CommerceMoney::from_minor(100, $currency),
                    new CommerceSingleQuantityPolicy()
                );
            }
        };

        return new CommerceCartService(
            new CommerceInMemoryCartRepository(),
            new CommerceCartSessionKeyResolver(),
            new CommerceCartFactory(),
            new CommerceCartCalculator($catalog),
            $catalog,
            null,
            null,
            null,
            null,
            CommercePedagogicalSeatReservationService::create($DB)
        );
    }

    public function test_cart_hold_can_be_extended_for_checkout(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $this->offer('K6-CART', $now);
        $carts = $this->carts();

        $added = $carts->add_product(
            101,
            'EUR',
            'fr',
            'K6-CART',
            1,
            1,
            [],
            $now
        );
        $cartuuid = $added->get_cart()->get_uuid();

        $renewed = $carts->renew_seat_reservations(
            101,
            'EUR',
            $now + 10,
            CommerceCheckoutSeatReservationCoordinator::CHECKOUT_TTL
        );

        self::assertTrue($renewed->has_changed());

        $link =
            CommercePedagogicalPromotionOfferRepository::create($DB)
                ->sale_link_for_product('K6-CART');
        $reservation =
            CommercePedagogicalSeatReservationRepository::create($DB)
                ->find(
                    (int)$link['promotion']->get_id(),
                    (int)$link['offer']->productid,
                    $cartuuid
                );

        self::assertSame(
            $now + 10
                + CommerceCheckoutSeatReservationCoordinator::CHECKOUT_TTL,
            $reservation?->get_expires_at()
        );
    }

    public function test_direct_purchase_reuses_original_cart_uuid(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $this->offer('K6-DIRECT', $now);
        $carts = $this->carts();

        $prepared = $carts->prepare_direct_product(
            201,
            'EUR',
            'fr',
            'K6-DIRECT',
            2,
            1,
            [],
            $now
        );
        $uuid = $prepared->get_cart()->get_uuid();

        $replayed = $carts->direct_snapshot(
            201,
            'EUR',
            'fr',
            'K6-DIRECT',
            2,
            1,
            [],
            $now + 10,
            $uuid,
            CommerceCheckoutSeatReservationCoordinator::CHECKOUT_TTL
        );

        self::assertSame(
            $uuid,
            $replayed->get_cart()->get_uuid()
        );

        $link =
            CommercePedagogicalPromotionOfferRepository::create($DB)
                ->sale_link_for_product('K6-DIRECT');
        $reservation =
            CommercePedagogicalSeatReservationRepository::create($DB)
                ->find(
                    (int)$link['promotion']->get_id(),
                    (int)$link['offer']->productid,
                    $uuid
                );

        self::assertSame(
            $now + 10
                + CommerceCheckoutSeatReservationCoordinator::CHECKOUT_TTL,
            $reservation?->get_expires_at()
        );
    }

    public function test_checkout_and_payment_minimum_leases_are_ten_minutes(): void {
        self::assertSame(
            10 * MINSECS,
            CommerceCheckoutSeatReservationCoordinator::CHECKOUT_TTL
        );
        self::assertSame(
            10 * MINSECS,
            CommerceCheckoutSeatReservationCoordinator::PAYMENT_TTL
        );
    }

    public function test_direct_purchase_session_persists_cart_uuid(): void {
        global $SESSION;

        $this->resetAfterTest(true);
        $uuid = str_repeat('d', 32);

        CommerceDirectPurchaseSession::store(
            'EUR',
            'K6-SESSION',
            9,
            1,
            [],
            $uuid
        );

        $current = CommerceDirectPurchaseSession::current('EUR');

        self::assertNotNull($current);
        self::assertSame($uuid, $current['cartuuid']);

        CommerceDirectPurchaseSession::clear();
    }

    public function test_checkout_runtime_contains_both_lease_extensions(): void {
        $root = dirname(__DIR__, 3);
        $runtime = file_get_contents(
            $root .
            '/classes/commerce/checkout/unified/CommerceCheckoutRuntime.php'
        );

        self::assertStringContainsString(
            'CommerceCheckoutSeatReservationCoordinator::CHECKOUT_TTL',
            $runtime
        );
        self::assertStringContainsString(
            'CommerceCheckoutSeatReservationCoordinator::PAYMENT_TTL',
            $runtime
        );
        self::assertStringContainsString(
            '$this->seatreservations?->extend_snapshot(',
            $runtime
        );
    }
}
