<?php

namespace local_subscriptions\tests\commerce\storefront;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_795f6e_targeted_corrections_test extends advanced_testcase {
    public function test_lifecycle_page_uses_current_crm_configurator_signature(): void {
        $source = file_get_contents(__DIR__ . '/../../../admin/commerce/products/lifecycle.php');

        self::assertStringContainsString(
            "'local-subscriptions-commerce-product-lifecycle-page'",
            $source
        );
    }

    public function test_access_scope_separates_shared_scope_from_canonical_mapping(): void {
        $source = file_get_contents(__DIR__ . '/../../../admin/commerce/products/access_scope.php');

        self::assertStringContainsString("\$action === 'scope'", $source);
        self::assertStringContainsString("\$action === 'canonical'", $source);
        self::assertStringContainsString("\$metadata['access']", $source);
        self::assertStringNotContainsString('transfer_product(', $source);
    }

    public function test_digital_ownership_reads_legacy_paid_purchases(): void {
        $source = file_get_contents(
            __DIR__ . '/../../../classes/commerce/storefront/ownership/CommerceStorefrontOwnershipResolver.php'
        );

        self::assertStringContainsString('subscription_digital_product', $source);
        self::assertStringContainsString('subscription_digital_payment_request', $source);
        self::assertStringContainsString("'legacy_digital_purchase'", $source);
        self::assertStringContainsString('userid = :userid', $source);
    }

    public function test_gustave_uses_the_current_independent_medallion_renderer(): void {
        $css = (string)file_get_contents(__DIR__ . '/../../../styles/storefront.css');
        $template = (string)file_get_contents(__DIR__ . '/../../../templates/storefront/product_badges.mustache');

        self::assertStringContainsString('.commerce-product-badge__gustave-medallion', $css);
        self::assertStringContainsString('width: 4.0rem;', $css);
        self::assertStringContainsString('height: 4.0rem;', $css);
        self::assertStringContainsString('commerce-product-badge__gustave-medallion', $template);
        self::assertStringContainsString('commerce-product-badge__gustave-image', $template);
    }
}
