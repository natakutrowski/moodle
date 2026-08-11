<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_learning_method_j16k4_test extends \advanced_testcase {
    public function test_route_markers_are_on_curve_and_between_cards(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/showroom/third_group_verbs.mustache'
        );

        self::assertStringContainsString(
            'M-58 100 C70 42,245 145,395 88 S650 40,805 100 S1080 150,1270 72',
            $template
        );

        // Central markers correspond exactly to curve segment endpoints.
        self::assertStringContainsString(
            '<circle cx="395" cy="88" r="5"></circle>',
            $template
        );
        self::assertStringContainsString(
            '<circle cx="805" cy="100" r="5"></circle>',
            $template
        );

        // The finish is represented by the flag only: no redundant finish circle.
        self::assertStringNotContainsString(
            '<circle cx="1260" cy="72" r="5"></circle>',
            $template
        );
        self::assertStringContainsString(
            'M1270 20 L1270 72',
            $template
        );
    }
}
