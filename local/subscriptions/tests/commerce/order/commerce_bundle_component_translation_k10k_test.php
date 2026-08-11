<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_bundle_component_translation_k10k_test extends advanced_testcase {
    public function test_bundle_component_resolver_uses_central_display_name_resolver(): void {
        $root = dirname(__DIR__, 3);
        $source = (string)file_get_contents(
            $root . '/classes/commerce/order/presentation/CommerceBundleComponentResolver.php'
        );

        $this->assertStringContainsString(
            'CommerceProductDisplayNameResolver',
            $source
        );
        $this->assertStringContainsString(
            '$displaynames->resolve(',
            $source
        );
        $this->assertStringContainsString(
            'current_language()',
            $source
        );

        $this->assertStringNotContainsString(
            "COALESCE(NULLIF(tr.name, ''), p.name)",
            $source
        );
        $this->assertStringNotContainsString(
            "LEFT JOIN {local_subs_commerce_prod_tr} tr",
            $source
        );
    }

    public function test_all_bundle_surfaces_share_the_same_component_resolver(): void {
        $root = dirname(__DIR__, 3);

        foreach ([
            '/order_result.php',
            '/order_details.php',
            '/classes/output/my_purchases/CurrentPresentationRenderer.php',
            '/classes/commerce/order/invoice/CommerceInvoicePdfService.php',
        ] as $path) {
            $source = (string)file_get_contents($root . $path);
            $this->assertStringContainsString(
                'CommerceBundleComponentResolver',
                $source,
                $path
            );
        }
    }
}
