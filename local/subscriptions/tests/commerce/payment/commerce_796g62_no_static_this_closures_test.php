<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796g62_no_static_this_closures_test extends advanced_testcase {
    public function test_availability_provider_filter_can_use_this(): void {
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
    }

    public function test_architecture_method_mapper_can_use_this(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/'
            . 'CommercePaymentArchitectureInspector.php'
        );

        $this->assertStringContainsString(
            '$this->presentationpolicy',
            $contents
        );
        $this->assertStringNotContainsString(
            'static function($availability): array',
            $contents
        );
        $this->assertStringContainsString(
            'function($availability): array',
            $contents
        );
    }
}
