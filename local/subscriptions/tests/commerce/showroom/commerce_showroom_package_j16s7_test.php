<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomPackageService;

final class commerce_showroom_package_j16s7_test extends \advanced_testcase {
    public function test_v2_export_contains_every_block_as_structured_config(): void {
        global $DB;
        $this->resetAfterTest(true);

        $repository = new CommerceShowroomCmsRepository($DB);
        $showroomid = $repository->save([
            'showroomkey' => 'third-group-verbs',
            'status' => 'published',
            'name' => 'Verbs',
            'template' => 'local_subscriptions/showroom/third_group_verbs',
            'slugfr' => 'showroom-fr',
            'slugen' => 'showroom-en',
            'slugru' => 'showroom-ru',
            'productsjson' => '{"course":"COURSE.TEST"}',
            'settingsjson' => '{"seo":{"fr":{"title":"SEO FR"}}}',
        ], 2);

        $configs = [
            [
                'blocktype' => 'hero',
                'sortorder' => 10,
                'config' => [
                    'title' => 'Hero FR',
                    'translations' => [
                        'ru' => ['title' => 'Герой'],
                        'en' => ['title' => 'Hero EN'],
                    ],
                    'backgroundopacity' => '80',
                ],
            ],
            [
                'blocktype' => 'verbs_cards',
                'sortorder' => 20,
                'config' => [
                    'title' => 'Cartes',
                    'items' => "A|B|fa-star\nC|D|fa-list",
                    'customcolor' => '#fff0f7',
                ],
            ],
        ];

        foreach ($configs as $block) {
            $repository->save_block($showroomid, [
                'blocktype' => $block['blocktype'],
                'sortorder' => $block['sortorder'],
                'enabled' => true,
                'configjson' => json_encode(
                    $block['config'],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ),
            ], 2);
        }

        $service = new CommerceShowroomPackageService($repository);
        $package = $service->export($showroomid);

        self::assertSame('campusfr-showroom', $package['format']);
        self::assertSame(2, $package['version']);
        self::assertSame('draft', $package['showroom']['status']);
        self::assertCount(2, $package['blocks']);

        self::assertIsArray($package['blocks'][0]['config']);
        self::assertSame(
            'Герой',
            $package['blocks'][0]['config']['translations']['ru']['title']
        );
        self::assertSame(
            '#fff0f7',
            $package['blocks'][1]['config']['customcolor']
        );
        self::assertArrayNotHasKey('configjson', $package['blocks'][0]);

        self::assertFalse($package['media']['included']);
    }

