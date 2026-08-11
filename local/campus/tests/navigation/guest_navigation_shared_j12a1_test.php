<?php

declare(strict_types=1);

namespace local_campus;

defined('MOODLE_INTERNAL') || die();

final class guest_navigation_shared_j12a1_test extends \advanced_testcase {
    public function test_shared_guest_navigation_builder_exists(): void {
        global $CFG;

        $builder = file_get_contents(
            $CFG->dirroot
                . '/local/campus/classes/navigation/'
                . 'GuestNavigationBuilder.php'
        );

        self::assertIsString($builder);
        self::assertStringContainsString(
            'final class GuestNavigationBuilder',
            $builder
        );
        self::assertStringContainsString("'shopurl'", $builder);
        self::assertStringContainsString("'loginurl'", $builder);
        self::assertStringContainsString("'languages'", $builder);
    }
}
