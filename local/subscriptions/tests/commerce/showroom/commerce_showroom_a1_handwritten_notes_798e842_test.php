<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_a1_handwritten_notes_798e842_test extends \advanced_testcase {
    public function test_showroom_loads_review_override_stylesheet(): void {
        global $CFG;

        $showroom = (string)file_get_contents($CFG->dirroot . '/local/subscriptions/showroom.php');
        self::assertStringContainsString('styles/showroom_a1_review_overrides.css', $showroom);
    }

    public function test_handwritten_notes_use_caveat_and_preserve_line_breaks(): void {
        global $CFG;

        $css = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/showroom_a1_review_overrides.css'
        );

        self::assertStringContainsString('CampusFR 7.98E.8.4.2', $css);
        self::assertStringContainsString('font-family: "Caveat", cursive !important;', $css);
        self::assertStringContainsString('#showroom-adventure .commerce-showroom-adventure-overview__visual figcaption', $css);
        self::assertStringContainsString('#showroom-audience-profiles .commerce-showroom-audience-note', $css);
        self::assertStringContainsString('white-space: pre-line;', $css);
    }
}
