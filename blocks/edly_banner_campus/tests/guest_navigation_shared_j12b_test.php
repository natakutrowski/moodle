<?php

declare(strict_types=1);

namespace block_edly_banner_campus;

defined('MOODLE_INTERNAL') || die();

final class guest_navigation_shared_j12b_test extends \advanced_testcase {
    public function test_landing_renders_shared_local_campus_component(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
                . '/blocks/edly_banner_campus/'
                . 'block_edly_banner_campus.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString(
            'local_campus/guest_navigation',
            $source
        );
        self::assertStringContainsString(
            "js_call_amd('local_campus/guest_navigation', 'init')",
            $source
        );
    }
}
