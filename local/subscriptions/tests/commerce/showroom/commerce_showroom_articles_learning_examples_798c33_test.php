<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockEditorRegistry;

final class commerce_showroom_articles_learning_examples_798c33_test extends \advanced_testcase {
    public function test_content_highlights_exposes_article_meaning_presentation_and_fields(): void {
        $schema = CommerceShowroomBlockEditorRegistry::schema('content_highlights');
        $fields = [];
        foreach (($schema['fields'] ?? []) as $field) {
            $fields[(string)($field['name'] ?? '')] = $field;
        }

        self::assertArrayHasKey('presentation', $fields);
        self::assertArrayHasKey('article_meaning', $fields['presentation']['options']);

        foreach ([
            'titleaccent', 'learningcards', 'sidetitle', 'sideintro', 'sideitems',
            'sidefooter', 'sidefooteraccent', 'summarytext', 'summaryaccent',
        ] as $fieldname) {
            self::assertArrayHasKey($fieldname, $fields);
        }
    }

    public function test_presenter_builds_three_learning_cards_and_fourth_insight_column(): void {
        $reflection = new \ReflectionClass(CommerceShowroomBlockConfigurationPresenter::class);
        $presenter = $reflection->newInstance();
        $method = $reflection->getMethod('apply_content_highlights');
        $method->setAccessible(true);

        $data = $method->invoke($presenter, [], [
            'presentation' => 'article_meaning',
            'title' => 'Un même mot. Trois articles.',
            'titleaccent' => 'Trois sens différents.',
            'learningcards' => implode("\n", [
                'UN|CAFÉ|Une portion.|Je prends un café.|Une tasse.',
                'LE|CAFÉ|Le café en général.|J’adore le café.|La boisson en général.',
                'DU|CAFÉ|Une quantité.|Je bois du café.|Une quantité indéterminée.',
            ]),
            'image1' => '/media/un.webp',
            'image2' => '/media/le.webp',
            'image3' => '/media/du.webp',
            'sidetitle' => 'Le même objet, un autre regard.',
            'sideintro' => 'L’article aide à montrer :',
            'sideitems' => "une unité|fa-solid fa-check\nquelque chose en général|fa-solid fa-check\nune quantité|fa-solid fa-check",
            'sidefooter' => 'Il faut comprendre',
            'sidefooteraccent' => 'le sens et la situation.',
            'summarytext' => 'Quand vous savez ce que vous voulez dire,',
            'summaryaccent' => 'le choix cesse d’être une loterie.',
        ]);

        self::assertSame('article_meaning', $data['contenthighlightpresentation']);
        self::assertTrue($data['contenthighlightisarticlemeaning']);
        self::assertFalse($data['contenthighlightisstandard']);
        self::assertSame('Trois sens différents.', $data['contenttitleaccent']);
        self::assertCount(3, $data['contentlearningcards']);
        self::assertSame('UN', $data['contentlearningcards'][0]['article']);
        self::assertSame('/media/un.webp', $data['contentlearningcards'][0]['imageurl']);
        self::assertSame('Je prends ', $data['contentlearningcards'][0]['exampleprefix']);
        self::assertSame('un café', $data['contentlearningcards'][0]['examplehighlight']);
        self::assertSame('.', $data['contentlearningcards'][0]['examplesuffix']);
        self::assertCount(3, $data['contentsideitems']);
        self::assertSame('Le même objet, un autre regard.', $data['contentsidetitle']);
        self::assertTrue($data['hascontentsummary']);
    }

    public function test_article_meaning_markup_has_four_columns_and_preserves_standard_fallback(): void {
        global $CFG;

        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/default.mustache');
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');

        self::assertStringContainsString('{{#contenthighlightisarticlemeaning}}', $template);
        self::assertStringContainsString('{{^contenthighlightisarticlemeaning}}', $template);
        self::assertStringContainsString('commerce-showroom-content-highlights__learning-grid', $template);
        self::assertStringContainsString('commerce-showroom-content-highlights__learning-side', $template);
        self::assertStringContainsString('commerce-showroom-content-highlights__badge', $template);
        self::assertStringContainsString('commerce-showroom-content-highlights__learning-example-accent', $template);
        self::assertStringContainsString('grid-template-columns: repeat(3, minmax(0, 1fr)) minmax(0, .95fr);', $css);
        self::assertStringContainsString('.commerce-showroom-content-highlights--article_meaning.commerce-showroom-section--mountains::after', $css);
        self::assertStringContainsString('text-align: center;', $css);
    }
}
