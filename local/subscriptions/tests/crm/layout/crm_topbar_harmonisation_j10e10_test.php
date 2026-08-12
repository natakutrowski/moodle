<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class crm_topbar_harmonisation_j10e10_test extends \advanced_testcase {
    public function test_crm_menu_matches_customer_navigation_contract(): void {
        $root = dirname(__DIR__, 3);
        $renderer = file_get_contents(
            $root . '/classes/crm/layout/CrmTopBarRenderer.php'
        );
        $styles = file_get_contents($root . '/styles.css');

        self::assertIsString($renderer);
        self::assertStringContainsString('UrlFactory::my_campus()', $renderer);
        self::assertStringContainsString('UrlFactory::my_courses()', $renderer);
        self::assertStringContainsString('UrlFactory::my_digital_products()', $renderer);
        self::assertStringContainsString('UrlFactory::my_purchases()', $renderer);
        self::assertStringContainsString('UrlFactory::storefront()', $renderer);
        self::assertStringContainsString('crm_topbar_preferences', $renderer);
        self::assertStringContainsString('crm_topbar_switch_role', $renderer);
        self::assertStringContainsString('render_moodle_admin_menu()', $renderer);
        self::assertStringContainsString('/admin/purgecaches.php', $renderer);
        self::assertStringContainsString("'section' => 'maintenancemode'", $renderer);
        self::assertStringContainsString("'section' => 'local_subscriptions_settings'", $renderer);
        self::assertStringContainsString("'section' => 'local_campus_settings'", $renderer);
        self::assertStringContainsString('crm_topbar_admin_shortcuts', $renderer);
        self::assertStringContainsString("'linkroot'", $renderer);
        self::assertStringContainsString("'linkusers'", $renderer);
        self::assertStringContainsString("'linkcourses'", $renderer);
        self::assertStringContainsString("'linkgrades'", $renderer);
        self::assertStringContainsString("'linkmodules'", $renderer);
        self::assertStringContainsString("'linkappearance'", $renderer);
        self::assertStringContainsString("'linkserver'", $renderer);
        self::assertStringContainsString("'linkreports'", $renderer);
        self::assertStringContainsString("'linkdevelopment'", $renderer);
        self::assertStringContainsString('.crm-app-topbar-admin-dropdown', $styles);
        self::assertStringContainsString('max-height: calc(100vh - 5rem)', $styles);
        self::assertStringNotContainsString("crm_topbar_calendar", $renderer);
        self::assertStringNotContainsString("UrlFactory::my_profile()", $renderer);
        self::assertStringContainsString('min-height: 2.75rem', $styles);
        self::assertStringContainsString('is_role_switched(SITEID)', $renderer);
        self::assertStringContainsString("get_string('switchrolereturn')", $renderer);
        self::assertStringContainsString("\$switchroleparams['switchrole'] = 0", $renderer);
        self::assertStringContainsString("\$switchroleparams['sesskey'] = sesskey()", $renderer);
        self::assertStringContainsString('crm-app-topbar-menu-current-role', $renderer);
        self::assertStringContainsString('.crm-app-topbar-menu-current-role', $styles);
    }
}
