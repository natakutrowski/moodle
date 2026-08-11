<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_stage_method_j16o5_test extends \advanced_testcase {
    public function test_journey_uses_seventy_percent_desktop_container_and_no_step_icons(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions/';
        $template = file_get_contents($root . 'templates/showroom/third_group_verbs.mustache');
        $css = file_get_contents($root . 'styles/showroom.css');

        $start = strpos($template, '{{#isstagemethod}}');
        $end = strpos($template, '{{/isstagemethod}}', $start);
        $stage = substr($template, $start, $end - $start);

        self::assertStringNotContainsString(
            '<span class="commerce-showroom-icon"><i class="{{icon}}"',
            $stage
        );
        self::assertStringContainsString('<i class="fa-solid fa-route" aria-hidden="true"></i>', $stage);
        self::assertStringContainsString('.commerce-showroom-stage-method .commerce-showroom-journey {', $css);
        self::assertStringContainsString('width: 70%;', $css);
        self::assertStringContainsString('grid-template-columns: 48px minmax(0, 1fr);', $css);
    }
}
