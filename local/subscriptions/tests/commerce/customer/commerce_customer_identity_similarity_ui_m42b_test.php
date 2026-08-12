<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

/**
 * M4.2B UI contract for Identity Operations.
 */
final class commerce_customer_identity_similarity_ui_m42b_test extends advanced_testcase {
    public function test_identity_workspace_exposes_reconciliation_and_similarity_tabs(): void {
        global $CFG;

        $renderer = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/customer/identity/'
                . 'CommerceCustomerIdentityNavigationRenderer.php'
        );
        $index = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/admin/commerce/customer-identities/index.php'
        );
        $similarities = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/admin/commerce/customer-identities/'
                . 'similarities.php'
        );

        self::assertStringContainsString(
            "self::RECONCILIATION",
            $renderer
        );
        self::assertStringContainsString(
            "self::SIMILARITIES",
            $renderer
        );
        self::assertStringContainsString(
            'CommerceCustomerIdentityNavigationRenderer::RECONCILIATION',
            $index
        );
        self::assertStringContainsString(
            'CommerceCustomerIdentityNavigationRenderer::SIMILARITIES',
            $similarities
        );
    }

    public function test_similarity_page_is_read_only_and_has_no_merge_action(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/admin/commerce/customer-identities/'
                . 'similarities.php'
        );

        self::assertStringContainsString(
            'CommerceCustomerIdentitySimilarityService',
            $source
        );
        self::assertStringNotContainsString('require_sesskey()', $source);
        self::assertStringNotContainsString("name' => 'merge'", $source);
        self::assertStringNotContainsString('$DB->update_record(\'user\'', $source);
        self::assertStringNotContainsString('$DB->delete_records(\'user\'', $source);
        self::assertStringNotContainsString('user_delete_user(', $source);
    }
}
