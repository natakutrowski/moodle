<?php

declare(strict_types=1);

namespace theme_edly;

defined('MOODLE_INTERNAL') || die();

final class guest_navigation_j11_test extends \advanced_testcase {
    public function test_guest_navigation_uses_unified_language_shop_and_login_controls(): void {
        global $CFG;

        $service = file_get_contents(
            $CFG->dirroot
                . '/theme/edly/classes/local/customer_navigation.php'
        );
        $context = file_get_contents(
            $CFG->dirroot
                . '/theme/edly/inc/edly_themehandler_context.php'
        );
        $navbar = file_get_contents(
            $CFG->dirroot
                . '/theme/edly/templates/theme_boost/navbar.mustache'
        );
        $desktop = file_get_contents(
            $CFG->dirroot
                . '/theme/edly/templates/guest_navigation.mustache'
        );
        $mobile = file_get_contents(
            $CFG->dirroot
                . '/theme/edly/templates/guest_navigation_mobile.mustache'
        );
        $css = file_get_contents(
            $CFG->dirroot
                . '/theme/edly/style/customer-navigation.css'
        );

        self::assertIsString($service);
        self::assertIsString($context);
        self::assertIsString($navbar);
        self::assertIsString($desktop);
        self::assertIsString($mobile);
        self::assertIsString($css);
        self::assertStringContainsString(
            'public static function build_guest',
            $service
        );
        self::assertStringContainsString(
            'GuestNavigationBuilder',
            $service
        );
        self::assertStringContainsString(
            "['guestnavigation'] = customer_navigation::build_guest",
            $context
        );
        self::assertStringContainsString(
            'theme_edly/guest_navigation',
            $navbar
        );
        self::assertStringContainsString(
            'campus-topbar-language__dropdown',
            $desktop
        );
        self::assertStringContainsString(
            'guestnavigation.shopurl',
            $desktop
        );
        self::assertStringContainsString(
            'guestnavigation.loginurl',
            $desktop
        );
        self::assertStringContainsString(
            'local_campus/guest_navigation',
            $mobile
        );
        self::assertStringContainsString(
            '.campus-guest-nav__button--shop',
            $css
        );
    }
}
