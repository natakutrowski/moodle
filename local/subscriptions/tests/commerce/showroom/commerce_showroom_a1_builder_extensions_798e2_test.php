<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\a1\CommerceA1ShowroomFoundationService;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockEditorRegistry;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockTypeRegistry;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomPageTemplateRegistry;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomRuntimeBlockSet;

/** 7.98E.2 — reusable Builder extensions required by the A1 Showroom. */
final class commerce_showroom_a1_builder_extensions_798e2_test extends \advanced_testcase {
    /** @return string[] */
    private function e2_types(): array {
        return [
            'anchor_nav',
            'adventure_overview',
            'community_clan',
            'tutor_chat',
            'curriculum_tabs',
            'journey_map',
            'preparation_flow',
        ];
    }

    public function test_new_blocks_are_generic_builder_types_and_a1_template_uses_them(): void {
        $definitions = CommerceShowroomBlockTypeRegistry::definitions();
        $schemas = CommerceShowroomBlockEditorRegistry::schemas(['course']);

        foreach ($this->e2_types() as $type) {
            self::assertArrayHasKey($type, $definitions);
            self::assertArrayHasKey($type, $schemas);
            self::assertNotEmpty($schemas[$type]['fields']);
        }

        $templates = CommerceShowroomPageTemplateRegistry::definitions();
        self::assertArrayHasKey('a1_course', $templates);
        foreach ($this->e2_types() as $type) {
            self::assertContains($type, $templates['a1_course']['blocks']);
        }
        self::assertContains('stats', $templates['a1_course']['blocks']);
        self::assertContains('testimonials', $templates['a1_course']['blocks']);
    }

    public function test_e2_structure_extends_existing_e1_draft_without_overwriting_editorial_content(): void {
        global $DB;
        $this->resetAfterTest(true);

        set_config('trial_conversion_product_sku', 'COURSE.A1.FULL', 'local_subscriptions');
        $service = CommerceA1ShowroomFoundationService::create($DB);
        $showroomid = $service->ensure(2);
        $repository = new CommerceShowroomCmsRepository($DB);

        $blocks = $repository->blocks($showroomid);
        self::assertCount(7, $blocks);
        $hero = reset($blocks);
        self::assertNotFalse($hero);
        $repository->save_block($showroomid, [
            'id' => (int)$hero->id,
            'blocktype' => 'hero',
            'blockkey' => (string)$hero->blockkey,
            'sortorder' => (int)$hero->sortorder,
            'enabled' => true,
            'configjson' => json_encode(['title' => 'Mon A1'], JSON_THROW_ON_ERROR),
        ], 2);

        self::assertSame(9, $service->ensure_builder_structure(2));
        $after = $repository->blocks($showroomid);
        self::assertCount(16, $after);
        self::assertSame([
            'hero', 'stats', 'anchor_nav', 'audience_profiles',
            'adventure_overview', 'content_highlights', 'community_clan',
            'tutor_chat', 'video', 'curriculum_tabs', 'journey_map',
            'preparation_flow', 'offers', 'testimonials', 'faq', 'final_cta',
        ], array_map(static fn(\stdClass $block): string => (string)$block->blocktype, $after));

        $bytype = [];
        foreach ($after as $block) {
            $bytype[(string)$block->blocktype] = $block;
        }
        self::assertSame(
            ['title' => 'Mon A1'],
            json_decode((string)$bytype['hero']->configjson, true, 512, JSON_THROW_ON_ERROR)
        );
        self::assertSame(1, (int)$bytype['hero']->enabled);
        foreach ($this->e2_types() as $type) {
            self::assertSame(0, (int)$bytype[$type]->enabled, $type . ' must be upgrade-safe.');
        }
        self::assertSame(0, (int)$bytype['stats']->enabled);
        self::assertSame(0, (int)$bytype['testimonials']->enabled);

        self::assertSame(0, $service->ensure_builder_structure(2));
        self::assertCount(16, $repository->blocks($showroomid));
    }

