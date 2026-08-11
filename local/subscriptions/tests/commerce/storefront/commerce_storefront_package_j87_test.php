<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductStatus;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\storefront\content\CommerceStorefrontContentFileService;
use local_subscriptions\commerce\storefront\transfer\CommerceStorefrontPackageService;

final class commerce_storefront_package_j87_test extends advanced_testcase {
    public function test_package_round_trip_preserves_storefront_media(): void {
        $this->resetAfterTest();
        $context = \context_system::instance();
        $itemid = 424242;
        get_file_storage()->create_file_from_string([
            'contextid' => $context->id,
            'component' => CommerceStorefrontContentFileService::COMPONENT,
            'filearea' => CommerceStorefrontContentFileService::FILEAREA,
            'itemid' => $itemid,
            'filepath' => '/image/',
            'filename' => 'hero.jpg',
        ], 'image-content');

        $source = new CommerceProduct(
            'J87-SOURCE',
            CommerceProductType::COURSE_ACCESS,
            CommerceProductStatus::ACTIVE,
            'Source',
            '',
            ['storefront' => [
                'template' => 'default',
                'locales' => [
                    'fr' => ['sections' => [[
                        'id' => 'hero-image',
                        'type' => 'image_text',
                        'mediaitemid' => $itemid,
                        'content' => '<p>Bonjour</p>',
                    ]]],
                ],
            ]]
        );
        $target = new CommerceProduct(
            'J87-TARGET',
            CommerceProductType::COURSE_ACCESS,
            CommerceProductStatus::ACTIVE,
            'Target',
            '',
            ['kept' => true]
        );

        $service = CommerceStorefrontPackageService::create();
        $package = $service->export($source);
        $metadata = $service->import($package, $target);

        $this->assertTrue($metadata['kept']);
        $section = $metadata['storefront']['locales']['fr']['sections'][0];
        $this->assertNotSame($itemid, (int)$section['mediaitemid']);
        $file = get_file_storage()->get_file(
            $context->id,
            CommerceStorefrontContentFileService::COMPONENT,
            CommerceStorefrontContentFileService::FILEAREA,
            (int)$section['mediaitemid'],
            '/image/',
            'hero.jpg'
        );
        $this->assertNotFalse($file);
        $this->assertSame('image-content', $file->get_content());
    }

    public function test_admin_exposes_versioned_portable_package_actions(): void {
        $source = file_get_contents(__DIR__ . '/../../../admin/commerce/products/storefront.php');
        $this->assertStringContainsString("'storefront_action' => 'export'", $source);
        $this->assertStringContainsString("'name' => 'storefront_action', 'value' => 'import'", str_replace(["\n", "\r"], '', $source));
        $this->assertStringContainsString('.cfrproduct', $source);
    }
}
