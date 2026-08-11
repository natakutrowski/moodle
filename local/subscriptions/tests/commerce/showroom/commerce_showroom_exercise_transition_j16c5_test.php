<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_exercise_transition_j16c5_test extends \advanced_testcase {
    public function test_public_template_contains_non_blocking_loading_layer(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/showroom/third_group_verbs.mustache'
        );
        self::assertIsString($template);

        self::assertStringContainsString('data-showroom-exercise-preview-body', $template);
        self::assertStringContainsString('data-showroom-exercise-preview-loading', $template);
        self::assertStringContainsString('decoding="async"', $template);
    }

    public function test_css_has_subtle_transition_and_reduced_motion_support(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/showroom.css'
        );
        self::assertIsString($css);

        self::assertStringContainsString(
            '.commerce-showroom-exercise-preview__body.is-switching',
            $css
        );
        self::assertStringContainsString(
            '@media (prefers-reduced-motion: reduce)',
            $css
        );
        self::assertStringContainsString(
            '@keyframes commerce-showroom-exercise-spin',
            $css
        );
    }
}
