<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n119b_reconciliation_ux_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_reconciliation_hides_internal_purchase_reference_from_table(): void {
        $page = $this->file(
            'admin/commerce/customer-identities/index.php'
        );

        self::assertStringContainsString(
            'crm-identity-reconciliation-sale-reference',
            $page
        );
        self::assertStringContainsString(
            'CommercePublicOrderReference',
            $page
        );
        self::assertStringContainsString(
            'from_internal(',
            $page
        );
    }


    public function test_customer_name_and_email_are_grouped_in_one_column(): void {
        $page = $this->file(
            'admin/commerce/customer-identities/index.php'
        );

        self::assertStringContainsString(
            'crm-identity-reconciliation-customer-name',
            $page
        );
        self::assertStringContainsString(
            'crm-identity-reconciliation-customer-email',
            $page
        );
        self::assertStringNotContainsString(
            "get_string('commerce_identity_email'",
            $page
        );
    }

    public function test_dry_run_is_presented_as_expected_effect(): void {
        $page = $this->file(
            'admin/commerce/customer-identities/index.php'
        );

        self::assertStringContainsString(
            'crm_identity_reconciliation_expected_effect',
            $page
        );
        self::assertStringContainsString(
            'CommerceCustomerIdentityImpactRenderer::render(',
            $page
        );
        self::assertStringNotContainsString(
            'commerce_identity_dryrun_impact_summary',
            $page
        );
    }


    public function test_advanced_filters_are_collapsed_behind_details(): void {
        $page = $this->file(
            'admin/commerce/customer-identities/index.php'
        );

        self::assertStringContainsString(
            "'details'",
            $page
        );
        self::assertStringContainsString(
            'crm_identity_reconciliation_more_filters',
            $page
        );
        self::assertStringContainsString(
            'crm-identity-reconciliation-advanced-grid',
            $page
        );
    }
}
