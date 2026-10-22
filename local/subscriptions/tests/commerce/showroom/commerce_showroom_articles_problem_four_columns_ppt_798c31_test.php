<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockEditorRegistry;

final class commerce_showroom_articles_problem_four_columns_ppt_798c31_test extends \advanced_testcase {
    public function test_editor_exposes_four_columns_ppt_fields(): void {
        $schema = CommerceShowroomBlockEditorRegistry::schema('problem_interactive');
        $fields = [];
        foreach (($schema['fields'] ?? []) as $field) {
            $fields[(string)($field['name'] ?? '')] = $field;
        }

        self::assertArrayHasKey('presentation', $fields);
        self::assertArrayHasKey('four_columns_ppt', $fields['presentation']['options']);

        foreach ([
            'col1title', 'col1items', 'col1footer',
            'col2title', 'col2centertext', 'col2subtext',
            'col3title', 'col3items',
            'col4title', 'col4items',
        ] as $fieldname) {
            self::assertArrayHasKey($fieldname, $fields);
        }
    }

    public function test_presenter_builds_four_columns_ppt_view_model(): void {
        $reflection = new \ReflectionClass(CommerceShowroomBlockConfigurationPresenter::class);
        $presenter = $reflection->newInstance();
        $method = $reflection->getMethod('apply_problem_interactive');
        $method->setAccessible(true);

        $data = $method->invoke($presenter, [], [
            'presentation' => 'four_columns_ppt',
            'col1title' => 'Tant de possibilités…',
            'col1items' => "le / la / les\nun / une / des",
            'col2title' => 'Que choisir ici ?',
            'col2centertext' => '?',
            'col2subtext' => 'Un, le, du… ?',
            'col3title' => 'Des situations réelles',
            'col3items' => "J’adore ___ chocolat.\nIl joue ___ piano.",
            'col4title' => 'Et ce n’est pas qu’une question de règles.',
            'col4items' => "Le sens change|un ami ≠ l’ami ≠ des amis|fa-regular fa-comment-dots\nTout dépend de la situation|Le contexte détermine l’article.|fa-solid fa-shuffle",
        ]);

        self::assertSame('four_columns_ppt', $data['interactiveproblempresentation']);
        self::assertTrue($data['interactiveproblemisfourcolumnsppt']);
        self::assertFalse($data['interactiveproblemisinteractive']);
        self::assertFalse($data['interactiveproblemispresentation']);
        self::assertSame('commerce-showroom-interactive-problem--four_columns_ppt', $data['interactiveproblemvariantclass']);
        self::assertCount(2, $data['interactiveproblemcol1items']);
        self::assertSame('J’adore ___ chocolat.', $data['interactiveproblemcol3items'][0]['text']);
        self::assertSame('Le sens change', $data['interactiveproblemcol4items'][0]['title']);
        self::assertSame('fa-regular fa-comment-dots', $data['interactiveproblemcol4items'][0]['icon']);
    }

    public function test_four_columns_ppt_has_dedicated_markup_styles_and_no_interaction_binding(): void {
        global $CFG;

        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/default.mustache');
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        $js = file_get_contents($CFG->dirroot . '/local/subscriptions/amd/src/showroom.js');

        self::assertStringContainsString('{{#interactiveproblemisfourcolumnsppt}}', $template);
        self::assertStringContainsString('commerce-showroom-interactive-problem__ppt-stage', $template);
        self::assertStringContainsString('commerce-showroom-interactive-problem__ppt-target', $template);
        self::assertStringContainsString('commerce-showroom-interactive-problem__ppt-arrows', $template);
        self::assertStringContainsString('grid-template-columns: 24% 25% 27% 24%;', $css);
        self::assertStringContainsString('.commerce-showroom-interactive-problem--four_columns_ppt', $css);
        self::assertStringContainsString('text-align: center;', $css);
        self::assertStringContainsString('color: #fff;', $css);
        self::assertStringContainsString('color: rgba(255, 255, 255, .92);', $css);
        self::assertStringContainsString("root.dataset.problemPresentation === 'four_columns_ppt'", $js);
    }
}
