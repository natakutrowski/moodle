<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();


final class commerce_showroom_exercise_mobile_j16c4_test extends \advanced_testcase {
    public function test_template_exposes_single_responsive_exercise_explorer(): void {
        global $CFG;
        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/third_group_verbs.mustache');
        self::assertIsString($template);
        self::assertStringContainsString('{{#isexerciseexplorer}}', $template);
        self::assertStringContainsString('data-showroom-exercise-preview-body', $template);
        self::assertStringContainsString('data-showroom-exercise-navigation', $template);
        self::assertStringContainsString('data-showroom-exercise-mobile-meta', $template);
    }

    public function test_javascript_keeps_preview_navigation_and_preload_in_one_controller(): void {
        global $CFG;
        $js = file_get_contents($CFG->dirroot . '/local/subscriptions/amd/src/showroom.js');
        self::assertStringContainsString('const preloadAll=', $js);
        self::assertStringContainsString('const renderPreview=async', $js);
        self::assertStringContainsString('activationToken', $js);
        self::assertStringContainsString("button.addEventListener('click',()=>activate(button))", $js);
    }
}
