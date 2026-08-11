<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomPackageService;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomPreviewDefinitionResolver;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomPublicationService;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomPublishedDefinitionResolver;

final class commerce_showroom_cms_autonomous_j16s10_test extends \advanced_testcase {
    public function test_imported_clone_with_new_key_is_previewable_without_registry_entry(): void {
        global $DB;
        $this->resetAfterTest(true);

        $repository = new CommerceShowroomCmsRepository($DB);

        // Occupy the original registry/CMS key so import must create "-2".
        $repository->save([
            'showroomkey' => 'third-group-verbs',
            'status' => 'draft',
            'name' => 'Original',
            'template' => 'local_subscriptions/showroom/third_group_verbs',
            'slugfr' => 'verbes-3e-groupe',
            'slugen' => 'third-group-verbs',
            'slugru' => 'glagoly-tretey-gruppy',
            'productsjson' => '{}',
            'settingsjson' => '{}',
        ], 2);

        $package = [
            'format' => 'campusfr-showroom',
            'version' => 2,
            'showroom' => [
                'showroomkey' => 'third-group-verbs',
                'status' => 'published',
                'name' => 'Imported',
                'template' => 'local_subscriptions/showroom/third_group_verbs',
                'titlekey' => '',
                'descriptionkey' => '',
                'productsjson' => '{}',
                'settingsjson' => '{}',
            ],
            'blocks' => [
                [
                    'blockkey' => 'hero',
                    'blocktype' => 'hero',
                    'sortorder' => 10,
                    'enabled' => true,
                    'config' => [
                        'title' => 'Imported hero',
                    ],
                ],
            ],
        ];

        $id = (new CommerceShowroomPackageService($repository))
            ->import(
                json_encode(
                    $package,
                    JSON_UNESCAPED_UNICODE
                        | JSON_UNESCAPED_SLASHES
                ),
                2
            );

        $record = $repository->get($id);
        self::assertNotNull($record);
        self::assertSame(
            'third-group-verbs-2',
            (string)$record->showroomkey
        );
        self::assertSame('', (string)$record->slugfr);
        self::assertSame('', (string)$record->slugen);
        self::assertSame('', (string)$record->slugru);

        $definition = (
            new CommerceShowroomPreviewDefinitionResolver($DB)
        )->require($id);

        self::assertSame(
            'third-group-verbs-2',
            $definition->get_key()
        );
        self::assertSame(
            'local_subscriptions/showroom/third_group_verbs',
            $definition->get_template()
        );
        self::assertSame(
            'commerce_showroom_third_group_verbs_title',
            $definition->get_title_key()
        );
    }

    public function test_autonomous_cms_showroom_can_be_published_after_slug_is_set(): void {
        global $DB;
        $this->resetAfterTest(true);

        $repository = new CommerceShowroomCmsRepository($DB);
        $id = $repository->save([
            'showroomkey' => 'autonomous-showroom',
            'status' => 'draft',
            'name' => 'Autonomous',
            'template' => 'local_subscriptions/showroom/third_group_verbs',
            'slugfr' => '',
            'slugen' => '',
            'slugru' => '',
            'productsjson' => '{}',
            'settingsjson' => '{}',
        ], 2);

        $repository->save_block($id, [
            'blocktype' => 'hero',
            'blockkey' => 'hero',
            'sortorder' => 10,
            'enabled' => true,
            'configjson' => '{}',
        ], 2);

        $publication = new CommerceShowroomPublicationService(
            $DB,
            $repository
        );
        $publication->submit_for_review($id, 2);

        try {
            $publication->publish($id, 2);
            self::fail('Publishing without a public slug must fail.');
        } catch (\moodle_exception $exception) {
            self::assertSame(
                'commerce_showroom_publish_requires_slug',
                $exception->errorcode
            );
        }

        $record = (array)$repository->get($id);
        $record['slugfr'] = 'autonomous-showroom';
        $repository->save($record, 2);

        $publication->publish($id, 2);

        $definition = (
            new CommerceShowroomPublishedDefinitionResolver($DB)
        )->require('autonomous-showroom');

        self::assertSame(
            'autonomous-showroom',
            $definition->get_key()
        );
        self::assertSame(
            'autonomous-showroom',
            $definition->get_slug('fr')
        );
    }

    public function test_published_resolver_does_not_require_registry_key(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/showroom/cms/'
            . 'CommerceShowroomPublishedDefinitionResolver.php'
        );

        self::assertStringContainsString(
            'CommerceShowroomRegistry::get($showroomkey)',
            $source
        );
        self::assertStringNotContainsString(
            'CommerceShowroomRegistry::require($showroomkey)',
            $source
        );
    }

    public function test_preview_resolver_has_no_registry_dependency(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/showroom/cms/'
            . 'CommerceShowroomPreviewDefinitionResolver.php'
        );

        self::assertStringNotContainsString(
            'CommerceShowroomRegistry',
            $source
        );
        self::assertStringContainsString(
            '->create($record);',
            $source
        );
    }
}
