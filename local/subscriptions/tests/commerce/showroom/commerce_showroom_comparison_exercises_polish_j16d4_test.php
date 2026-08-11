<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();


final class commerce_showroom_comparison_exercises_polish_j16d4_test extends \advanced_testcase {
    public function test_current_comparison_contract_is_present(): void {
        global $CFG;
        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/third_group_verbs.mustache');
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        self::assertIsString($template);
        self::assertIsString($css);
        self::assertStringContainsString('{{#iscomparison}}', $template);
        self::assertStringContainsString('commerce-showroom-comparison__table', $css);
        self::assertStringContainsString('--showroom-comparison-check: #1f9d65;', $css);
        self::assertStringContainsString('commerce-showroom-comparison__recommended', $css);
        self::assertStringContainsString('commerce-showroom-comparison-mobile__recommended', $css);
    }
}
