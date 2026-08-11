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
        self::assertStringNotContainsString("crm_topbar_calendar", $renderer);
        self::assertStringNotContainsString("UrlFactory::my_profile()", $renderer);
        self::assertStringContainsString('min-height: 2.75rem', $styles);
    }
}
