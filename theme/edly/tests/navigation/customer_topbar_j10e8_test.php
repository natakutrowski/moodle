<?php

declare(strict_types=1);

namespace theme_edly;

defined('MOODLE_INTERNAL') || die();

final class customer_topbar_j10e8_test extends \advanced_testcase {
    public function test_customer_topbar_replaces_legacy_user_and_language_menus(): void {
        $theme = dirname(__DIR__, 2);
        $navbar = file_get_contents($theme . '/templates/theme_boost/navbar.mustache');
        $navigation = file_get_contents($theme . '/templates/customer_navigation.mustache');
        $service = file_get_contents($theme . '/classes/local/customer_navigation.php');

        self::assertIsString($navbar);
        self::assertIsString($navigation);
        self::assertIsString($service);

        self::assertStringNotContainsString(
            '{{> theme_edly/edly_navbar_user }}',
            $navbar
        );
        self::assertStringContainsString(
            'campus-topbar-user',
            $navigation
        );
        self::assertStringContainsString(
            'campus-topbar-language',
            $navigation
        );
        self::assertStringContainsString(
            'customernavigation.logouturl',
            $navigation
        );
        self::assertStringContainsString(
            "'/login/logout.php'",
            $service
        );
    }

    public function test_topbar_uses_native_details_without_bootstrap_dropdown_dependency(): void {
        $theme = dirname(__DIR__, 2);
        $navigation = file_get_contents($theme . '/templates/customer_navigation.mustache');

        self::assertIsString($navigation);
        self::assertStringContainsString('<details class="campus-topbar-language">', $navigation);
        self::assertStringContainsString('<details class="campus-topbar-user">', $navigation);
        self::assertStringNotContainsString('data-bs-toggle="dropdown"', $navigation);
    }
}
