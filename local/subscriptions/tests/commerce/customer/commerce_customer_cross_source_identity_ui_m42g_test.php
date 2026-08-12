<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_customer_cross_source_identity_ui_m42g_test extends advanced_testcase {
    public function test_link_page_is_dry_run_first_and_sesskey_protected(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/admin/commerce/customer-identities/'
                . 'legacy-link.php'
        );

        self::assertStringContainsString(
            'CommerceLegacyDigitalIdentityLinkService',
            $source
        );
        self::assertStringContainsString(
            "optional_param('action', 'preview'",
            $source
        );
        self::assertStringContainsString('require_sesskey()', $source);
        self::assertStringContainsString(
            "'name' => 'confirm'",
            $source
        );
    }

    public function test_provisioning_page_links_similarity_candidate_to_existing_account_flow(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/admin/commerce/customer-identities/'
                . 'provisioning.php'
        );

        self::assertStringContainsString(
            '/customer-identities/legacy-link.php',
            $source
        );
        self::assertStringContainsString(
            'commerce_identity_legacy_link_action',
            $source
        );
    }
}
