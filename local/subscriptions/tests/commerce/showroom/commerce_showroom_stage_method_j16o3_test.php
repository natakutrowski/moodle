<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_stage_method_j16o3_test extends \advanced_testcase {
    public function test_journey_badge_uses_route_icon_and_shared_visual_contract(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions/';
        $template = file_get_contents($root . 'templates/showroom/third_group_verbs.mustache');
        $css = file_get_contents($root . 'styles/showroom.css');

        self::assertStringContainsString('<i class="fa-solid fa-route" aria-hidden="true"></i>', $template);
        self::assertStringContainsString('commerce-showroom-stage-method__badge', $css);
        self::assertStringContainsString('commerce-showroom-ascent__badge', $css);
    }
}
