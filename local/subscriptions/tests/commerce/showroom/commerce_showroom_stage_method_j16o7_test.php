<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_stage_method_j16o7_test extends \advanced_testcase {
    public function test_journey_visual_contract(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions/';
        $template = file_get_contents($root . 'templates/showroom/third_group_verbs.mustache');
        $css = file_get_contents($root . 'styles/showroom.css');

        self::assertStringContainsString(
            'commerce-showroom-ascent__badge commerce-showroom-stage-method__badge',
            $template
        );
        self::assertStringContainsString(
            '<i class="fa-solid fa-route" aria-hidden="true"></i>',
            $template
        );
        self::assertStringContainsString('commerce-showroom-journey__step--rest', $template);
        self::assertStringContainsString('fa-solid fa-mug-hot', $template);
        self::assertStringContainsString('object-fit: cover;', $css);
        self::assertStringContainsString('object-position: right center;', $css);
        self::assertStringContainsString(
            'grid-template-columns: 48px minmax(0, 1fr) 68px;',
            $css
        );
        self::assertStringContainsString('color: var(--showroom-pink);', $css);
    }
}
