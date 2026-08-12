<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

/**
 * Final M4.2 safety contract.
 */
final class commerce_customer_identity_operations_safety_m42f_test extends advanced_testcase {
    public function test_similarity_and_dry_run_surfaces_do_not_delete_users(): void {
        global $CFG;

        $files = [
            'admin/commerce/customer-identities/similarities.php',
            'classes/commerce/customer/merge/CommerceCustomerMergePlanner.php',
            'classes/commerce/customer/reconciliation/CommerceCustomerIdentitySearchService.php',
        ];

        foreach ($files as $relativepath) {
            $source = file_get_contents(
                $CFG->dirroot . '/local/subscriptions/' . $relativepath
            );

            self::assertStringNotContainsString(
                'user_delete_user(',
                $source,
                $relativepath
            );
            self::assertStringNotContainsString(
                '$DB->delete_records(\'user\'',
                $source,
                $relativepath
            );
        }
    }

    public function test_merge_execution_suspends_sources_instead_of_deleting_them(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/customer/merge/'
                . 'CommerceCustomerMergeExecutionService.php'
        );

        self::assertStringContainsString(
            '$user->suspended = 1',
            $source
        );
        self::assertStringContainsString(
            'user_update_user(',
            $source
        );
        self::assertStringNotContainsString(
            'user_delete_user(',
            $source
        );
    }

    public function test_provisioned_account_is_not_active_before_customer_activation(): void {
        global $CFG;

        $provisioning = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/customer/provisioning/'
                . 'CommerceLegacyDigitalProvisioningService.php'
        );
        $activation = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/customer/provisioning/'
                . 'CommerceLegacyDigitalAccountActivationService.php'
        );

        self::assertStringContainsString(
            "'confirmed' => 0",
            $provisioning
        );
        self::assertStringContainsString(
            "'suspended' => 1",
            $provisioning
        );
        self::assertStringContainsString(
            '$user->confirmed = 1',
            $activation
        );
        self::assertStringContainsString(
            '$user->suspended = 0',
            $activation
        );
    }
}
