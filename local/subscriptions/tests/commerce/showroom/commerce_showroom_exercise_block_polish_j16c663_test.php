<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_exercise_block_polish_j16c663_test extends \advanced_testcase {
    public function test_exercise_section_applies_builder_layout_class(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/showroom/third_group_verbs.mustache'
        );
        self::assertIsString($template);
        self::assertStringContainsString(
            'commerce-showroom-section--soft {{exerciseexplorerlayoutclass}}',
            $template
        );
    }

    public function test_builder_and_presenter_support_explicit_white_background(): void {
        global $CFG;

        $registry = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/showroom/cms/CommerceShowroomBlockEditorRegistry.php'
        );
        $presenter = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/showroom/cms/CommerceShowroomBlockConfigurationPresenter.php'
        );

        self::assertStringContainsString("'white' => 'Blanc'", $registry);
        self::assertStringContainsString(
            "['default', 'white', 'light', 'soft', 'campuspink', 'dark', 'gradient', 'custom', 'image']",
            $presenter
        );
        self::assertStringContainsString("'white'\n        );", $presenter);
    }

    public function test_exercise_cards_use_subtle_pink_border_and_hint_is_centered(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/showroom.css'
        );
        self::assertIsString($css);
        self::assertStringContainsString(
            'border: 1px solid rgba(217, 48, 121, .14);',
            $css
        );
        self::assertStringContainsString('justify-content: center;', $css);
        self::assertStringContainsString(
            '.commerce-showroom-background--white { background: #fff; }',
            $css
        );
    }
}
