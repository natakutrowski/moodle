<?php

declare(strict_types=1);

namespace local_subscriptions;

use local_subscriptions\commerce\storefront\seo\CommerceStorefrontSeoPresenter;

defined('MOODLE_INTERNAL') || die();

final class commerce_storefront_seo_responsive_test
        extends \advanced_testcase {

    public function test_head_markup_is_escaped_and_complete(): void {
        $presenter = new CommerceStorefrontSeoPresenter();
        $html = $presenter->head_html([
            'title' => 'A1 "CampusFR"',
            'description' => 'Cours <complet>',
            'canonical' => 'https://example.test/product?a=1&b=2',
            'image' => 'https://example.test/image.jpg',
            'locale' => 'fr-FR',
            'type' => 'product',
        ]);

        $this->assertStringContainsString('rel="canonical"', $html);
        $this->assertStringContainsString('og:title', $html);
        $this->assertStringContainsString('twitter:card', $html);
        $this->assertStringNotContainsString('<complet>', $html);
    }

    public function test_asset_editor_exposes_only_four_master_roles(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/products/assets.php'
        );

        $this->assertSame(
            1,
            substr_count(
                $source,
                'CommerceCatalogMediaManager::ROLE_CHECKOUT,'
            )
        );
        $this->assertSame(
            1,
            substr_count(
                $source,
                'CommerceCatalogMediaManager::ROLE_STOREFRONT,'
            )
        );
        $this->assertSame(
            1,
            substr_count(
                $source,
                'CommerceCatalogMediaManager::ROLE_PRODUCT,'
            )
        );
        $this->assertSame(
            1,
            substr_count(
                $source,
                'CommerceCatalogMediaManager::ROLE_RESOURCES,'
            )
        );
    }

    public function test_audit_does_not_count_legacy_fallback_as_master_mismatch(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/catalog/visual/'
            . 'CommerceProductVisualAuditService.php'
        );

        $this->assertStringContainsString(
            "&& !\$format['fallback_available']",
            $source
        );
        $this->assertStringContainsString(
            "\$format['available']",
            $source
        );
    }


    public function test_seo_uses_official_moodle_head_hook(): void {
        global $CFG;

        $page = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/storefront_product.php'
        );
        $hooks = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/db/hooks.php'
        );

        $this->assertStringNotContainsString(
            'additionalhtmlhead',
            $page
        );
        $this->assertStringContainsString(
            'CommerceStorefrontSeoHeadRegistry::set',
            $page
        );
        $this->assertStringContainsString(
            'before_standard_head_html_generation',
            $hooks
        );
    }

    public function test_responsive_certification_is_green(): void {
        $service = new \local_subscriptions\commerce\storefront\certification\CommerceStorefrontSeoResponsiveCertificationService();

        $findings = $service->certify();

        $this->assertFalse($service->has_errors($findings));
        $this->assertCount(4, $findings);
    }
}
