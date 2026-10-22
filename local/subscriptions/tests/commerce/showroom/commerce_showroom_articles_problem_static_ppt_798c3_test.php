<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockConfigurationPresenter;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockEditorRegistry;

final class commerce_showroom_articles_problem_static_ppt_798c3_test extends \advanced_testcase {
    public function test_editor_exposes_static_ppt_presentation_without_changing_default(): void {
        $schema = CommerceShowroomBlockEditorRegistry::schema('problem_interactive');
        $presentation = null;
        foreach (($schema['fields'] ?? []) as $field) {
            if (($field['name'] ?? '') === 'presentation') {
                $presentation = $field;
                break;
            }
        }

        self::assertIsArray($presentation);
        self::assertSame('select', $presentation['type']);
        self::assertArrayHasKey('interactive', $presentation['options']);
        self::assertArrayHasKey('static_ppt', $presentation['options']);
    }

    public function test_presenter_defaults_to_interactive_and_accepts_static_ppt(): void {
        $reflection = new \ReflectionClass(CommerceShowroomBlockConfigurationPresenter::class);
        $presenter = $reflection->newInstance();
        $method = $reflection->getMethod('apply_problem_interactive');
        $method->setAccessible(true);

        $interactive = $method->invoke($presenter, [], []);
        self::assertSame('interactive', $interactive['interactiveproblempresentation']);
        self::assertTrue($interactive['interactiveproblemisinteractive']);
        self::assertFalse($interactive['interactiveproblemispresentation']);

        $static = $method->invoke($presenter, [], ['presentation' => 'static_ppt']);
        self::assertSame('static_ppt', $static['interactiveproblempresentation']);
        self::assertTrue($static['interactiveproblemispresentation']);
        self::assertFalse($static['interactiveproblemisinteractive']);
        self::assertSame(
            'commerce-showroom-interactive-problem--static_ppt',
            $static['interactiveproblemvariantclass']
        );
    }

    public function test_static_ppt_keeps_circle_and_dynamic_arrows_but_disables_interaction(): void {
        global $CFG;

        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/default.mustache');
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        $js = file_get_contents($CFG->dirroot . '/local/subscriptions/amd/src/showroom.js');

        self::assertStringContainsString('data-interactive-problem-arrows', $template);
        self::assertStringContainsString('commerce-showroom-interactive-problem__target-icon', $template);
        self::assertStringContainsString('{{interactiveproblemvariantclass}}', $template);
        self::assertStringContainsString('data-problem-presentation="{{interactiveproblempresentation}}"', $template);
        self::assertStringContainsString('.commerce-showroom-interactive-problem--static_ppt', $css);
        self::assertStringContainsString("root.dataset.problemPresentation === 'static_ppt'", $js);
    }
}
