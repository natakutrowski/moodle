<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();


final class commerce_showroom_interactive_problem_j16h5_test extends \advanced_testcase {
    public function test_interactive_problem_has_measurable_geometry_hooks(): void {
        global $CFG;
        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/third_group_verbs.mustache');
        $js = file_get_contents($CFG->dirroot . '/local/subscriptions/amd/src/showroom.js');
        self::assertStringContainsString('data-showroom-interactive-problem', $template);
        self::assertStringContainsString('data-interactive-problem-arrows', $template);
        self::assertStringContainsString('data-interactive-problem-cross', $template);
        self::assertStringContainsString('data-interactive-problem-target', $template);
        self::assertStringContainsString('ResizeObserver', $js);
        self::assertStringContainsString('scheduleDraw', $js);
    }
}
