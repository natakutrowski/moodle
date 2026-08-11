<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_exercise_mobile_order_j16c665_test extends \advanced_testcase {
    public function test_mobile_active_card_comes_before_navigation(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/showroom.css'
        );
        self::assertIsString($css);

        self::assertStringContainsString(
            ".commerce-showroom-exercise-mobile-meta {\n        order: 2;",
            $css
        );
        self::assertStringContainsString(
            ".commerce-showroom-exercise-navigation {\n        order: 3;",
            $css
        );
    }

    public function test_mobile_navigation_controls_come_before_swipe_hint(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/showroom.css'
        );

        self::assertStringContainsString(
            ".commerce-showroom-exercise-navigation__controls {\n        order: 1;",
            $css
        );
        self::assertStringContainsString(
            ".commerce-showroom-exercise-navigation__hint {\n        order: 3;",
            $css
        );
    }
}
