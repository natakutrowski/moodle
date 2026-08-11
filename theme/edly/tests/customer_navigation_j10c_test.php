<?php
// This file is part of Moodle - http://moodle.org/

namespace theme_edly;

defined('MOODLE_INTERNAL') || die();

final class customer_navigation_j10c_test extends \advanced_testcase {
    public function test_customer_navigation_is_integrated_in_global_theme_shell(): void {
        global $CFG;

        $navbar = file_get_contents(
            $CFG->dirroot . '/theme/edly/templates/theme_boost/navbar.mustache'
        );
        $context = file_get_contents(
            $CFG->dirroot . '/theme/edly/inc/edly_themehandler_context.php'
        );
        $config = file_get_contents(
            $CFG->dirroot . '/theme/edly/config.php'
        );

        $this->assertIsString($navbar);
        $this->assertStringContainsString(
            'theme_edly/customer_navigation',
            $navbar
        );
        $this->assertStringContainsString(
            'theme_edly/customer_navigation_mobile',
            $navbar
        );
        $this->assertIsString($context);
        $this->assertStringContainsString(
            "customer_navigation::build(\$PAGE)",
            $context
        );
        $this->assertIsString($config);
        $this->assertStringContainsString(
            "'customer-navigation'",
            $config
        );
    }

    public function test_navigation_uses_public_url_factory_with_safe_fallbacks(): void {
        global $CFG;

        $service = file_get_contents(
            $CFG->dirroot . '/theme/edly/classes/local/customer_navigation.php'
        );

        $this->assertIsString($service);
        foreach ([
            "self::url('my_campus'",
            "self::url('my_courses'",
            "self::url('my_digital_products'",
            "self::url('my_purchases'",
            "self::url('my_profile'",
            "self::url('storefront'",
            "self::url('cart'",
        ] as $needle) {
            $this->assertStringContainsString($needle, $service);
        }
    }
}
