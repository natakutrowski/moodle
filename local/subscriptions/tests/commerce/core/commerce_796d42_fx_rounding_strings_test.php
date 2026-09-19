<?php
declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796d42_fx_rounding_strings_test extends advanced_testcase {
    public function test_d4_rounding_strings_are_defined(): void {
        foreach ([
            'commerce_fx_rounding_none',
            'commerce_fx_rounding_whole',
            'commerce_fx_rounding_ending90',
        ] as $key) {
            $value = get_string($key, 'local_subscriptions');
            $this->assertStringNotContainsString('[[' . $key . ']]', $value);
        }
    }
}
