<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

/**
 * 7.98D.6.1 — the Trial Legacy observability page must be reachable from CRM Commerce.
 */
final class trial_admin_navigation_798d61_test extends advanced_testcase {

    public function test_main_crm_commerce_menu_exposes_trial_status(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/crm/navigation/CrmNavigationRegistry.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString("get_string('trial_admin_status_nav', 'local_subscriptions')", $source);
        self::assertStringContainsString('subscription_config::trial_transition_status_page()', $source);
        self::assertStringContainsString('Capabilities::MANAGE_SUBSCRIPTIONS', $source);
    }

    public function test_commerce_secondary_navigation_exposes_trial_status(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/crm/commerce/navigation/CommerceSectionNavigationRegistry.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString("public const TRIAL = 'trial';", $source);
        self::assertStringContainsString('self::TRIAL', $source);
        self::assertStringContainsString('subscription_config::trial_transition_status_page()', $source);
    }

    public function test_trial_status_page_renders_commerce_navigation_with_trial_active(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/trial/index.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString(
            'CommerceSectionNavigationRenderer::render(',
            $source
        );
        self::assertStringContainsString(
            'CommerceSectionNavigationRenderer::TRIAL',
            $source
        );
        self::assertStringContainsString(
            'CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE',
            $source
        );
    }

    public function test_moodle_admin_fallback_entry_remains_available(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/settings.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString('local_subscriptions_trial_transition_status', $source);
        self::assertStringContainsString('trial_transition_status_page()', $source);
    }
}
