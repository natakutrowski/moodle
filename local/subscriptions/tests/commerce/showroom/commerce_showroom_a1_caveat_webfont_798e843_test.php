<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_a1_caveat_webfont_798e843_test extends \advanced_testcase {
    public function test_a1_showroom_loads_real_caveat_webfont(): void {
        global $CFG;

        $showroom = file_get_contents($CFG->dirroot . '/local/subscriptions/showroom.php');
        self::assertIsString($showroom);
        self::assertStringContainsString('$definition->get_key() === \'a1-course\'', $showroom);
        self::assertStringContainsString(
            'https://fonts.googleapis.com/css2?family=Caveat:wght@400;500;600;700&display=swap',
            $showroom
        );
        self::assertStringContainsString('https://fonts.gstatic.com', $showroom);
        self::assertStringContainsString("'crossorigin' => 'anonymous'", $showroom);
    }

    public function test_a1_handwritten_notes_use_caveat_without_legacy_font_override(): void {
        global $CFG;

        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        self::assertIsString($css);
        self::assertStringContainsString('CampusFR 7.98E.8.4.4', $css);
        self::assertStringContainsString('font-family: "Caveat", cursive;', $css);
        self::assertStringContainsString('white-space: pre-line;', $css);
        self::assertStringNotContainsString(
            'font-family: "Segoe Script", "Snell Roundhand", "Apple Chancery", "URW Chancery L", "Brush Script MT", cursive;',
            $css
        );
    }
}
