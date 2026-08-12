<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

/**
 * M4.2E UI and activation contract.
 */
final class commerce_legacy_digital_provisioning_ui_m42e_test extends advanced_testcase {
    public function test_identity_workspace_has_provisioning_tab_and_dry_run(): void {
        global $CFG;

        $navigation = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/customer/identity/'
                . 'CommerceCustomerIdentityNavigationRenderer.php'
        );
        $page = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/admin/commerce/customer-identities/'
                . 'provisioning.php'
        );

        self::assertStringContainsString(
            "public const PROVISIONING = 'provisioning'",
            $navigation
        );
        self::assertStringContainsString(
            'CommerceLegacyDigitalBulkProvisioningService',
            $page
        );
        self::assertStringContainsString(
            "'value' => 'preview'",
            $page
        );
        self::assertStringContainsString(
            "'name' => 'confirmprovisioning'",
            $page
        );
        self::assertStringContainsString(
            'require_sesskey()',
            $page
        );
    }

    public function test_activation_page_uses_one_time_activation_service(): void {
        global $CFG;

        $page = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/legacy_account_activate.php'
        );
        $service = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/customer/provisioning/'
                . 'CommerceLegacyDigitalAccountActivationService.php'
        );

        self::assertStringContainsString(
            'CommerceLegacyDigitalAccountActivationService',
            $page
        );
        self::assertStringContainsString(
            'validate_user_key(',
            $service
        );
        self::assertStringContainsString(
            'delete_user_key(',
            $service
        );
        self::assertStringContainsString(
            "'ready'",
            $service
        );
    }
}
