<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\a1\CommerceA1ShowroomFoundationService;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;

/** @covers \local_subscriptions\commerce\showroom\a1\CommerceA1ShowroomFoundationService */
final class commerce_showroom_a1_review_ready_798e6_test extends \advanced_testcase {
    public function test_e6_completes_faq_final_cta_and_keeps_testimonials_disabled(): void {
        global $DB;
        $this->resetAfterTest(true);

        $service = CommerceA1ShowroomFoundationService::create($DB);
        $service->ensure(null);
        $service->ensure_builder_structure(null);
        $service->apply_editorial_seed_v1(null);
        $service->apply_program_map_seed_v1(null);
        $service->apply_preparation_offer_seed_v1(null);
        $result = $service->apply_review_ready_seed_v1(null);

        self::assertSame(3, $result['seeded']);
        self::assertSame(0, $result['preserved']);
        self::assertGreaterThan(0, $result['layoutmerged']);

        $repo = new CommerceShowroomCmsRepository($DB);
        $showroom = $repo->get_by_key(CommerceA1ShowroomFoundationService::SHOWROOM_KEY);
        self::assertNotNull($showroom);
        self::assertSame('draft', (string)$showroom->status);

        $bytype = [];
        foreach ($repo->blocks((int)$showroom->id) as $block) {
            $bytype[(string)$block->blocktype] = $block;
        }

        self::assertSame(1, (int)$bytype['faq']->enabled);
        self::assertSame(1, (int)$bytype['final_cta']->enabled);
        self::assertSame(0, (int)$bytype['testimonials']->enabled);

        $faq = json_decode((string)$bytype['faq']->configjson, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Остались вопросы?', $faq['translations']['ru']['title']);
        self::assertCount(9, explode("\n", $faq['translations']['ru']['items']));
        self::assertSame('white', $faq['sectionbackground']);
        self::assertTrue($faq['singleopen']);

        $final = json_decode((string)$bytype['final_cta']->configjson, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Франция ждёт —', $final['translations']['ru']['title']);
        self::assertSame('поехали вместе?', $final['translations']['ru']['titleaccent']);
        self::assertSame('#showroom-offers', $final['buttontarget']);
        self::assertSame('gradient', $final['sectionbackground']);
        self::assertSame('white', $final['sectiontextcolor']);

        $testimonials = json_decode((string)$bytype['testimonials']->configjson, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('', $testimonials['translations']['ru']['items']);
        self::assertSame('ОТЗЫВЫ', $testimonials['translations']['ru']['eyebrow']);

        $second = $service->apply_review_ready_seed_v1(null);
        self::assertSame(0, $second['seeded']);
        self::assertSame(3, $second['preserved']);
        self::assertSame(0, $second['layoutmerged']);
    }

    public function test_e6_keeps_existing_builder_copy_and_presentation_choices(): void {
        global $DB;
        $this->resetAfterTest(true);

        $service = CommerceA1ShowroomFoundationService::create($DB);
        $service->ensure(null);
        $service->ensure_builder_structure(null);

        $repo = new CommerceShowroomCmsRepository($DB);
        $showroom = $repo->get_by_key(CommerceA1ShowroomFoundationService::SHOWROOM_KEY);
        self::assertNotNull($showroom);

        $faqblock = null;
        foreach ($repo->blocks((int)$showroom->id) as $block) {
            if ((string)$block->blocktype === 'faq') {
                $faqblock = $block;
                break;
            }
        }
        self::assertNotNull($faqblock);

        $repo->save_block((int)$showroom->id, [
            'id' => (int)$faqblock->id,
            'blocktype' => 'faq',
            'blockkey' => (string)$faqblock->blockkey,
            'sortorder' => (int)$faqblock->sortorder,
            'enabled' => false,
            'configjson' => json_encode([
                'translations' => ['ru' => ['title' => 'FAQ de Nata']],
                'sectionbackground' => 'dark',
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ], 2);

        $service->apply_review_ready_seed_v1(2);
        $fresh = $repo->get_block((int)$faqblock->id);
        self::assertNotNull($fresh);
        self::assertSame(0, (int)$fresh->enabled);
        $config = json_decode((string)$fresh->configjson, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('FAQ de Nata', $config['translations']['ru']['title']);
        self::assertSame('dark', $config['sectionbackground']);
        self::assertSame('large', $config['sectionspacing']);
    }
}
