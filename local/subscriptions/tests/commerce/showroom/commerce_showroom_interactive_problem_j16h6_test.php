<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_interactive_problem_j16h6_test extends \advanced_testcase {
    public function test_passive_ellipsis_row_is_not_a_problem_choice(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/showroom/third_group_verbs.mustache'
        );

        self::assertIsString($template);
        self::assertStringContainsString(
            'commerce-showroom-interactive-problem__choice--passive',
            $template
        );
        self::assertStringContainsString('<strong>...</strong>', $template);

        $passivepos = strpos(
            $template,
            'commerce-showroom-interactive-problem__choice--passive'
        );
        self::assertNotFalse($passivepos);

        $fragment = substr($template, $passivepos - 120, 500);
        self::assertStringNotContainsString('data-problem-choice', $fragment);
        self::assertStringNotContainsString('draggable="true"', $fragment);
        self::assertStringNotContainsString('<button', $fragment);
    }

    public function test_correct_answer_stays_visible_longer(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/showroom.js'
        );

        self::assertIsString($js);
        self::assertStringContainsString(
            '? (prefersReducedMotion() ? 3200 : 2600)',
            $js
        );
        self::assertStringContainsString(
            ': (prefersReducedMotion() ? 2400 : 1500);',
            $js
        );
        self::assertStringContainsString(
            '}, resetDelay);',
            $js
        );
    }

    public function test_dynamic_arrow_code_is_left_in_place(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/showroom.js'
        );

        self::assertStringContainsString(
            'const bindInteractiveProblemArrows = () => {',
            $js
        );
        self::assertStringContainsString(
            'bindInteractiveProblemArrows();',
            $js
        );
    }
}
