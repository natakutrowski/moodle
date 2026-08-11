<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();


final class commerce_showroom_exercise_builder_ux_j16c62_test extends \advanced_testcase {
    public function test_current_exercise_builder_is_multilingual_and_media_aware(): void {
        global $CFG;
        $js = file_get_contents($CFG->dirroot . '/local/subscriptions/js/showroom_builder.js');
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom_builder.css');
        self::assertIsString($js);
        self::assertIsString($css);
        self::assertStringContainsString('commerce-showroom-language-tabs', $js);
        self::assertStringContainsString('exerciseFallback', $js);
        self::assertStringContainsString('createExerciseMediaSlot', $js);
        self::assertStringContainsString('.commerce-showroom-exercise-editor__accordions', $css);
        self::assertStringContainsString('.commerce-showroom-exercise-media-slot', $css);
    }
}
