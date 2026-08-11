<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\statistics\CommerceStatisticsPeriod;

final class commerce_statistics_period_test extends \advanced_testcase {
    public function test_previous_period_has_same_duration(): void {
        $period = CommerceStatisticsPeriod::custom(1000, 1600);
        $previous = $period->previous();
        $this->assertSame(600, $period->duration());
        $this->assertSame(400, $previous->start());
        $this->assertSame(1000, $previous->end());
        $this->assertTrue($period->contains(1000));
        $this->assertFalse($period->contains(1600));
    }

    public function test_invalid_period_is_rejected(): void {
        $this->expectException(\coding_exception::class);
        CommerceStatisticsPeriod::custom(1000, 1000);
    }
}
