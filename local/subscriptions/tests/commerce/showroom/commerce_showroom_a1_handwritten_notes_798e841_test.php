<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

/** 7.98E8.4.1 — shared handwritten typography for A1 decorative notes. */
final class commerce_showroom_a1_handwritten_notes_798e841_test extends \advanced_testcase {
    public function test_postcard_caption_and_audience_note_share_refined_script_stack(): void {
        global $CFG;

        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');

        self::assertIsString($css);
        self::assertStringContainsString('CampusFR 7.98E.8.4.1', $css);
        self::assertStringContainsString(
            '#showroom-adventure .commerce-showroom-adventure-overview__visual figcaption,',
            $css
        );
        self::assertStringContainsString(
            '#showroom-audience-profiles .commerce-showroom-audience-note',
            $css
        );
        self::assertStringContainsString(
            'font-family: "Segoe Script", "Snell Roundhand", "Apple Chancery", "URW Chancery L", "Brush Script MT", cursive;',
            $css
        );
        self::assertStringContainsString('font-weight: 600;', $css);
        self::assertStringContainsString('font-style: italic;', $css);
    }
}
