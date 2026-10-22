<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\a1\CommerceA1ShowroomFoundationService;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;

/** @covers \local_subscriptions\commerce\showroom\a1\CommerceA1ShowroomFoundationService */
final class commerce_showroom_a1_multilingual_seo_responsive_798e7_test extends \advanced_testcase {
    public function test_e7_adds_fr_en_copy_seo_and_slugs_without_publishing(): void {
        global $CFG, $DB;
        $this->resetAfterTest(true);

        $service = CommerceA1ShowroomFoundationService::create($DB);
        $service->ensure(null);
        $service->ensure_builder_structure(null);
        $service->apply_editorial_seed_v1(null);
        $service->apply_program_map_seed_v1(null);
        $service->apply_preparation_offer_seed_v1(null);
        $service->apply_review_ready_seed_v1(null);
        $result = $service->apply_multilingual_seo_seed_v1(null);

        self::assertGreaterThan(40, $result['translationsmerged']);
        self::assertSame(15, $result['seofieldsset']);
        self::assertSame(3, $result['slugsset']);

        $repo = new CommerceShowroomCmsRepository($DB);
        $showroom = $repo->get_by_key(CommerceA1ShowroomFoundationService::SHOWROOM_KEY);
        self::assertNotNull($showroom);
        self::assertSame('draft', (string)$showroom->status);
        self::assertSame('francais-a1', (string)$showroom->slugfr);
        self::assertSame('french-a1', (string)$showroom->slugen);
        self::assertSame('francuzskij-a1', (string)$showroom->slugru);

        $settings = json_decode((string)$showroom->settingsjson, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('7.98E7', $settings['foundation']['reviewready']);
        self::assertSame('Cours de français A1 pour débutants | CampusFR', $settings['seo']['fr']['title']);
        self::assertSame('French A1 course for beginners | CampusFR', $settings['seo']['en']['title']);
        self::assertSame('Французский A1 с нуля — курс CampusFR', $settings['seo']['ru']['title']);

        $bytype = [];
        foreach ($repo->blocks((int)$showroom->id) as $block) {
            $bytype[(string)$block->blocktype] = $block;
        }

        foreach (['hero', 'audience_profiles', 'offers', 'faq', 'final_cta'] as $type) {
            $config = json_decode((string)$bytype[$type]->configjson, true, 512, JSON_THROW_ON_ERROR);
            self::assertNotEmpty($config['translations']['fr']);
            self::assertNotEmpty($config['translations']['en']);
        }

        $hero = json_decode((string)$bytype['hero']->configjson, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Commencer le voyage', $hero['translations']['fr']['primarylabel']);
        self::assertSame('Start the journey', $hero['translations']['en']['primarylabel']);

        $program = json_decode((string)$bytype['curriculum_tabs']->configjson, true, 512, JSON_THROW_ON_ERROR);
        self::assertCount(30, explode("\n", $program['translations']['fr']['topics']));
        self::assertCount(30, explode("\n", $program['translations']['en']['topics']));
        self::assertArrayHasKey('grammar', $program['translations']['fr']);
        self::assertArrayHasKey('grammar', $program['translations']['en']);
        self::assertSame('', $program['translations']['fr']['grammar']);
        self::assertSame('', $program['translations']['en']['grammar']);

        self::assertSame(0, (int)$bytype['testimonials']->enabled);

        $second = $service->apply_multilingual_seo_seed_v1(null);
        self::assertSame(0, $second['translationsmerged']);
        self::assertSame(0, $second['seofieldsset']);
        self::assertSame(0, $second['slugsset']);

        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        self::assertIsString($css);
        self::assertStringContainsString('[data-showroom="a1-course"]', $css);
        self::assertStringContainsString('@media (max-width: 991.98px)', $css);
        self::assertStringContainsString('@media (max-width: 767.98px)', $css);
        self::assertStringContainsString('.commerce-showroom-offers__grid', $css);
        self::assertStringContainsString('.commerce-showroom-final', $css);
    }

    public function test_e7_never_overwrites_manual_translation_slug_or_seo(): void {
        global $DB;
        $this->resetAfterTest(true);

        $service = CommerceA1ShowroomFoundationService::create($DB);
        $service->ensure(null);
        $service->ensure_builder_structure(null);
        $service->apply_editorial_seed_v1(null);
        $service->apply_program_map_seed_v1(null);
        $service->apply_preparation_offer_seed_v1(null);
        $service->apply_review_ready_seed_v1(null);

        $repo = new CommerceShowroomCmsRepository($DB);
        $showroom = $repo->get_by_key(CommerceA1ShowroomFoundationService::SHOWROOM_KEY);
        self::assertNotNull($showroom);

        $settings = json_decode((string)$showroom->settingsjson, true, 512, JSON_THROW_ON_ERROR);
        $settings['seo']['fr']['title'] = 'SEO Nata';
        $repo->save([
            'id' => (int)$showroom->id,
            'showroomkey' => (string)$showroom->showroomkey,
            'status' => (string)$showroom->status,
            'name' => (string)$showroom->name,
            'template' => (string)$showroom->template,
            'slugfr' => 'nata-a1',
            'slugen' => (string)$showroom->slugen,
            'slugru' => (string)$showroom->slugru,
            'titlekey' => (string)$showroom->titlekey,
            'descriptionkey' => (string)$showroom->descriptionkey,
            'productsjson' => (string)$showroom->productsjson,
            'settingsjson' => json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ], 2);

        $heroblock = null;
        foreach ($repo->blocks((int)$showroom->id) as $block) {
            if ((string)$block->blocktype === 'hero') {
                $heroblock = $block;
                break;
            }
        }
        self::assertNotNull($heroblock);
        $hero = json_decode((string)$heroblock->configjson, true, 512, JSON_THROW_ON_ERROR);
        $hero['translations']['fr']['title'] = 'Titre Nata';
        $repo->save_block((int)$showroom->id, [
            'id' => (int)$heroblock->id,
            'blocktype' => 'hero',
            'blockkey' => (string)$heroblock->blockkey,
            'sortorder' => (int)$heroblock->sortorder,
            'enabled' => true,
            'configjson' => json_encode($hero, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ], 2);

        $service->apply_multilingual_seo_seed_v1(2);

        $freshshowroom = $repo->get_by_key(CommerceA1ShowroomFoundationService::SHOWROOM_KEY);
        self::assertNotNull($freshshowroom);
        self::assertSame('nata-a1', (string)$freshshowroom->slugfr);
        $freshsettings = json_decode((string)$freshshowroom->settingsjson, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('SEO Nata', $freshsettings['seo']['fr']['title']);
        self::assertNotEmpty($freshsettings['seo']['fr']['description']);

        $freshhero = $repo->get_block((int)$heroblock->id);
        self::assertNotNull($freshhero);
        $freshheroconfig = json_decode((string)$freshhero->configjson, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Titre Nata', $freshheroconfig['translations']['fr']['title']);
        self::assertSame('Commencer le voyage', $freshheroconfig['translations']['fr']['primarylabel']);
    }
}
