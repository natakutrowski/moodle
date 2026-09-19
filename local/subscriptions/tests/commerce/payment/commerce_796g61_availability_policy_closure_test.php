<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796g61_availability_policy_closure_test extends advanced_testcase {
    public function test_provider_filter_closure_can_access_presentation_policy(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/availability/'
            . 'CommercePaymentAvailabilityResolver.php'
        );

        $this->assertStringContainsString(
            '$this->presentationpolicy',
            $contents
        );

        $this->assertStringNotContainsString(
            'static fn(' . PHP_EOL
            . '                    CommercePaymentProvider $provider',
            $contents
        );

        $this->assertStringContainsString(
            'fn(' . PHP_EOL
            . '                    CommercePaymentProvider $provider',
            $contents
        );
    }
}
