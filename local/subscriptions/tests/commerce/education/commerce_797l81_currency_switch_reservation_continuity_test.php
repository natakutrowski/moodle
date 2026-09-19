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
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservation;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationRepository;
use local_subscriptions\commerce\education\reservation\CommercePedagogicalSeatReservationService;

final class commerce_797l81_currency_switch_reservation_continuity_test extends advanced_testcase {
    private function setup_offer(string $sku, int $now): array {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $promotion = CommercePedagogicalPromotionRepository::create($DB)->save(
            new CommercePedagogicalPromotion(
                null, 'l81-' . strtolower($sku), 'L8.1', (int)$course->id,
                CommercePedagogicalPromotionStatus::OPEN, true,
                null, null, $now, null, 1, null, null, $now, $now
            )
        );
        $product = (new CommerceProductRepository($DB, new CommerceCatalogHydrator()))->save(
            new CommerceProduct($sku, CommerceProductType::COURSE_ACCESS, CommerceProductStatus::ACTIVE, $sku)
        );
        CommercePedagogicalPromotionOfferRepository::create($DB)->link(
            (int)$promotion->get_id(), (int)$product->get_id(), 1, null, $now
        );
        $groups = CommercePedagogicalGroupRepository::create($DB);
        $groups->save_configuration(new CommercePedagogicalGroupConfiguration(
            (int)$promotion->get_id(), true, 1, null, null, $now, $now
        ));
        CommercePedagogicalGroupOrchestrator::create($DB)->create_group(
            (int)$promotion->get_id(), 'L8.1 group', 0,
            null, null, null, null, null, $now, null, (int)$product->get_id()
        );
        return [$promotion, $product];
    }

    public function test_anonymous_cart_rebind_preserves_deadline_and_anchors(): void {
        global $DB;
        $this->resetAfterTest(true);
        $now = time();
        [$promotion, $product] = $this->setup_offer('L81-ANON', $now);

        $service = CommercePedagogicalSeatReservationService::create($DB);
        $repo = CommercePedagogicalSeatReservationRepository::create($DB);
        $source = str_repeat('a', 32);
        $target = str_repeat('b', 32);

        $held = $service->reserve('L81-ANON', $source, 0, 1, $now, 900);
        self::assertNotNull($held);
        $held = $service->renew_lease('L81-ANON', $source, 0, 1, $now + 30, 600, 'checkout');
        $held = $service->renew_lease('L81-ANON', $source, 0, 1, $now + 60, 600, 'payment');

        $expires = $held->get_expires_at();
        $checkout = $held->get_checkout_started_at();
        $payment = $held->get_payment_started_at();
        $created = $held->get_time_created();

        self::assertSame(1, $service->transfer_cart($source, $target, 0, $now + 90));
        self::assertNull($repo->find((int)$promotion->get_id(), (int)$product->get_id(), $source));

        $moved = $repo->find((int)$promotion->get_id(), (int)$product->get_id(), $target);
        self::assertNotNull($moved);
        self::assertSame(CommercePedagogicalSeatReservation::ACTIVE, $moved->get_state());
        self::assertSame(0, $moved->get_customer_id());
        self::assertSame($expires, $moved->get_expires_at());
        self::assertSame($checkout, $moved->get_checkout_started_at());
        self::assertSame($payment, $moved->get_payment_started_at());
        self::assertSame($created, $moved->get_time_created());
    }

    public function test_negative_target_customer_remains_rejected(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->expectException(\coding_exception::class);
        CommercePedagogicalSeatReservationService::create($DB)->transfer_cart(
            str_repeat('c', 32), str_repeat('d', 32), -1, time()
        );
    }

    public function test_currency_switch_service_wires_atomic_rebind_before_source_delete(): void {
        global $CFG;
        $this->resetAfterTest(true);
        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/cart/currency/CommerceCartCurrencySwitchService.php'
        );
        $transfer = strpos($source, '$this->reservations->transfer_cart(');
        $delete = strpos($source, '$this->repository->delete($sourcekey);');
        self::assertNotFalse($transfer);
        self::assertNotFalse($delete);
        self::assertLessThan($delete, $transfer);
        self::assertStringContainsString('$this->reservations->release_product(', $source);
    }
}
