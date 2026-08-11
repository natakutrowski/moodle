<?php

declare(strict_types=1);

namespace local_subscriptions;

final class commerce_product_cover_admin_test
        extends \advanced_testcase {

    public function test_product_asset_page_exposes_four_master_visual_roles(): void {
        $source = file_get_contents(
            dirname(__DIR__, 3)
            . '/admin/commerce/products/assets.php'
        );

        foreach ([
            'ROLE_CHECKOUT',
            'ROLE_STOREFRONT',
            'ROLE_PRODUCT',
            'ROLE_RESOURCES',
        ] as $role) {
            $this->assertStringContainsString($role, $source);
        }

        foreach ([
            'ROLE_RECOMMENDATION',
            'ROLE_EMAIL',
            'ROLE_SOCIAL',
        ] as $legacyrole) {
            $this->assertStringNotContainsString(
                'CommerceCatalogMediaManager::'
                    . $legacyrole
                    . ',',
                $source
            );
        }

        foreach (['1:1', '4:3', '16:9', '4:5'] as $ratio) {
            $this->assertStringContainsString(
                "'ratio' => '" . $ratio . "'",
                $source
            );
        }

        $this->assertStringContainsString(
            "'save_cover_'",
            $source
        );
        $this->assertStringContainsString(
            "'delete_cover_'",
            $source
        );
    }
}
