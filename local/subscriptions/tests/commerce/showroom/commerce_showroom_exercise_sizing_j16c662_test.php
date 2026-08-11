<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_exercise_sizing_j16c662_test extends \advanced_testcase {
    public function test_exercise_cards_have_requested_micro_spacing(): void {
        global $CFG;

        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        self::assertIsString($css);
        self::assertStringContainsString(
            'padding: calc(.62rem + 2px) calc(.78rem + 2px);',
            $css
        );
        self::assertStringContainsString('margin-block: 1px;', $css);
    }

    public function test_preview_is_ninety_percent_and_centered(): void {
        global $CFG;

        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        self::assertIsString($css);
        self::assertStringContainsString('width: 90%;', $css);
        self::assertStringContainsString('margin: 0 auto;', $css);
    }
}
