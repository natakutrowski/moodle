<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();


final class commerce_showroom_exercise_renderer_j16c3_test extends \advanced_testcase {
    public function test_exercise_controller_preloads_and_is_race_safe(): void {
        global $CFG;
        $js = file_get_contents($CFG->dirroot . '/local/subscriptions/amd/src/showroom.js');
        self::assertIsString($js);
        self::assertStringContainsString('previewCache', $js);
        self::assertStringContainsString('activationToken', $js);
        self::assertStringContainsString('const preloadAll=', $js);
        self::assertStringContainsString('const renderPreview=async', $js);
        self::assertStringContainsString('token!==activationToken', $js);
    }
}
