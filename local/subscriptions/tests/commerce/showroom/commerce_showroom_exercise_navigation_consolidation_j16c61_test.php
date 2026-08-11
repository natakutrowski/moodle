<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();


final class commerce_showroom_exercise_navigation_consolidation_j16c61_test extends \advanced_testcase {
    public function test_current_exercise_explorer_layout_contract(): void {
        global $CFG;
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        self::assertIsString($css);
        self::assertStringContainsString('.commerce-showroom-explorer', $css);
        self::assertStringContainsString('.commerce-showroom-exercises__desktop-hint', $css);
        self::assertStringContainsString('.commerce-showroom-exercise-preview', $css);
        self::assertStringContainsString('.commerce-showroom-exercise-navigation__controls', $css);
        self::assertStringContainsString('.commerce-showroom-exercise-mobile-meta', $css);
        self::assertStringContainsString('touch-action: pan-y;', $css);
    }
}
