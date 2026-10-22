<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

/** 7.98C.3.3.1 — symbolic Hero stats must never be rewritten as numeric counters. */
final class commerce_showroom_articles_stats_symbols_798c331_test extends \advanced_testcase {
    public function test_stats_counter_binding_respects_animate_flag_and_ignores_non_numeric_values(): void {
        global $CFG;

        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/default.mustache');
        $js = file_get_contents($CFG->dirroot . '/local/subscriptions/amd/src/showroom.js');

        self::assertIsString($template);
        self::assertIsString($js);
        self::assertStringContainsString('{{#statsanimate}}data-showroom-counter', $template);
        self::assertStringContainsString('{{/statsanimate}}>{{herostat1value}}', $template);
        self::assertStringContainsString("return /[0-9]/.test(raw);", $js);
        self::assertStringContainsString("entry.target.textContent = '0';", $js);
    }
}
