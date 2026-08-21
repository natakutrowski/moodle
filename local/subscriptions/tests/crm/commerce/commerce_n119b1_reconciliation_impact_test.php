<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n119b1_reconciliation_impact_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_shared_impact_renderer_explains_each_business_change(): void {
        $renderer = $this->file(
            'classes/commerce/customer/identity/CommerceCustomerIdentityImpactRenderer.php'
        );

        foreach ([
            'crm_identity_reconciliation_effect_purchase_link',
            'crm_identity_reconciliation_effect_course_access_detail',
            'crm_identity_reconciliation_effect_digital_detail',
            'crm_identity_reconciliation_effect_guest_detail',
            'crm_identity_reconciliation_effect_legacy_detail',
        ] as $key) {
            self::assertStringContainsString(
                $key,
                $renderer
            );
        }
    }

    public function test_index_and_bulk_share_same_impact_renderer(): void {
        foreach ([
            'admin/commerce/customer-identities/index.php',
            'admin/commerce/customer-identities/bulk.php',
        ] as $file) {
            $page = $this->file($file);

            self::assertStringContainsString(
                'CommerceCustomerIdentityImpactRenderer::render(',
                $page
            );
            self::assertStringNotContainsString(
                'commerce_identity_dryrun_impact_summary',
                $page
            );
        }
    }

    public function test_bulk_uses_public_sale_reference_and_links_to_sale(): void {
        $page = $this->file(
            'admin/commerce/customer-identities/bulk.php'
        );

        self::assertStringContainsString(
            'CommercePublicOrderReference',
            $page
        );
        self::assertStringContainsString(
            'from_internal(',
            $page
        );
        self::assertStringContainsString(
            "'/local/subscriptions/admin/commerce/purchases/view.php'",
            $page
        );
        self::assertStringContainsString(
            'crm-identity-reconciliation-sale-reference',
            $page
        );
    }


    public function test_preview_action_has_dedicated_action_row(): void {
        $page = $this->file(
            'admin/commerce/customer-identities/index.php'
        );

        self::assertStringContainsString(
            'crm-identity-reconciliation-table-actions',
            $page
        );
    }
}
