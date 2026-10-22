<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockEditorRegistry;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomRuntimeBlockSet;

/** @covers \local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter */
final class commerce_showroom_a1_journey_map_798e10_test extends \advanced_testcase {
    public function test_e810_projects_validated_coordinates_and_preserves_old_map(): void {
        global $DB;
        $this->resetAfterTest(true);

        $repository = new CommerceShowroomCmsRepository($DB);
        $showroomid = $repository->save([
            'showroomkey' => 'a1-e810-coordinate-test',
            'status' => 'draft',
            'name' => 'A1 E8.10 geographic test',
            'template' => 'local_subscriptions/showroom/default',
            'slugfr' => '', 'slugen' => '', 'slugru' => '',
            'titlekey' => '', 'descriptionkey' => '',
            'productsjson' => '{}', 'settingsjson' => '{}',
        ], 2);
        $repository->save_block($showroomid, [
            'blocktype' => 'journey_map',
            'sortorder' => 20,
            'enabled' => true,
            'configjson' => json_encode([
                'presentation' => 'interactive_a1',
                'geopoints' => implode("\n", [
                    '01|Nice|43.70094|7.26839|CampusFR_A1_01.jpg',
                    '15|Corse|41.38720|9.15910|CampusFR_A1_15.jpg',
                    // A path injection and a duplicate number must be rejected.
                    '30|Wrong|44.9|6.6|../../evil.jpg',
                    '01|Duplicate|44|6|CampusFR_A1_01.jpg',
                ]),
                'translations' => [
                    'fr' => ['captions' => "01|Jour 1 · Bonjour\n15|Jour 15 · La Corse"],
                    'ru' => ['captions' => "01|День 1 · Познакомимся?\n15|День 15 · Море"],
                ],
            ], JSON_THROW_ON_ERROR),
        ], 2);

        $blocks = CommerceShowroomRuntimeBlockSet::load_preview($DB, $showroomid);
        $data = (new CommerceShowroomBlockConfigurationPresenter())->apply([], $blocks);
        self::assertTrue($data['journeymapinteractive']);
        self::assertTrue($data['hasjourneymapgeopoints']);
        self::assertCount(2, $data['journeymapgeopoints']);
        self::assertSame('01', $data['journeymapgeopoints'][0]['number']);
        self::assertSame('Nice', $data['journeymapgeopoints'][0]['place']);
        self::assertEqualsWithDelta(58.0998, (float)$data['journeymapgeopoints'][0]['x'], 0.001);
        self::assertEqualsWithDelta(38.1811, (float)$data['journeymapgeopoints'][0]['y'], 0.001);
        self::assertStringContainsString('CampusFR_A1_15.jpg', $data['journeymapgeopoints'][1]['imageurl']);
        self::assertStringNotContainsString('evil.jpg', json_encode($data['journeymapgeopoints']));
    }

    public function test_e810_editor_and_media_assets_exist(): void {
        global $CFG;
        $names = array_column(CommerceShowroomBlockEditorRegistry::schema('journey_map')['fields'], 'name');
        foreach (['presentation', 'geopoints', 'stops', 'captions', 'counttitle', 'hint'] as $field) {
            self::assertContains($field, $names);
        }
        $folder = $CFG->dirroot . '/local/subscriptions/pix/journey_map';
        self::assertFileExists($folder . '/paca_corse_georeferenced.png');
        foreach (range(1, 30) as $number) {
            self::assertFileExists(sprintf('%s/photos/CampusFR_A1_%02d.jpg', $folder, $number));
        }
        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/block_journey_map.mustache');
        self::assertStringContainsString('data-showroom-geo-map', $template);
        self::assertStringContainsString('{{^journeymapinteractive}}', $template);
    }
}
