<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\catalog\assets\CommerceCatalogMediaManager;
use local_subscriptions\commerce\catalog\assets\CommerceCatalogResponsiveImageService;

final class commerce_catalog_responsive_images_j15h1g_test extends advanced_testcase {
    public function test_supported_role_presets_are_stable(): void {
        $reflection = new \ReflectionClass(CommerceCatalogResponsiveImageService::class);
        $presets = $reflection->getConstant('PRESETS');
        $this->assertSame([480, 800], $presets[CommerceCatalogMediaManager::ROLE_STOREFRONT]['widths']);
        $this->assertSame([160, 320], $presets[CommerceCatalogMediaManager::ROLE_CHECKOUT]['widths']);
        $this->assertSame([240, 480], $presets[CommerceCatalogMediaManager::ROLE_RESOURCES]['widths']);
        $this->assertSame([360, 720], $presets[CommerceCatalogMediaManager::ROLE_RECOMMENDATION]['widths']);
    }

    public function test_templates_use_srcset_without_removing_fallback_src(): void {
        global $CFG;
        foreach ([
            'templates/storefront/product_card.mustache',
            'templates/cart/page.mustache',
            'templates/checkout/page.mustache',
            'templates/my_digital_products/components/resource_card.mustache',
        ] as $relative) {
            $content = file_get_contents($CFG->dirroot . '/local/subscriptions/' . $relative);
            $this->assertStringContainsString('src="{{coverurl}}"', $content);
            $this->assertStringContainsString('srcset="{{coversrcset}}"', $content);
        }
    }
}
