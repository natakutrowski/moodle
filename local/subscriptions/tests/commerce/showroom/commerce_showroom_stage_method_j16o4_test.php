<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_stage_method_j16o4_test extends \advanced_testcase {
    public function test_journey_cards_are_text_first_and_desktop_column_is_seventy_percent(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions/';
        $css = file_get_contents($root . 'styles/showroom.css');
        $template = file_get_contents($root . 'templates/showroom/third_group_verbs.mustache');

        self::assertStringContainsString('width: 70%;', $css);
        self::assertStringContainsString('max-width: 70%;', $css);
        self::assertStringContainsString('margin: 0 auto 0 0;', $css);
        self::assertStringContainsString('grid-template-columns: 48px minmax(0, 1fr);', $css);
        self::assertStringContainsString('fa-solid fa-route', $template);
    }
}
