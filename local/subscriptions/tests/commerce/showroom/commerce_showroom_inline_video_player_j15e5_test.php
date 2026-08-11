<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Inline Showroom video player coverage.
 *
 * @package    local_subscriptions
 * @copyright  2026 CampusFR
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/**
 * Protects the public inline video controls and first-frame fallback markup.
 */
final class commerce_showroom_inline_video_player_j15e5_test extends \advanced_testcase {
    public function test_inline_video_markup_contains_accessible_states_and_fallback(): void {
        global $CFG;
        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/third_group_verbs.mustache');
        self::assertStringContainsString('preload="metadata"', $template);
        self::assertStringContainsString('data-showroom-inline-video-control', $template);
        self::assertStringContainsString('data-replay-label', $template);
        self::assertStringContainsString('aria-label', $template);

    }

    public function test_showroom_javascript_manages_video_states(): void {
        global $CFG;

        $javascript = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/showroom.js'
        );

        self::assertIsString($javascript);
        self::assertStringContainsString('const bindInlineVideos', $javascript);
        self::assertStringContainsString("setState('replay')", $javascript);
        self::assertStringNotContainsString("setState('pause')", $javascript);
        self::assertStringContainsString("setState('play')", $javascript);
        self::assertStringContainsString('firstFrameFallback', $javascript);
    }
}
