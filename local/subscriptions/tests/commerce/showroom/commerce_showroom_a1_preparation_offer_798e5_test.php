<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\a1\CommerceA1ShowroomFoundationService;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomRuntimeBlockSet;

/** @covers \local_subscriptions\commerce\showroom\a1\CommerceA1ShowroomFoundationService */
final class commerce_showroom_a1_preparation_offer_798e5_test extends \advanced_testcase {
    public function test_e5_seeds_included_a0_story_and_dynamic_a1_offers(): void {
        global $DB;
        $this->resetAfterTest(true);
        $service = CommerceA1ShowroomFoundationService::create($DB);
        $service->ensure(null);
        $service->ensure_builder_structure(null);
        $service->apply_editorial_seed_v1(null);
        $service->apply_program_map_seed_v1(null);
        $result = $service->apply_preparation_offer_seed_v1(null);

        self::assertSame(2, $result['seeded']);
        self::assertSame(0, $result['preserved']);

        $repo = new CommerceShowroomCmsRepository($DB);
        $showroom = $repo->get_by_key(CommerceA1ShowroomFoundationService::SHOWROOM_KEY);
        self::assertNotNull($showroom);
        self::assertSame('draft', (string)$showroom->status);

        $products = json_decode((string)$showroom->productsjson, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame([
            CommerceA1ShowroomFoundationService::PRODUCT_ROLE_FR => CommerceA1ShowroomFoundationService::PRODUCT_SKU_FR,
            CommerceA1ShowroomFoundationService::PRODUCT_ROLE_RU => CommerceA1ShowroomFoundationService::PRODUCT_SKU_RU,
        ], $products);
        self::assertArrayNotHasKey('a0', $products);

        $bytype = [];
        foreach ($repo->blocks((int)$showroom->id) as $block) {
            $bytype[(string)$block->blocktype] = $block;
        }

        self::assertSame(1, (int)$bytype['preparation_flow']->enabled);
        self::assertSame(1, (int)$bytype['offers']->enabled);

        $prep = json_decode(
            (string)$bytype['preparation_flow']->configjson,
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        $prepru = $prep['translations']['ru'];
        self::assertSame('подготовительное приключение A0', $prepru['titleaccent']);
        self::assertStringContainsString('внутри курса A1', $prepru['text']);
        self::assertStringContainsString('не продаётся отдельно', $prepru['note']);
        self::assertStringContainsString('Сразу получаешь A0', $prepru['items']);

        $offers = json_decode(
            (string)$bytype['offers']->configjson,
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        self::assertSame('standard', $offers['presentation']);
        self::assertSame('course_fr,course_ru', $offers['order']);
        self::assertSame('premium', $offers['cardvariant']);
        self::assertSame('ТАРИФ', $offers['translations']['ru']['badge']);
        self::assertSame(
            'СОПРОВОЖДЕНИЕ НА ФРАНЦУЗСКОМ',
            $offers['translations']['ru']['course_frrolelabel']
        );
        self::assertSame(
            'СОПРОВОЖДЕНИЕ НА РУССКОМ',
            $offers['translations']['ru']['course_rurolelabel']
        );
        self::assertStringContainsString(
            '{group_size}',
            $offers['translations']['ru']['course_frfeatures']
        );
        self::assertStringContainsString(
            '{group_size}',
            $offers['translations']['ru']['course_rufeatures']
        );

        $serialized = json_encode($offers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString('16 900', $serialized);
        self::assertStringNotContainsString('16900', $serialized);
        self::assertStringNotContainsString('₽', $serialized);
        self::assertStringNotContainsString('RUB', $serialized);
    }

    public function test_e5_preserves_builder_edits_and_is_idempotent(): void {
        global $DB;
        $this->resetAfterTest(true);

        $service = CommerceA1ShowroomFoundationService::create($DB);
        $service->ensure(null);
        $service->ensure_builder_structure(null);
        $service->apply_editorial_seed_v1(null);
        $service->apply_program_map_seed_v1(null);

        $repo = new CommerceShowroomCmsRepository($DB);
        $showroom = $repo->get_by_key(CommerceA1ShowroomFoundationService::SHOWROOM_KEY);
        self::assertNotNull($showroom);

        $bytype = [];
        foreach ($repo->blocks((int)$showroom->id) as $block) {
            $bytype[(string)$block->blocktype] = $block;
        }

        $custom = json_encode(
            ['translations' => ['ru' => ['title' => 'Мой A0']]],
            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );
        $repo->save_block((int)$showroom->id, [
            'id' => (int)$bytype['preparation_flow']->id,
            'blocktype' => 'preparation_flow',
            'blockkey' => (string)$bytype['preparation_flow']->blockkey,
            'sortorder' => (int)$bytype['preparation_flow']->sortorder,
            'enabled' => false,
            'configjson' => $custom,
        ], 2);

        $first = $service->apply_preparation_offer_seed_v1(2);
        self::assertSame(1, $first['seeded']);
        self::assertSame(1, $first['preserved']);

        $freshprep = $repo->get_block((int)$bytype['preparation_flow']->id);
        self::assertNotNull($freshprep);
        self::assertSame($custom, (string)$freshprep->configjson);
        self::assertSame(0, (int)$freshprep->enabled);

        $second = $service->apply_preparation_offer_seed_v1(2);
        self::assertSame(0, $second['seeded']);
        self::assertSame(2, $second['preserved']);
    }

    public function test_offer_builder_copy_can_use_pedagogy_tokens_without_overriding_commerce_prices(): void {
        global $DB;
        $this->resetAfterTest(true);

        $repo = new CommerceShowroomCmsRepository($DB);
        $showroomid = $repo->save([
            'showroomkey' => 'a1-e5-runtime',
            'status' => 'draft',
            'name' => 'A1 E5 runtime',
            'template' => 'local_subscriptions/showroom/default',
            'slugfr' => '', 'slugen' => '', 'slugru' => '',
            'titlekey' => '', 'descriptionkey' => '',
            'productsjson' => '{}', 'settingsjson' => '{}',
        ], 2);
        $repo->save_block($showroomid, [
            'blocktype' => 'offers',
            'sortorder' => 10,
            'enabled' => true,
            'configjson' => json_encode([
                'presentation' => 'single_course',
                'singleofferrole' => 'course',
                'coursetitle' => 'A1 dynamique',
                'coursefeatures' => "Clan de {group_size} personnes\n{remaining_places} places restantes",
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ], 2);

        $blocks = CommerceShowroomRuntimeBlockSet::load_preview($DB, $showroomid);
        $data = (new CommerceShowroomBlockConfigurationPresenter())->apply([
            'pedagogicalgroupsize' => 6,
            'pedagogicalremaining' => 3,
            'offers' => [
                [
                    'role' => 'course',
                    'sku' => 'A1-FULL',
                    'name' => 'Catalogue A1',
                    'priceformatted' => '123,45 €',
                    'promotionjoinpriceformatted' => '49,00 €',
                    'haspromotionjoincontext' => true,
                    'promotionjoinpurchasable' => true,
                    'shortdescription' => '',
                ],
                [
                    'role' => 'pdf',
                    'sku' => 'PDF-X',
                    'name' => 'PDF',
                    'priceformatted' => '10,00 €',
                    'shortdescription' => '',
                ],
            ],
        ], $blocks);

        self::assertTrue($data['offersissinglecourse']);
        self::assertCount(1, $data['offers']);
        $offer = $data['offers'][0];
        self::assertSame('A1 dynamique', $offer['name']);
        self::assertSame('123,45 €', $offer['priceformatted']);
        self::assertSame('49,00 €', $offer['promotionjoinpriceformatted']);
        self::assertTrue($offer['haspromotionjoincontext']);
        self::assertSame('Clan de 6 personnes', $offer['features'][0]['text']);
        self::assertSame('3 places restantes', $offer['features'][1]['text']);
    }
}
