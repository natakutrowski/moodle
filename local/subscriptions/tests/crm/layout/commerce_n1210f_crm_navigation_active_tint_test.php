<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n1210f_crm_navigation_active_tint_test extends \advanced_testcase {

    public function test_active_crm_navigation_has_a_tinted_background(): void {
        $styles = dirname(__DIR__, 3) . '/styles.css';
        self::assertFileExists($styles);

        $css = file_get_contents($styles);
        self::assertIsString($css);

        self::assertStringContainsString(
            '.crm-app-navigation-link.is-active,',
            $css
        );
        self::assertStringContainsString(
            '.crm-app-navigation-link[aria-current="page"]',
            $css
        );
        self::assertStringContainsString(
            'rgba(238, 242, 255, 0.98)',
            $css
        );
        self::assertStringContainsString(
            'rgba(245, 243, 255, 0.98)',
            $css
        );
    }
}
