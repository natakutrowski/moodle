<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

/**
 * @coversNothing
 */
final class commerce_showroom_a1_tutor_chat_798e872_test extends \advanced_testcase {
    public function test_tutor_template_and_css_include_badge_icon_wider_chat_offset_and_rotation(): void {
        global $CFG;

        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/block_tutor_chat.mustache');
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');

        self::assertIsString($template);
        self::assertStringContainsString('fa-solid fa-comments', $template);
        self::assertStringContainsString('commerce-showroom-tutor-chat__badge', $template);

        self::assertIsString($css);
        self::assertStringContainsString('CampusFR 7.98E.8.7.2', $css);
        self::assertStringContainsString('width: min(100%, 27rem);', $css);
        self::assertStringContainsString('margin: -5rem -1rem 0 auto;', $css);
        self::assertStringContainsString('transform: rotate(-3deg);', $css);
        self::assertStringContainsString('align-self: flex-start;', $css);
        self::assertStringContainsString('align-self: flex-end;', $css);
    }
}
