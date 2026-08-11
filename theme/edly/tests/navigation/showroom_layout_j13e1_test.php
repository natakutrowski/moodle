<?php

declare(strict_types=1);

namespace theme_edly;

defined('MOODLE_INTERNAL') || die();

final class showroom_layout_j13e1_test extends \advanced_testcase {
    public function test_showroom_layout_keeps_topbar_without_moodle_navbar(): void {
        global $CFG;
        $config = file_get_contents($CFG->dirroot . '/theme/edly/config.php');
        $layout = file_get_contents($CFG->dirroot . '/theme/edly/layout/showroom.php');
        $template = file_get_contents($CFG->dirroot . '/theme/edly/templates/showroom_shell.mustache');

        self::assertIsString($config);
        self::assertIsString($layout);
        self::assertIsString($template);
        self::assertStringContainsString("'showroom' => [", $config);
        self::assertStringContainsString("'file' => 'showroom.php'", $config);
        self::assertStringContainsString('theme_edly/customer_navigation', $template);
        self::assertStringContainsString('local_campus/guest_navigation', $template);
        self::assertStringNotContainsString('theme_boost/navbar', $template);
        self::assertStringNotContainsString('output.full_header', $template);

    }
}
