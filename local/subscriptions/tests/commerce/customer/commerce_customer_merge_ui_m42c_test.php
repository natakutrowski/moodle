<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

/**
 * M4.2C dry-run UI contract.
 */
final class commerce_customer_merge_ui_m42c_test extends advanced_testcase {
    public function test_merge_page_is_dry_run_only(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/admin/commerce/customer-identities/merge.php'
        );

        self::assertStringContainsString(
            'CommerceCustomerMergePlanner',
            $source
        );
        self::assertStringContainsString(
            'CommerceCustomerIdentityNavigationRenderer::MERGE',
            $source
        );
        self::assertStringNotContainsString(
            '$DB->update_record(',
            $source
        );
        self::assertStringNotContainsString(
            '$DB->delete_records(',
            $source
        );
        self::assertStringNotContainsString(
            'user_delete_user(',
            $source
        );
    }

    public function test_similarity_page_can_select_accounts_for_merge_preview(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/admin/commerce/customer-identities/'
                . 'similarities.php'
        );

        self::assertStringContainsString(
            "'name' => 'userids[]'",
            $source
        );
        self::assertStringContainsString(
            '/customer-identities/merge.php',
            $source
        );
    }
}
