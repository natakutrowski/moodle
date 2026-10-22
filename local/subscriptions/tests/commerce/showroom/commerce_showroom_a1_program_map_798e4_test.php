<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\a1\CommerceA1ShowroomFoundationService;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockEditorRegistry;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomRuntimeBlockSet;

/** @covers \local_subscriptions\commerce\showroom\a1\CommerceA1ShowroomFoundationService */
final class commerce_showroom_a1_program_map_798e4_test extends \advanced_testcase {
    public function test_e4_seeds_ppt_programme_map_and_programme_anchor(): void {
        global $DB;
        $this->resetAfterTest(true);

        $service = CommerceA1ShowroomFoundationService::create($DB);
        $service->ensure(null);
        $service->ensure_builder_structure(null);
        $service->apply_editorial_seed_v1(null);
        $result = $service->apply_program_map_seed_v1(null);

        self::assertSame(2, $result['seeded']);
        self::assertSame(0, $result['preserved']);
        self::assertSame(1, $result['anchorupdated']);

        $repo = new CommerceShowroomCmsRepository($DB);
        $showroom = $repo->get_by_key(CommerceA1ShowroomFoundationService::SHOWROOM_KEY);
        self::assertNotNull($showroom);
        self::assertSame('draft', (string)$showroom->status);

        $bytype = [];
        foreach ($repo->blocks((int)$showroom->id) as $block) {
            $bytype[(string)$block->blocktype] = $block;
        }

        foreach (['curriculum_tabs', 'journey_map'] as $type) {
            self::assertSame(1, (int)$bytype[$type]->enabled, $type . ' should be enabled by E4');
        }
        // E5/E6 content stays deliberately untouched.
        self::assertSame(0, (int)$bytype['preparation_flow']->enabled);
        self::assertSame(0, (int)$bytype['testimonials']->enabled);

        $curriculum = json_decode(
            (string)$bytype['curriculum_tabs']->configjson,
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        $ru = $curriculum['translations']['ru'];
        self::assertSame('Что ты изучишь —', $ru['title']);
        self::assertSame('программа A1', $ru['titleaccent']);
        self::assertSame('Темы курса', $ru['tab1label']);
        self::assertSame('Грамматика A1', $ru['tab2label']);
        self::assertSame('', $ru['grammar']);
        $topiclines = preg_split('/\R/u', trim((string)$ru['topics']), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        self::assertCount(30, $topiclines);
        self::assertStringContainsString('Первые шаги|fa-solid fa-people-group|01|Знакомство', $ru['topics']);
        self::assertStringContainsString('Уже увереннее|fa-regular fa-star|30|Финальное приключение', $ru['topics']);

        $map = json_decode(
            (string)$bytype['journey_map']->configjson,
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        $mapru = $map['translations']['ru'];
        $stoplines = preg_split('/\R/u', trim((string)$mapru['stops']), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        self::assertCount(9, $stoplines);
        self::assertContains('Avignon|Прованс', $stoplines);
        self::assertContains('Bonifacio|Корсика', $stoplines);
        self::assertStringContainsString('Лазурный берег|Nice, Antibes, Cannes', $mapru['items']);
        self::assertStringContainsString('Свободное время|Небольшие расслабленные разделы', $mapru['items']);

        $anchor = json_decode(
            (string)$bytype['anchor_nav']->configjson,
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        self::assertStringContainsString('Программа|#showroom-program', $anchor['translations']['ru']['items']);
    }

    public function test_e4_preserves_builder_edits_and_only_seeds_remaining_empty_block(): void {
        global $DB;
        $this->resetAfterTest(true);

        $service = CommerceA1ShowroomFoundationService::create($DB);
        $service->ensure(null);
        $service->ensure_builder_structure(null);
        $service->apply_editorial_seed_v1(null);

        $repo = new CommerceShowroomCmsRepository($DB);
        $showroom = $repo->get_by_key(CommerceA1ShowroomFoundationService::SHOWROOM_KEY);
        self::assertNotNull($showroom);
        $bytype = [];
        foreach ($repo->blocks((int)$showroom->id) as $block) {
            $bytype[(string)$block->blocktype] = $block;
        }

        $repo->save_block((int)$showroom->id, [
            'id' => (int)$bytype['curriculum_tabs']->id,
            'blocktype' => 'curriculum_tabs',
            'blockkey' => (string)$bytype['curriculum_tabs']->blockkey,
            'sortorder' => (int)$bytype['curriculum_tabs']->sortorder,
            'enabled' => false,
            'configjson' => json_encode(['title' => 'Programme personnalisé'], JSON_THROW_ON_ERROR),
        ], 2);
        $repo->save_block((int)$showroom->id, [
            'id' => (int)$bytype['anchor_nav']->id,
            'blocktype' => 'anchor_nav',
            'blockkey' => (string)$bytype['anchor_nav']->blockkey,
            'sortorder' => (int)$bytype['anchor_nav']->sortorder,
            'enabled' => true,
            'configjson' => json_encode(['items' => 'Mon menu|#custom'], JSON_THROW_ON_ERROR),
        ], 2);

        $result = $service->apply_program_map_seed_v1(2);
        self::assertSame(1, $result['seeded']);
        self::assertSame(1, $result['preserved']);
        self::assertSame(0, $result['anchorupdated']);

        $fresh = [];
        foreach ($repo->blocks((int)$showroom->id) as $block) {
            $fresh[(string)$block->blocktype] = $block;
        }
        self::assertSame(
            ['title' => 'Programme personnalisé'],
            json_decode((string)$fresh['curriculum_tabs']->configjson, true, 512, JSON_THROW_ON_ERROR)
        );
        self::assertSame(0, (int)$fresh['curriculum_tabs']->enabled);
        self::assertSame(
            ['items' => 'Mon menu|#custom'],
            json_decode((string)$fresh['anchor_nav']->configjson, true, 512, JSON_THROW_ON_ERROR)
        );
        self::assertSame(1, (int)$fresh['journey_map']->enabled);

        $second = $service->apply_program_map_seed_v1(2);
        self::assertSame(0, $second['seeded']);
        self::assertSame(2, $second['preserved']);
        self::assertSame(0, $second['anchorupdated']);
    }

    public function test_runtime_presenter_maps_route_stops_and_keeps_unknown_grammar_hidden(): void {
        global $DB;
        $this->resetAfterTest(true);

        $repo = new CommerceShowroomCmsRepository($DB);
        $showroomid = $repo->save([
            'showroomkey' => 'a1-e4-runtime',
            'status' => 'draft',
            'name' => 'A1 E4 runtime',
            'template' => 'local_subscriptions/showroom/default',
            'slugfr' => '', 'slugen' => '', 'slugru' => '',
            'titlekey' => '', 'descriptionkey' => '',
            'productsjson' => '{}', 'settingsjson' => '{}',
        ], 2);

        $repo->save_block($showroomid, [
            'blocktype' => 'curriculum_tabs',
            'sortorder' => 10,
            'enabled' => true,
            'configjson' => json_encode([
                'tab1label' => 'Темы курса',
                'tab2label' => 'Грамматика A1',
                'topics' => "Первые шаги|fa-users|01|Знакомство\nПервые шаги|fa-users|02|Внешность",
                'grammar' => '',
                'defaulttab' => 'topics',
            ], JSON_THROW_ON_ERROR),
        ], 2);
        $repo->save_block($showroomid, [
            'blocktype' => 'journey_map',
            'sortorder' => 20,
            'enabled' => true,
            'configjson' => json_encode([
                'stops' => "Avignon|Прованс\nAix-en-Provence|Прованс\nBonifacio|Корсика",
                'items' => 'Прованс|Marseille, Aix, Avignon|fa-seedling',
            ], JSON_THROW_ON_ERROR),
        ], 2);

        $blocks = CommerceShowroomRuntimeBlockSet::load_preview($DB, $showroomid);
        $data = (new CommerceShowroomBlockConfigurationPresenter())->apply([], $blocks);

        self::assertCount(1, $data['curriculumtopicsgroups']);
        self::assertFalse($data['hascurriculumgrammar']);
        self::assertTrue($data['curriculumtopicsactive']);
        self::assertCount(3, $data['journeymapstops']);
        self::assertSame('Avignon', $data['journeymapstops'][0]['place']);
        self::assertTrue($data['journeymapstops'][0]['hasarrow']);
        self::assertSame('Корсика', $data['journeymapstops'][2]['region']);
        self::assertFalse($data['journeymapstops'][2]['hasarrow']);
        self::assertTrue($data['hasjourneymapstops']);
    }

    public function test_builder_and_public_template_expose_generic_route_stops(): void {
        global $CFG;

        $schemas = CommerceShowroomBlockEditorRegistry::schemas(['course']);
        self::assertArrayHasKey('journey_map', $schemas);
        $names = array_map(
            static fn(array $field): string => (string)($field['name'] ?? ''),
            $schemas['journey_map']['fields']
        );
        self::assertContains('stops', $names);

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/showroom/block_journey_map.mustache'
        );
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        self::assertIsString($template);
        self::assertStringContainsString('{{#hasjourneymapstops}}', $template);
        self::assertStringContainsString('{{#journeymapstops}}', $template);
        self::assertStringContainsString('commerce-showroom-journey-map__route', $template);
        self::assertIsString($css);
        self::assertStringContainsString('.commerce-showroom-journey-map__route', $css);
    }
}
