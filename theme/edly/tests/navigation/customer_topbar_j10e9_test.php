<?php

declare(strict_types=1);

namespace theme_edly;

final class customer_topbar_j10e9_test extends \advanced_testcase {
    public function test_topbar_contains_admin_shortcuts_and_no_notification_output(): void {
        $root = dirname(__DIR__, 2);
        $service = file_get_contents($root . '/classes/local/customer_navigation.php');
        $template = file_get_contents($root . '/templates/customer_navigation.mustache');
        $navbar = file_get_contents($root . '/templates/theme_boost/navbar.mustache');
        $css = file_get_contents($root . '/style/customer-navigation.css');

        self::assertStringContainsString("has_capability('moodle/site:config'", $service);
        self::assertStringContainsString('CrmNavigationRegistry', $service);
        self::assertStringContainsString('visible_items($context)', $service);
        self::assertStringContainsString("new \\moodle_url('/admin/search.php')", $service);
        self::assertStringContainsString('customernavigation.isadmin', $template);
        self::assertStringContainsString('campus-customer-nav__admin-menu', $template);
        self::assertStringContainsString('customernavigation.crmitems', $template);
        self::assertStringContainsString('customernavigation.adminitems', $template);
        self::assertStringContainsString('campus-customer-nav__admin-dropdown', $template);
        self::assertStringNotContainsString('output.navbar_plugin_output', $navbar);
        self::assertStringContainsString('justify-content: flex-end', $css);
        self::assertStringContainsString('.campus-customer-nav__admin-dropdown', $css);
    }
}
