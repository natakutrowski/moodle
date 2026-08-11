<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_interactive_problem_j16h8_test extends \advanced_testcase {
    public function test_consequence_text_is_slightly_darker(): void {
        global $CFG;
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');

        self::assertStringContainsString(
            ".commerce-showroom-interactive-problem__consequence p {\n    color: #493f55;",
            $css
        );
    }

    public function test_mobile_arrow_reaches_lower(): void {
        global $CFG;
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');

        self::assertStringContainsString('height: 3.85rem;', $css);
        self::assertStringContainsString('height: 2.75rem;', $css);
    }
}
