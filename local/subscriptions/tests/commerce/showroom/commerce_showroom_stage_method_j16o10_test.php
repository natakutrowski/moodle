<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_stage_method_j16o10_test extends \advanced_testcase {
    public function test_route_crop_and_mobile_split_contract(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions/';
        $template = file_get_contents($root . 'templates/showroom/third_group_verbs.mustache');
        $css = file_get_contents($root . 'styles/showroom.css');

        self::assertStringContainsString('commerce-showroom-stage-method__mobile-goat', $template);
        self::assertStringContainsString(
            '/* J16O13 — local Journey segments: no parent rail, no cross-context z-index fight. */',
            $css
        );
        self::assertStringContainsString('width: 111.1112% !important;', $css);
        self::assertStringContainsString('width: 200% !important;', $css);
        self::assertStringContainsString('.commerce-showroom-stage-method__mobile-goat img', $css);
    }
}
