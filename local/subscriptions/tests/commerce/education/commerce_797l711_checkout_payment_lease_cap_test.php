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
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupConfiguration;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupOrchestrator;
use local_subscriptions\commerce\education\group\CommercePedagogicalGroupRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotion;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionOfferRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationService;
use local_subscriptions\commerce\checkout\unified\CommerceCheckoutSeatReservationCoordinator;

final class commerce_797l711_checkout_payment_lease_cap_test extends advanced_testcase {
    public function test_checkout_and_payment_leases_are_anchored_once(): void {
        global $DB;

        $this->resetAfterTest(true);
        $now = time();
        $course = $this->getDataGenerator()->create_course();
        $promotion = CommercePedagogicalPromotionRepository::create($DB)->save(
            new CommercePedagogicalPromotion(
                null,
                'l711',
                'L7.1.1',
                (int)$course->id,
                CommercePedagogicalPromotionStatus::OPEN,
                true,
                null,
                null,
                $now,
                null,
                2,
                null,
                null,
                $now,
                $now
            )
        );
        $product = (new CommerceProductRepository($DB, new CommerceCatalogHydrator()))->save(
            new CommerceProduct(
                'L711',
                CommerceProductType::COURSE_ACCESS,
                CommerceProductStatus::ACTIVE,
                'L711'
            )
        );
        CommercePedagogicalPromotionOfferRepository::create($DB)->link(
            (int)$promotion->get_id(),
            (int)$product->get_id(),
            2,
            null,
            $now
        );
        $groups = CommercePedagogicalGroupRepository::create($DB);
        $groups->save_configuration(new CommercePedagogicalGroupConfiguration(
            (int)$promotion->get_id(),
            true,
            2,
            null,
            null,
            $now,
            $now
        ));
        CommercePedagogicalGroupOrchestrator::create($DB)->create_group(
            (int)$promotion->get_id(),
            'L711 group',
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

        $service = CommercePedagogicalSeatReservationService::create($DB);
        $uuid = str_repeat('a', 32);
        $service->reserve('L711', $uuid, 42, 1, $now);

        $firstcheckout = $service->renew_lease(
            'L711',
            $uuid,
            42,
            1,
            $now + 60,
            CommerceCheckoutSeatReservationCoordinator::CHECKOUT_TTL,
            'checkout'
        );
        self::assertSame($now + 60, $firstcheckout?->get_checkout_started_at());
        // Entering checkout must never shorten the original 15-minute cart hold.
        self::assertSame(
            $now + CommercePedagogicalSeatReservationService::DEFAULT_TTL,
            $firstcheckout?->get_expires_at()
        );

        $secondcheckout = $service->renew_lease(
            'L711',
            $uuid,
            42,
            1,
            $now + 5 * MINSECS,
            CommerceCheckoutSeatReservationCoordinator::CHECKOUT_TTL,
            'checkout'
        );
        self::assertSame(
            $firstcheckout?->get_expires_at(),
            $secondcheckout?->get_expires_at()
        );

        $cartrefresh = $service->renew(
            'L711',
            $uuid,
            42,
            1,
            $now + 6 * MINSECS
        );
        self::assertSame(
            $firstcheckout?->get_expires_at(),
            $cartrefresh?->get_expires_at()
        );

        $firstpayment = $service->renew_lease(
            'L711',
            $uuid,
            42,
            1,
            $now + 7 * MINSECS,
            CommerceCheckoutSeatReservationCoordinator::PAYMENT_TTL,
            'payment'
        );
        self::assertSame($now + 7 * MINSECS, $firstpayment?->get_payment_started_at());
        self::assertSame(
            $now + 7 * MINSECS + CommerceCheckoutSeatReservationCoordinator::PAYMENT_TTL,
            $firstpayment?->get_expires_at()
        );

        $secondpayment = $service->renew_lease(
            'L711',
            $uuid,
            42,
            1,
            $now + 8 * MINSECS,
            CommerceCheckoutSeatReservationCoordinator::PAYMENT_TTL,
            'payment'
        );
        self::assertSame(
            $firstpayment?->get_expires_at(),
            $secondpayment?->get_expires_at()
        );
    }
}