    public function test_v2_round_trip_preserves_all_block_configs_and_order(): void {
        global $DB;
        $this->resetAfterTest(true);

        $repository = new CommerceShowroomCmsRepository($DB);
        $sourceid = $repository->save([
            'showroomkey' => 'third-group-verbs',
            'status' => 'published',
            'name' => 'Source',
            'template' => 'local_subscriptions/showroom/third_group_verbs',
            'slugfr' => 'fr-source',
            'slugen' => 'en-source',
            'slugru' => 'ru-source',
            'productsjson' => '{"course":"COURSE.TEST","pdf":"DIGITAL.TEST"}',
            'settingsjson' => '{"foo":{"bar":"baz"}}',
        ], 2);

        $expected = [
            [
                'blocktype' => 'hero',
                'sortorder' => 10,
                'enabled' => true,
                'config' => [
                    'title' => 'A',
                    'translations' => [
                        'fr' => ['title' => 'FR'],
                        'en' => ['title' => 'EN'],
                        'ru' => ['title' => 'RU'],
                    ],
                ],
            ],
            [
                'blocktype' => 'final_cta',
                'sortorder' => 30,
                'enabled' => false,
                'config' => [
                    'title' => 'CTA',
                    'legalshowname' => true,
                    'backgroundblur' => '3',
                ],
            ],
        ];

        foreach ($expected as $block) {
            $repository->save_block($sourceid, [
                'blocktype' => $block['blocktype'],
                'sortorder' => $block['sortorder'],
                'enabled' => $block['enabled'],
                'configjson' => json_encode(
                    $block['config'],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ),
            ], 2);
        }

        $service = new CommerceShowroomPackageService($repository);
        $json = json_encode(
            $service->export($sourceid),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        $importedid = $service->import($json, 2);
        $imported = $repository->get($importedid);

        self::assertNotNull($imported);
        self::assertSame('draft', $imported->status);
        self::assertSame('', $imported->slugfr);
        self::assertSame('', $imported->slugen);
        self::assertSame('', $imported->slugru);
        self::assertSame(
            '{"foo":{"bar":"baz"}}',
            $imported->settingsjson
        );
        self::assertSame(
            '{"course":"COURSE.TEST","pdf":"DIGITAL.TEST"}',
            $imported->productsjson
        );

        $blocks = $repository->blocks($importedid);
        self::assertCount(2, $blocks);

        foreach ($expected as $index => $expectedblock) {
            self::assertSame(
                $expectedblock['blocktype'],
                $blocks[$index]->blocktype
            );
            self::assertSame(
                $expectedblock['sortorder'],
                (int)$blocks[$index]->sortorder
            );
            self::assertSame(
                $expectedblock['enabled'],
                (int)$blocks[$index]->enabled === 1
            );
            self::assertSame(
                $expectedblock['config'],
                json_decode($blocks[$index]->configjson, true)
            );
        }
    }

    public function test_v1_package_remains_importable(): void {
        global $DB;
        $this->resetAfterTest(true);

        $repository = new CommerceShowroomCmsRepository($DB);
        $service = new CommerceShowroomPackageService($repository);

        $package = [
            'format' => 'campusfr-showroom',
            'version' => 1,
            'showroom' => [
                'showroomkey' => 'legacy-import',
                'status' => 'published',
                'name' => 'Legacy',
                'template' => 'local_subscriptions/showroom/third_group_verbs',
                'productsjson' => '{}',
                'settingsjson' => '{}',
            ],
            'blocks' => [
                [
                    'blocktype' => 'hero',
                    'sortorder' => 10,
                    'enabled' => 1,
                    'configjson' => '{"title":"Legacy hero"}',
                ],
            ],
        ];

        $id = $service->import(json_encode($package), 2);

        self::assertSame('draft', $repository->get($id)->status);
        $blocks = $repository->blocks($id);
        self::assertCount(1, $blocks);
        self::assertSame(
            ['title' => 'Legacy hero'],
            json_decode($blocks[0]->configjson, true)
        );
    }

    public function test_unknown_block_type_aborts_import_instead_of_partial_copy(): void {
        global $DB;
        $this->resetAfterTest(true);

        $service = new CommerceShowroomPackageService(
            new CommerceShowroomCmsRepository($DB)
        );

        $package = [
            'format' => 'campusfr-showroom',
            'version' => 2,
            'showroom' => [
                'showroomkey' => 'invalid',
                'name' => 'Invalid',
                'template' => 'local_subscriptions/showroom/third_group_verbs',
            ],
            'blocks' => [
                [
                    'blocktype' => 'not_a_real_block',
                    'config' => [],
                ],
            ],
        ];

        $this->expectException(\invalid_parameter_exception::class);
        $service->import(json_encode($package), 2);
    }

    public function test_main_page_exposes_create_from_json_import(): void {
        global $CFG;

        $root = $CFG->dirroot . '/local/subscriptions/';
        $index = file_get_contents(
            $root . 'admin/commerce/showrooms/index.php'
        );
        $import = file_get_contents(
            $root . 'admin/commerce/showrooms/import.php'
        );

        self::assertStringContainsString(
            'commerce_showroom_import_create',
            $index
        );
        self::assertStringContainsString(
            '/admin/commerce/showrooms/import.php',
            $index
        );
        self::assertStringContainsString(
            "'enctype' => 'multipart/form-data'",
            $import
        );
        self::assertStringContainsString(
            "'name' => 'packagefile'",
            $import
        );
        self::assertStringContainsString(
            "if (\$extension === 'zip')",
            $import
        );
        self::assertStringContainsString(
            "if (\$extension !== 'json')",
            $import
        );
        self::assertStringContainsString(
            'MAX_IMPORT_BYTES',
            $import
        );
        self::assertStringContainsString(
            'CommerceShowroomPortablePackageService',
            $import
        );
        self::assertStringContainsString(
            "'.zip,.json,application/zip,application/json'",
            $import
        );
    }
}
