<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_learning_method_j16k3_test extends \advanced_testcase {
    public function test_route_extends_outside_cards_and_bubble_tails_protrude(): void {
        global $CFG;

        $root = $CFG->dirroot . '/local/subscriptions/';
        $template = file_get_contents(
            $root . 'templates/showroom/third_group_verbs.mustache'
        );
        $css = file_get_contents($root . 'styles/showroom.css');

        self::assertStringContainsString(
            'viewBox="-90 0 1380 170"',
            $template
        );
        self::assertStringContainsString(
            'M1270 20 L1270 72',
            $template
        );
        self::assertStringContainsString(
            'background: rgba(255, 249, 252, .64);',
            $css
        );
        self::assertStringContainsString(
            '.commerce-showroom-driving-method__thought:nth-child(2)::after',
            $css
        );
        self::assertStringContainsString(
            'bottom: -8px;',
            $css
        );
        self::assertStringContainsString(
            'width: 115%;',
            $css
        );
    }
}
