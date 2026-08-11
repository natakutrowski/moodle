<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockMediaManager;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomPortablePackageService;

final class commerce_showroom_portable_package_j16s8_test extends \advanced_testcase {
    public function test_portable_round_trip_copies_media_and_remaps_urls(): void {
        global $DB;

        if (!class_exists(\ZipArchive::class)) {
            $this->markTestSkipped('ZipArchive extension is not available.');
        }

        $this->resetAfterTest(true);
        $context = \context_system::instance();
        $repository = new CommerceShowroomCmsRepository($DB);

        $showroomid = $repository->save([
            'showroomkey' => 'third-group-verbs',
            'status' => 'published',
            'name' => 'Portable source',
            'template' => 'local_subscriptions/showroom/third_group_verbs',
            'productsjson' => '{}',
            'settingsjson' => '{}',
        ], 2);

        $blockid = $repository->save_block($showroomid, [
            'blocktype' => 'hero',
            'blockkey' => 'hero',
            'sortorder' => 10,
            'enabled' => true,
            'configjson' => '{}',
        ], 2);

        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwC'
            . 'AAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='
        );

        $stored = get_file_storage()->create_file_from_string([
            'contextid' => $context->id,
            'component' => CommerceShowroomBlockMediaManager::COMPONENT,
            'filearea' => CommerceShowroomBlockMediaManager::FILEAREA,
            'itemid' => $blockid,
            'filepath' => '/backgroundurl/',
            'filename' => 'backgroundurl.png',
        ], $png);

        $sourceurl = \moodle_url::make_pluginfile_url(
            $context->id,
            CommerceShowroomBlockMediaManager::COMPONENT,
            CommerceShowroomBlockMediaManager::FILEAREA,
            $blockid,
            $stored->get_filepath(),
            $stored->get_filename(),
            false
        )->out(false);

        $repository->save_block($showroomid, [
            'id' => $blockid,
            'blocktype' => 'hero',
            'blockkey' => 'hero',
            'sortorder' => 10,
            'enabled' => true,
            'configjson' => json_encode([
                'backgroundurl' => $sourceurl,
                'translations' => [
                    'ru' => ['title' => 'Тест'],
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ], 2);

        $service = new CommerceShowroomPortablePackageService(
            $DB,
            $repository,
            $context
        );

        $export = $service->export_zip($showroomid);
        self::assertFileExists($export['pathname']);
        self::assertSame(1, $export['mediacount']);

        $zip = new \ZipArchive();
        self::assertTrue($zip->open($export['pathname']) === true);
        $manifest = json_decode(
            (string)$zip->getFromName('showroom.json'),
            true
        );
        $zip->close();

        self::assertTrue($manifest['media']['included']);
        self::assertCount(1, $manifest['media']['files']);

        $report = $service->import_zip($export['pathname'], 2);

        self::assertSame(1, $report['blockcount']);
        self::assertSame(1, $report['mediacount']);
        self::assertSame(1, $report['remappedcount']);
        self::assertSame(0, $report['unresolvedcount']);

        $blocks = $repository->blocks($report['showroomid']);
        self::assertCount(1, $blocks);
        $targetblock = $blocks[0];

        self::assertNotSame($blockid, (int)$targetblock->id);

        $config = json_decode($targetblock->configjson, true);
        self::assertStringNotContainsString(
            '/showroom_block_media/' . $blockid . '/',
            $config['backgroundurl']
        );
        self::assertStringContainsString(
            '/showroom_block_media/' . (int)$targetblock->id . '/',
            $config['backgroundurl']
        );
        self::assertSame('Тест', $config['translations']['ru']['title']);

        $targetfiles = get_file_storage()->get_area_files(
            $context->id,
            CommerceShowroomBlockMediaManager::COMPONENT,
            CommerceShowroomBlockMediaManager::FILEAREA,
            (int)$targetblock->id,
            'id ASC',
            false
        );
        self::assertCount(1, $targetfiles);
        self::assertSame(
            $stored->get_contenthash(),
            reset($targetfiles)->get_contenthash()
        );
    }

    public function test_portable_import_rejects_path_traversal_before_creation(): void {
        global $DB;

        if (!class_exists(\ZipArchive::class)) {
            $this->markTestSkipped('ZipArchive extension is not available.');
        }

        $this->resetAfterTest(true);
        $repository = new CommerceShowroomCmsRepository($DB);
        $context = \context_system::instance();

        $tmp = make_request_directory(false, true)
            . '/unsafe.showroom.zip';
        $zip = new \ZipArchive();
        self::assertTrue(
            $zip->open(
                $tmp,
                \ZipArchive::CREATE | \ZipArchive::OVERWRITE
            ) === true
        );
        $zip->addFromString('showroom.json', '{}');
        $zip->addFromString('../evil.png', 'evil');
        $zip->close();

        $before = $DB->count_records('local_subs_showroom');

        try {
            (new CommerceShowroomPortablePackageService(
                $DB,
                $repository,
                $context
            ))->import_zip($tmp, 2);
            self::fail('Unsafe ZIP path must be rejected.');
        } catch (\invalid_parameter_exception) {
            self::assertSame(
                $before,
                $DB->count_records('local_subs_showroom')
            );
        }
    }

    public function test_builder_exposes_portable_export_and_zip_import(): void {
        global $CFG;

        $root = $CFG->dirroot . '/local/subscriptions/';
        $edit = file_get_contents(
            $root . 'admin/commerce/showrooms/edit.php'
        );
        $import = file_get_contents(
            $root . 'admin/commerce/showrooms/import.php'
        );
        $export = file_get_contents(
            $root . 'admin/commerce/showrooms/export_portable.php'
        );

        self::assertStringContainsString(
            '/admin/commerce/showrooms/export_portable_preflight.php',
            $edit
        );
        self::assertStringContainsString(
            'commerce_showroom_export_portable',
            $edit
        );
        self::assertStringContainsString(
            'send_temp_file(',
            $export
        );
        self::assertStringContainsString(
            "if (\$extension === 'zip')",
            $import
        );
        self::assertStringContainsString(
            'CommerceShowroomPortablePackageService',
            $import
        );
        self::assertStringContainsString(
            'send_file(',
            $export
        );
    }
}
