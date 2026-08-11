<?php

declare(strict_types=1);

namespace theme_edly;

defined('MOODLE_INTERNAL') || die();

final class customer_navigation_storefront_j10e7_test extends \advanced_testcase {
    public function test_mobile_menu_is_registered_with_page_requirements(): void {
        $root = dirname(__DIR__, 2);
        $context = file_get_contents($root . '/inc/edly_themehandler_context.php');
        $navbar = file_get_contents($root . '/templates/theme_boost/navbar.mustache');

        $this->assertIsString($context);
        $this->assertIsString($navbar);
        $this->assertStringContainsString(
            '$PAGE->requires->js_call_amd(\'theme_edly/mobile_menu\', \'init\');',
            $context
        );
        $this->assertStringNotContainsString(
            "require(['theme_edly/mobile_menu']",
            $navbar
        );
    }
}
