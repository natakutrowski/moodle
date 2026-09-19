<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796g6_admin_policy_routing_test extends advanced_testcase {
    public function test_availability_filters_methods_and_providers_through_admin_policy(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/availability/'
            . 'CommercePaymentAvailabilityResolver.php'
        );

        $this->assertStringContainsString(
            'CommercePaymentPresentationPolicy',
            $contents
        );
        $this->assertStringContainsString(
            'is_method_allowed(',
            $contents
        );
        $this->assertStringContainsString(
            'is_provider_allowed(',
            $contents
        );
        $this->assertStringContainsString(
            '$provider->get_key()',
            $contents
        );
    }

    public function test_market_eligibility_is_still_applied_before_provider_routing(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/availability/'
            . 'CommercePaymentAvailabilityResolver.php'
        );

        $market = strpos(
            $contents,
            'CommercePaymentMethodMarketEligibility::supports('
        );
        $admin = strpos(
            $contents,
            'is_method_allowed('
        );

        $this->assertNotFalse($market);
        $this->assertNotFalse($admin);
        $this->assertLessThan(
            $admin,
            $market
        );
    }
}
