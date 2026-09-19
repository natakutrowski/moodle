<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotion;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;

final class commerce_797m632_started_promotion_sales_window_test extends advanced_testcase {
    private function promotion(
        string $status,
        bool $published,
        ?int $salesopensat,
        ?int $salesclosesat,
        int $now
    ): CommercePedagogicalPromotion {
        return new CommercePedagogicalPromotion(
            1,
            'm632-started-sales',
            'M6.3.2 started sales',
            16,
            $status,
            $published,
            $salesopensat,
            $salesclosesat,
            $now - HOURSECS,
            $now + DAYSECS,
            10,
            null,
            null,
            $now - HOURSECS,
            $now
        );
    }

    public function test_started_promotion_keeps_sales_open_inside_explicit_sales_window(): void {
        $now = 1789752000;
        $promotion = $this->promotion(
            CommercePedagogicalPromotionStatus::STARTED,
            true,
            $now - HOURSECS,
            $now + DAYSECS,
            $now
        );

        self::assertTrue($promotion->sales_are_open($now));
    }

    public function test_started_promotion_still_obeys_sales_open_and_close_dates(): void {
        $now = 1789752000;

        $notyet = $this->promotion(
            CommercePedagogicalPromotionStatus::STARTED,
            true,
            $now + HOURSECS,
            $now + DAYSECS,
            $now
        );
        self::assertFalse($notyet->sales_are_open($now));

        $closed = $this->promotion(
            CommercePedagogicalPromotionStatus::STARTED,
            true,
            $now - DAYSECS,
            $now - 1,
            $now
        );
        self::assertFalse($closed->sales_are_open($now));
    }

    public function test_finished_and_unpublished_promotions_remain_closed(): void {
        $now = 1789752000;

        $finished = $this->promotion(
            CommercePedagogicalPromotionStatus::FINISHED,
            true,
            $now - HOURSECS,
            $now + DAYSECS,
            $now
        );
        self::assertFalse($finished->sales_are_open($now));

        $unpublished = $this->promotion(
            CommercePedagogicalPromotionStatus::STARTED,
            false,
            $now - HOURSECS,
            $now + DAYSECS,
            $now
        );
        self::assertFalse($unpublished->sales_are_open($now));
    }
}
