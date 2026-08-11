<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use coding_exception;
use local_subscriptions\commerce\digital\library\CommerceDigitalDownloadPresentation;
use local_subscriptions\commerce\digital\library\CommerceDigitalLibrary;
use local_subscriptions\commerce\digital\library\CommerceDigitalResourcePresentation;

/** @coversDefaultClass \local_subscriptions\commerce\digital\library\CommerceDigitalLibrary */
final class commerce_digital_library_read_model_test extends advanced_testcase {
    public function test_library_exposes_product_and_download_counts(): void {
        $resource = new CommerceDigitalResourcePresentation(
            'product:sku:digital.guide',
            'Guide CampusFR',
            'native',
            '/local/subscriptions/storefront_product.php?sku=DIGITAL.GUIDE',
            null,
            null,
            1700000000,
            [new CommerceDigitalDownloadPresentation(
                'Version classique',
                '/download.php',
                'desktop',
                true,
                'guide.pdf',
                'pdf',
                2048,
                1,
                1700000100
            )],
            false,
            [],
            '/local/subscriptions/pix/cover/guide.png'
        );
        $library = new CommerceDigitalLibrary([$resource], 42, 'client@example.test');

        $this->assertFalse($library->is_empty());
        $this->assertSame(1, $library->count());
        $this->assertSame(1, $library->count_downloadable_resources());
        $this->assertSame(42, $library->get_userid());
        $this->assertSame('client@example.test', $library->get_customeremail());

        $export = $resource->export();
        $this->assertTrue($export['hascover']);
        $this->assertArrayNotHasKey('orderurl', $export);
        $this->assertArrayNotHasKey('purchasedate', $export);
        $this->assertArrayNotHasKey('source', $export);
    }

    public function test_download_exports_file_information_and_history(): void {
        $download = new CommerceDigitalDownloadPresentation(
            'Version mobile',
            '/download.php?version=mobile',
            'mobile',
            true,
            'guide-mobile.pdf',
            'pdf',
            4096,
            2,
            1700000000
        );

        $export = $download->export();

        $this->assertTrue($export['ismobile']);
        $this->assertSame('guide-mobile.pdf', $export['filename']);
        $this->assertSame('PDF', $export['filetype']);
        $this->assertSame(4096, $export['filesize']);
        $this->assertTrue($export['hasdownloadhistory']);
        $this->assertTrue($export['hasbeendownloaded']);
        $this->assertSame(2, $export['downloadcount']);
        $this->assertTrue($export['haslastdownload']);
        $this->assertSame('mobile', $export['assetkey']);
    }


    public function test_legacy_download_exports_explicitly_unavailable_history(): void {
        $download = new CommerceDigitalDownloadPresentation(
            'Version classique',
            '/download.php',
            'desktop',
            true,
            'guide.pdf',
            'pdf',
            2048,
            null,
            null,
            ['historyavailable' => false, 'assetkey' => 'desktop']
        );

        $export = $download->export();

        $this->assertFalse($export['historyavailable']);
        $this->assertTrue($export['historyunavailable']);
        $this->assertFalse($export['hasdownloadhistory']);
        $this->assertFalse($export['hasbeendownloaded']);
        $this->assertSame('desktop', $export['assetkey']);
    }

    public function test_unavailable_download_is_not_exported(): void {
        $resource = new CommerceDigitalResourcePresentation(
            'product:legacy:12',
            'PDF CampusFR',
            'legacy',
            null,
            null,
            null,
            0,
            [new CommerceDigitalDownloadPresentation('Indisponible', '/download.php', 'default', false)]
        );

        $export = $resource->export();

        $this->assertFalse($resource->has_downloads());
        $this->assertFalse($export['hasdownloads']);
        $this->assertSame(0, $export['downloadcount']);
        $this->assertSame([], $export['downloads']);
    }

    public function test_resource_source_is_strictly_validated(): void {
        $this->expectException(coding_exception::class);

        new CommerceDigitalResourcePresentation(
            'resource:test',
            'Ressource',
            'unknown',
            null,
            null,
            null,
            0,
            []
        );
    }

    public function test_download_variant_is_strictly_validated(): void {
        $this->expectException(coding_exception::class);

        new CommerceDigitalDownloadPresentation('Télécharger', '/download.php', 'tablet');
    }
}