    public function test_runtime_presenter_maps_five_stats_tabs_map_flow_and_single_offer(): void {
        global $DB;
        $this->resetAfterTest(true);

        $repository = new CommerceShowroomCmsRepository($DB);
        $showroomid = $repository->save([
            'showroomkey' => 'a1-e2-runtime',
            'status' => 'draft',
            'name' => 'A1 E2 runtime',
            'template' => 'local_subscriptions/showroom/default',
            'slugfr' => '', 'slugen' => '', 'slugru' => '',
            'titlekey' => '', 'descriptionkey' => '',
            'productsjson' => '{}', 'settingsjson' => '{}',
        ], 2);

        $configs = [
            'stats' => [
                'items' => "30|aventures|fa-route\n{promotion_start}|départ|fa-calendar\nA0|inclus|fa-book\n{group_size}|par clan|fa-users\nTutorat|inclus|fa-comments",
            ],
            'anchor_nav' => [
                'items' => "Cours|#showroom-adventure\nProgramme|#showroom-program\nTarif|#showroom-offers",
                'sticky' => true,
                'ctalabel' => 'Commencer',
                'ctatarget' => '#showroom-offers',
            ],
            'curriculum_tabs' => [
                'tab1label' => 'Thèmes',
                'tab2label' => 'Grammaire A1',
                'topics' => "Premiers pas|fa-users|01|Se présenter\nPremiers pas|fa-users|02|Décrire une personne\nVoyage|fa-plane|21|À l’hôtel",
                'grammar' => "Bases|fa-book|01|Être et avoir\nBases|fa-book|02|Articles",
                'defaulttab' => 'topics',
            ],
            'journey_map' => [
                'title' => 'Carte du voyage',
                'imageurl' => '/map.webp',
                'items' => "Côte d’Azur|Nice, Antibes, Cannes|fa-sun\nProvence|Marseille, Aix, Avignon|fa-spa\nCorse|Bastia, Ajaccio, Bonifacio|fa-sailboat",
            ],
            'preparation_flow' => [
                'title' => 'Préparation A0 incluse',
                'items' => "Acheter A1|Accès immédiat|fa-cart-shopping\nA0 inclus|Apprendre à lire|fa-book-open\nEntrer en A1|Plus sereinement|fa-flag",
            ],
            'offers' => [
                'presentation' => 'single_course',
                'singleofferrole' => 'course',
            ],
        ];
        foreach ($configs as $index => $config) {
            $repository->save_block($showroomid, [
                'blocktype' => $index,
                'sortorder' => 10,
                'enabled' => true,
                'configjson' => json_encode($config, JSON_THROW_ON_ERROR),
            ], 2);
        }

        $blocks = CommerceShowroomRuntimeBlockSet::load_preview($DB, $showroomid);
        $data = (new CommerceShowroomBlockConfigurationPresenter())->apply([
            'pedagogicalpromotionstartlabel' => '19 octobre 2026',
            'pedagogicalgroupsize' => 6,
            'pedagogicalremaining' => 4,
            'pedagogicalpromotioncapacity' => 24,
            'offers' => [
                ['role' => 'course', 'sku' => 'A1', 'name' => 'A1'],
                ['role' => 'pdf', 'sku' => 'PDF', 'name' => 'PDF'],
            ],
            'comparisonoffers' => [],
        ], $blocks);

        self::assertTrue($data['hasherostat5']);
        self::assertSame('19 octobre 2026', $data['herostat2value']);
        self::assertSame('6', $data['herostat4value']);
        self::assertSame('Tutorat', $data['herostat5value']);
        self::assertCount(3, $data['anchornavitems']);
        self::assertTrue($data['anchornavsticky']);
        self::assertCount(2, $data['curriculumtopicsgroups']);
        self::assertSame('Premiers pas', $data['curriculumtopicsgroups'][0]['title']);
        self::assertCount(2, $data['curriculumtopicsgroups'][0]['items']);
        self::assertTrue($data['hasjourneymapimage']);
        self::assertCount(3, $data['journeymapitems']);
        self::assertCount(3, $data['preparationitems']);
        self::assertTrue($data['preparationitems'][0]['hasarrow']);
        self::assertFalse($data['preparationitems'][2]['hasarrow']);
        self::assertTrue($data['offersissinglecourse']);
        self::assertSame('1', $data['offerscolumns']);
        self::assertCount(1, $data['offers']);
        self::assertSame('course', $data['offers'][0]['role']);

        $runtime = $blocks->to_template_data();
        self::assertTrue($runtime['showblockanchornav']);
        self::assertTrue($runtime['showblockcurriculumtabs']);
        self::assertTrue($runtime['showblockjourneymap']);
        self::assertTrue($runtime['showblockpreparationflow']);
    }

    public function test_public_renderer_contains_accessible_tabs_anchor_navigation_and_new_partials(): void {
        global $CFG;

        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/default.mustache');
        $javascript = file_get_contents($CFG->dirroot . '/local/subscriptions/amd/src/showroom.js');
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');

        self::assertIsString($template);
        self::assertStringContainsString('block_anchor_nav', $template);
        self::assertStringContainsString('block_curriculum_tabs', $template);
        self::assertStringContainsString('block_journey_map', $template);
        self::assertStringContainsString('block_preparation_flow', $template);
        self::assertStringContainsString('{{#hasherostat5}}', $template);

        self::assertIsString($javascript);
        self::assertStringContainsString('bindCurriculumTabs', $javascript);
        self::assertStringContainsString("event.key === 'ArrowRight'", $javascript);
        self::assertStringContainsString('bindSectionAnchors', $javascript);

        self::assertIsString($css);
        self::assertStringContainsString('.commerce-showroom-curriculum__tabs', $css);
        self::assertStringContainsString('.commerce-showroom-offers--single-course', $css);

        foreach ([
            'block_anchor_nav.mustache',
            'block_adventure_overview.mustache',
            'block_community_clan.mustache',
            'block_tutor_chat.mustache',
            'block_curriculum_tabs.mustache',
            'curriculum_group.mustache',
            'block_journey_map.mustache',
            'block_preparation_flow.mustache',
        ] as $partial) {
            self::assertFileExists($CFG->dirroot . '/local/subscriptions/templates/showroom/' . $partial);
        }
    }
}
