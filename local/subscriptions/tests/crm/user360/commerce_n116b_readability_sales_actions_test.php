<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n116b_readability_sales_actions_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = __DIR__ . '/../../../' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_commerce_order_menu_reuses_sales_context_menu_classes(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360CommerceAccessRenderer.php'
        );

        self::assertStringContainsString(
            'crm-sales-row-menu-toggle',
            $renderer
        );
        self::assertStringContainsString(
            'crm-sales-row-menu-link',
            $renderer
        );
        self::assertStringContainsString(
            'crm-sales-row-menu-section',
            $renderer
        );
        self::assertStringNotContainsString(
            'crm-sales-row-menu-item',
            $renderer
        );
    }

    public function test_commerce_order_menu_links_to_sales_and_order_details(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360CommerceAccessRenderer.php'
        );

        self::assertStringContainsString(
            "'/local/subscriptions/admin/commerce/purchases/view.php'",
            $renderer
        );
        self::assertStringContainsString(
            "'/local/subscriptions/order_details.php'",
            $renderer
        );
        self::assertStringContainsString(
            "'/local/subscriptions/order_invoice.php'",
            $renderer
        );
        self::assertStringContainsString(
            "'/local/subscriptions/admin/commerce/mail/index.php'",
            $renderer
        );
    }

    public function test_commerce_order_menu_exposes_existing_sales_mail_actions(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360CommerceAccessRenderer.php'
        );

        self::assertStringContainsString(
            'CommercePurchaseActionPolicy',
            $renderer
        );
        self::assertStringContainsString(
            'CommerceSalesFollowupService',
            $renderer
        );
        self::assertStringContainsString(
            "'/local/subscriptions/admin/commerce/purchases/resend_receipt.php'",
            $renderer
        );
        self::assertStringContainsString(
            "'/local/subscriptions/admin/commerce/purchases/resend_access.php'",
            $renderer
        );
        self::assertStringContainsString(
            "'/local/subscriptions/admin/commerce/purchases/followup_mail.php'",
            $renderer
        );
        self::assertStringContainsString(
            "'sesskey' => sesskey()",
            $renderer
        );
    }

    public function test_advanced_commerce_keeps_sales_style_context_menu_contract(): void {
        $renderer = $this->file(
            'classes/crm/user360/rendering/User360CommerceAccessRenderer.php'
        );

        self::assertStringContainsString(
            'crm-sales-row-menu-toggle',
            $renderer
        );
        self::assertStringContainsString(
            'crm-sales-row-menu-link',
            $renderer
        );
        self::assertStringContainsString(
            'crm-sales-row-menu-section',
            $renderer
        );
    }


    public function test_plugin_version_is_unchanged(): void {
        $version = $this->file('version.php');
        self::assertStringContainsString(
            '$plugin->version = 2026081602;',
            $version
        );
    }
}
