<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

/**
 * M4.2D execution UI and schema contract.
 */
final class commerce_customer_merge_execution_ui_m42d_test extends advanced_testcase {
    public function test_merge_page_requires_explicit_confirmation_for_execution(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/admin/commerce/customer-identities/merge.php'
        );

        self::assertStringContainsString(
            'CommerceCustomerMergeExecutionService',
            $source
        );
        self::assertStringContainsString(
            "'name' => 'confirmmerge'",
            $source
        );
        self::assertStringContainsString(
            "'value' => 'execute'",
            $source
        );
        self::assertStringContainsString('require_sesskey()', $source);
    }

    public function test_merge_audit_tables_are_declared_in_install_xml(): void {
        global $CFG;

        $xml = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/db/install.xml'
        );

        self::assertStringContainsString(
            'TABLE NAME="local_subs_identity_merge"',
            $xml
        );
        self::assertStringContainsString(
            'TABLE NAME="local_subs_identity_merge_source"',
            $xml
        );
    }
}
