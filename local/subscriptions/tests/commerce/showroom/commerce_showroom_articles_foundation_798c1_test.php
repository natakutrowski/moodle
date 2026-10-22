<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockEditorRegistry;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockTypeRegistry;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomRuntimeBlockSet;

final class commerce_showroom_articles_foundation_798c1_test extends \advanced_testcase {
    public function test_articles_reusable_blocks_are_registered_and_editable(): void {
        $definitions = CommerceShowroomBlockTypeRegistry::definitions();
        self::assertArrayHasKey('heart_puzzle', $definitions);
        self::assertArrayHasKey('before_after', $definitions);

        $highlights = array_column(
            CommerceShowroomBlockEditorRegistry::schema('content_highlights')['fields'],
            'name'
        );
        self::assertContains('columns', $highlights);
        self::assertContains('image1', $highlights);
        self::assertContains('image6', $highlights);

        $heart = array_column(
            CommerceShowroomBlockEditorRegistry::schema('heart_puzzle')['fields'],
            'name'
        );
        foreach (['pieces', 'centerimageurl', 'centerlabel', 'footertitle', 'footertext', 'footeraccent'] as $field) {
            self::assertContains($field, $heart);
        }

        $beforeafter = array_column(
            CommerceShowroomBlockEditorRegistry::schema('before_after')['fields'],
            'name'
        );
        foreach (['beforeitems', 'afteritems', 'imageurl', 'summarytitle', 'summarytext'] as $field) {
            self::assertContains($field, $beforeafter);
        }
    }

    public function test_articles_blocks_reach_runtime_presenter_without_product_specific_logic(): void {
        global $DB;
        $this->resetAfterTest(true);

        $repository = new CommerceShowroomCmsRepository($DB);
        $showroomid = $repository->save([
            'showroomkey' => 'articles-c1-fixture',
            'status' => 'published',
            'name' => 'Articles C1 fixture',
            'template' => 'local_subscriptions/showroom/default',
            'titlekey' => '',
            'descriptionkey' => '',
            'productsjson' => '{}',
            'settingsjson' => '{}',
        ], 2);

        $repository->save_block($showroomid, [
            'blocktype' => 'content_highlights',
            'blockkey' => 'meaning',
            'sortorder' => 10,
            'enabled' => true,
            'configjson' => json_encode([
                'title' => 'Trois regards',
                'items' => "UN CAFÉ|Une tasse|fa-solid fa-mug-hot\nLE CAFÉ|Le concept|fa-solid fa-book\nDU CAFÉ|Une quantité|fa-solid fa-droplet",
                'columns' => '3',
                'image1' => '/media/cafe-un.webp',
                'image2' => '/media/cafe-le.webp',
                'image3' => '/media/cafe-du.webp',
            ], JSON_THROW_ON_ERROR),
        ], 2);

        $repository->save_block($showroomid, [
            'blocktype' => 'heart_puzzle',
            'blockkey' => 'heart',
            'sortorder' => 20,
            'enabled' => true,
            'configjson' => json_encode([
                'title' => 'Tout est lié.',
                'titleaccent' => 'Assemblez le système.',
                'pieces' => implode("\n", [
                    '01|Qui ?|Défini ou indéfini.|le / un|fa-solid fa-magnifying-glass',
                    '02|Comptable ?|Ajouter le partitif.|du / de la|fa-solid fa-cube',
                    '03|La règle change|Négation et quantité.|pas de|fa-solid fa-arrow-right-arrow-left',
                    '04|Les pièges|Expressions figées.|jouer du piano|fa-solid fa-triangle-exclamation',
                    '05|Disparition|Sans article.|Ø|fa-regular fa-circle',
                ]),
                'centerimageurl' => '/media/eiffel.webp',
                'centerlabel' => 'Le système des articles',
                'footertitle' => 'Chaque épisode révèle une pièce.',
                'footertext' => 'Quand tout est assemblé,',
                'footeraccent' => 'le cœur recommence à battre.',
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ], 2);

        $repository->save_block($showroomid, [
            'blocktype' => 'before_after',
            'blockkey' => 'result',
            'sortorder' => 30,
            'enabled' => true,
            'configjson' => json_encode([
                'title' => 'Avant',
                'titleaccent' => 'et après',
                'beforelabel' => 'Avant',
                'beforetitle' => 'Vous hésitez',
                'beforeitems' => "Doute|Quel article choisir ?|fa-solid fa-question\nPause|Vous cherchez la règle.|fa-solid fa-pause",
                'afterlabel' => 'Après',
                'aftertitle' => 'Vous choisissez naturellement',
                'afteritems' => "Sens|Vous voyez la différence.|fa-solid fa-brain\nConfiance|Vous parlez plus librement.|fa-solid fa-comments",
                'imageurl' => '/media/gustave-alf.webp',
                'summarytext' => 'Plus de compréhension.',
                'summaryaccent' => 'Plus de liberté.',
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ], 2);

        $blocks = CommerceShowroomRuntimeBlockSet::load($DB, 'articles-c1-fixture');
        self::assertTrue($blocks->is_enabled('heart_puzzle'));
        self::assertTrue($blocks->is_enabled('before_after'));
        self::assertSame(['content_highlights', 'heart_puzzle', 'before_after'], $blocks->sequence());

        $template = $blocks->to_template_data();
        self::assertTrue($template['showblockheartpuzzle']);
        self::assertTrue($template['showblockbeforeafter']);
        self::assertTrue($template['showroomorderedblocks'][1]['isheartpuzzle']);
        self::assertTrue($template['showroomorderedblocks'][2]['isbeforeafter']);

        $data = (new CommerceShowroomBlockConfigurationPresenter())->apply([], $blocks);
        self::assertSame('3', $data['contenthighlightcolumns']);
        self::assertTrue($data['contentstats'][0]['hasimage']);
        self::assertSame('/media/cafe-un.webp', $data['contentstats'][0]['imageurl']);

        self::assertCount(5, $data['heartpuzzlepieces']);
        self::assertSame('01', $data['heartpuzzlepiece1number']);
        self::assertSame('05', $data['heartpuzzlepiece5number']);
        self::assertTrue($data['hasheartpuzzlecenterimage']);
        self::assertTrue($data['hasheartpuzzlefooter']);

        self::assertCount(2, $data['beforeitems']);
        self::assertCount(2, $data['afteritems']);
        self::assertTrue($data['hasbeforeafterimage']);
        self::assertTrue($data['hasbeforeaftersummary']);
    }

    public function test_default_renderer_contains_reusable_articles_markup_and_responsive_css(): void {
        global $CFG;

        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/default.mustache');
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');

        self::assertIsString($template);
        self::assertIsString($css);
        self::assertStringContainsString('{{#isheartpuzzle}}', $template);
        self::assertStringContainsString('commerce-showroom-heart-puzzle__segments', $template);
        self::assertStringContainsString('{{#isbeforeafter}}', $template);
        self::assertStringContainsString('commerce-showroom-before-after__grid', $template);
        self::assertStringContainsString('commerce-showroom-stat__media', $template);

        self::assertStringContainsString('.commerce-showroom-heart-puzzle__stage', $css);
        self::assertStringContainsString('.commerce-showroom-before-after__grid', $css);
        self::assertStringContainsString('.commerce-showroom-content-highlights--columns-3', $css);
        self::assertStringContainsString('@media (max-width: 680px)', $css);
    }
}
