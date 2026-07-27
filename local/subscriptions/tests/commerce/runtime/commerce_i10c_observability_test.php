<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\read\observability\CommerceReadObservation;
use local_subscriptions\commerce\read\observability\CommerceReadObserver;

final class commerce_i10c_observability_test extends advanced_testcase {
    public function test_successful_native_observation_is_silent(): void {
        $observer = new CommerceReadObserver();

        $observer->observe(new CommerceReadObservation(
            'crm',
            'subscription',
            1,
            'native',
            true,
            false,
            null,
            1
        ));

        $this->assertDebuggingNotCalled();
    }

    public function test_successful_legacy_fallback_is_silent(): void {
        $observer = new CommerceReadObserver();

        $observer->observe(new CommerceReadObservation(
            'admin',
            'digital',
            73,
            'legacy_fallback',
            true,
            false,
            null,
            13
        ));

        $this->assertDebuggingNotCalled();
    }

    public function test_failed_read_is_reported(): void {
        $observer = new CommerceReadObserver();

        $observer->observe(new CommerceReadObservation(
            'admin',
            'subscription',
            305,
            'unavailable',
            false,
            false,
            null,
            4
        ));

        $this->assertDebuggingCalled();
    }
}
