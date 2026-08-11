<?php

declare(strict_types=1);

namespace theme_edly;

defined('MOODLE_INTERNAL') || die();

final class guest_mobile_j12a1_test extends \advanced_testcase {
    public function test_guest_navigation_uses_shared_builder_and_native_controls(): void {
        global $CFG;

        $builder = file_get_contents(
            $CFG->dirroot
                . '/theme/edly/classes/local/customer_navigation.php'
        );
        $mobile = file_get_contents(
            $CFG->dirroot
                . '/theme/edly/templates/guest_navigation_mobile.mustache'
        );

        self::assertIsString($builder);
        self::assertIsString($mobile);
        self::assertStringContainsString(
            'GuestNavigationBuilder',
            $builder
        );
        self::assertStringContainsString(
            'local_campus/guest_navigation',
            $mobile
        );
        self::assertStringNotContainsString(
            'data-bs-toggle',
            $mobile
        );
    }
}
