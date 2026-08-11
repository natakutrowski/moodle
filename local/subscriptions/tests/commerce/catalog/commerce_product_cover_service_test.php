<?php

declare(strict_types=1);

namespace local_subscriptions;

use local_subscriptions\commerce\catalog\assets\CommerceCatalogMediaManager;
use local_subscriptions\commerce\catalog\cover\CommerceProductCoverContext;
use local_subscriptions\commerce\catalog\cover\CommerceProductCoverService;

final class commerce_product_cover_service_test extends \advanced_testcase {
    public function test_context_specific_cover_and_storefront_fallback_are_resolved(): void {
        $this->resetAfterTest();
        $context = \context_system::instance();
        $media = new CommerceCatalogMediaManager($context);
        $service = new CommerceProductCoverService($media);
        $fs = get_file_storage();
        $productid = 987;
        foreach ([CommerceProductCoverContext::STOREFRONT => 'store.jpg', CommerceProductCoverContext::RECOMMENDATION => 'rec.jpg'] as $role => $name) {
            $fs->create_file_from_string([
                'contextid'=>$context->id,'component'=>'local_subscriptions','filearea'=>'catalog_media',
                'itemid'=>$productid,'filepath'=>'/'.$role.'/','filename'=>$name,
            ], 'image');
        }
        $recommendation = $service->resolve($productid, CommerceProductCoverContext::RECOMMENDATION);
        $resources = $service->resolve($productid, CommerceProductCoverContext::RESOURCES);
        $this->assertStringContainsString(
            'store.jpg',
            (string)$recommendation->get_url()
        );
        $this->assertTrue($recommendation->is_fallback());
        $this->assertSame(
            CommerceProductCoverContext::STOREFRONT,
            $recommendation->get_resolved_context()
        );
        $this->assertStringContainsString('store.jpg', (string)$resources->get_url());
        $this->assertTrue($resources->is_fallback());
    }
}
