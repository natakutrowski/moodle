<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\a1\CommerceA1ShowroomFoundationService;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;

/** @covers \local_subscriptions\commerce\showroom\a1\CommerceA1ShowroomFoundationService */
final class commerce_showroom_a1_editorial_seed_798e3_test extends \advanced_testcase {
    public function test_seed_populates_ppt_sections_and_keeps_future_sections_disabled(): void {
        global $DB;
        $this->resetAfterTest(true);

        $service = CommerceA1ShowroomFoundationService::create($DB);
        $service->ensure(null);
        $service->ensure_builder_structure(null);
        $result = $service->apply_editorial_seed_v1(null);

        self::assertGreaterThanOrEqual(10, $result['seeded']);
        self::assertSame(1, $result['created']);

        $repo = new CommerceShowroomCmsRepository($DB);
        $showroom = $repo->get_by_key(CommerceA1ShowroomFoundationService::SHOWROOM_KEY);
        self::assertNotNull($showroom);
        self::assertSame('draft', $showroom->status);

        $blocks = $repo->blocks((int)$showroom->id);
        $bytype = [];
        foreach ($blocks as $block) {
            $bytype[(string)$block->blocktype] = $block;
        }

        foreach ([
            'hero', 'stats', 'anchor_nav', 'audience_profiles', 'adventure_overview',
            'content_highlights', 'community_clan', 'tutor_chat', 'support', 'video',
        ] as $type) {
            self::assertArrayHasKey($type, $bytype);
            self::assertSame(1, (int)$bytype[$type]->enabled, $type . ' should be enabled');
            $config = json_decode((string)$bytype[$type]->configjson, true, 512, JSON_THROW_ON_ERROR);
            self::assertArrayHasKey('translations', $config);
            self::assertArrayHasKey('ru', $config['translations']);
        }

        foreach (['curriculum_tabs', 'journey_map', 'preparation_flow', 'testimonials'] as $type) {
            self::assertSame(0, (int)$bytype[$type]->enabled, $type . ' remains for E4/E5/E6');
        }

        $hero = json_decode((string)$bytype['hero']->configjson, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Французский с нуля —', $hero['translations']['ru']['title']);
        self::assertStringContainsString('путешествие по Провансу', $hero['translations']['ru']['titleaccent']);
        self::assertSame('#showroom-offers', $hero['primarytarget']);
        self::assertSame('#showroom-video', $hero['secondarytarget']);

        $stats = json_decode((string)$bytype['stats']->configjson, true, 512, JSON_THROW_ON_ERROR);
        self::assertStringContainsString('{promotion_start}', $stats['translations']['ru']['items']);
        self::assertStringContainsString('{group_size}', $stats['translations']['ru']['items']);

        $clan = json_decode((string)$bytype['community_clan']->configjson, true, 512, JSON_THROW_ON_ERROR);
        self::assertStringContainsString('{group_size}', $clan['translations']['ru']['text']);

        $support = json_decode((string)$bytype['support']->configjson, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(
            'Преподаватель CampusFR — рядом, когда нужно',
            $support['translations']['ru']['title']
        );
    }

    public function test_seed_is_idempotent_and_does_not_overwrite_builder_edits(): void {
        global $DB;
        $this->resetAfterTest(true);

        $service = CommerceA1ShowroomFoundationService::create($DB);
        $service->ensure(null);
        $service->ensure_builder_structure(null);
        $repo = new CommerceShowroomCmsRepository($DB);
        $showroom = $repo->get_by_key(CommerceA1ShowroomFoundationService::SHOWROOM_KEY);
        self::assertNotNull($showroom);

        $hero = null;
        foreach ($repo->blocks((int)$showroom->id) as $block) {
            if ($block->blocktype === 'hero') {
                $hero = $block;
                break;
            }
        }
        self::assertNotNull($hero);
        $custom = json_encode([
            'translations' => ['ru' => ['title' => 'Мой ручной заголовок']],
            'primarytarget' => '#custom',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $repo->save_block((int)$showroom->id, [
            'id' => (int)$hero->id,
            'blocktype' => 'hero',
            'blockkey' => (string)$hero->blockkey,
            'sortorder' => (int)$hero->sortorder,
            'enabled' => false,
            'configjson' => $custom,
        ], 0);

        $first = $service->apply_editorial_seed_v1(null);
        self::assertGreaterThanOrEqual(1, $first['preserved']);
        $heroafter = $repo->get_block((int)$hero->id);
        self::assertNotNull($heroafter);
        self::assertSame($custom, (string)$heroafter->configjson);
        self::assertSame(0, (int)$heroafter->enabled);

        $second = $service->apply_editorial_seed_v1(null);
        self::assertSame(0, $second['seeded']);
        self::assertSame(0, $second['created']);
        self::assertGreaterThanOrEqual(10, $second['preserved']);
    }

    public function test_seed_does_not_invent_french_or_english_copy(): void {
        global $DB;
        $this->resetAfterTest(true);

        $service = CommerceA1ShowroomFoundationService::create($DB);
        $service->ensure(null);
        $service->apply_editorial_seed_v1(null);
        $repo = new CommerceShowroomCmsRepository($DB);
        $showroom = $repo->get_by_key(CommerceA1ShowroomFoundationService::SHOWROOM_KEY);
        self::assertNotNull($showroom);

        foreach ($repo->blocks((int)$showroom->id) as $block) {
            if ((int)$block->enabled !== 1) {
                continue;
            }
            $config = json_decode((string)$block->configjson, true, 512, JSON_THROW_ON_ERROR);
            if (!isset($config['translations'])) {
                continue;
            }
            self::assertArrayNotHasKey('fr', $config['translations']);
            self::assertArrayNotHasKey('en', $config['translations']);
        }
    }
}
