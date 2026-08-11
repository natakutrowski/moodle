<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_interactive_problem_j16h7_test extends \advanced_testcase {
    public function test_mobile_tap_hint_is_configurable(): void {
        global $CFG;

        $registry = file_get_contents(
            $CFG->dirroot .
            '/local/subscriptions/classes/commerce/showroom/cms/CommerceShowroomBlockEditorRegistry.php'
        );
        $presenter = file_get_contents(
            $CFG->dirroot .
            '/local/subscriptions/classes/commerce/showroom/cms/CommerceShowroomBlockConfigurationPresenter.php'
        );

        self::assertStringContainsString(
            "self::text('taphint', 'Aide mobile au toucher')",
            $registry
        );
        self::assertStringContainsString(
            "interactiveproblemtaphint",
            $presenter
        );
    }

    public function test_mobile_has_vertical_arrow_and_hides_drag_handle(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/showroom/third_group_verbs.mustache'
        );
        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/showroom.css'
        );

        self::assertStringContainsString(
            'commerce-showroom-interactive-problem__mobile-arrow',
            $template
        );
        self::assertStringContainsString(
            'commerce-showroom-interactive-problem__drag-hint--mobile',
            $template
        );
        self::assertStringContainsString(
            ".commerce-showroom-interactive-problem__drag-handle {\n        display: none !important;",
            $css
        );
        self::assertStringContainsString(
            '.commerce-showroom-interactive-problem__mobile-arrow span::after',
            $css
        );
    }

    public function test_desktop_dynamic_arrows_remain_present(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/showroom/third_group_verbs.mustache'
        );

        self::assertStringContainsString(
            'data-interactive-problem-arrows',
            $template
        );
    }
}
