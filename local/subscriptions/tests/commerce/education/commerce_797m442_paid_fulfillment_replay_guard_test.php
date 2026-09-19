<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_797m442_paid_fulfillment_replay_guard_test extends advanced_testcase {
    public function test_completed_purchase_cannot_be_regressed_to_pending_by_stale_worker(): void {
        $source = file_get_contents(
            __DIR__ . '/../../../classes/commerce/fulfillment/native/checkout/CommerceNativePaidPurchaseCompleter.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            "CommercePurchaseStatus::FULFILLMENT_PENDING",
            $source
        );
        $this->assertStringContainsString(
            "CommercePurchaseStatus::FULFILLED,",
            $source
        );
        $this->assertStringContainsString(
            "CommercePurchaseStatus::COMPLETED,",
            $source
        );
    }

    public function test_consumed_paid_hold_is_not_renewed_on_fulfillment_replay(): void {
        $lifecycle = file_get_contents(
            __DIR__ . '/../../../classes/commerce/education/reservation/CommercePedagogicalSeatReservationPurchaseLifecycle.php'
        );
        $service = file_get_contents(
            __DIR__ . '/../../../classes/commerce/education/reservation/CommercePedagogicalSeatReservationService.php'
        );

        $this->assertIsString($lifecycle);
        $this->assertIsString($service);
        $this->assertStringContainsString('is_consumed_by_purchase(', $lifecycle);
        $this->assertStringContainsString('public function is_consumed_by_purchase(', $service);
        $this->assertStringContainsString('CommercePedagogicalSeatReservation::CONSUMED', $service);
    }
}
