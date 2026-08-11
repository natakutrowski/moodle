<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Showroom mobile and video stabilisation coverage.
 *
 * @package    local_subscriptions
 * @copyright  2026 CampusFR
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/**
 * Protects the certified mobile offer layout and simplified video overlay.
 */
final class commerce_showroom_stabilisation_j15e6_test extends \advanced_testcase {
    public function test_mobile_offers_use_accessible_vertical_layout(): void {
        global $CFG;

        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');

        self::assertIsString($css);
        self::assertStringContainsString('grid-auto-flow: row', $css);
        self::assertStringContainsString('scroll-snap-type: none', $css);
        self::assertStringContainsString('overflow: visible', $css);
    }

    public function test_playing_video_does_not_expose_a_custom_pause_action(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/showroom/third_group_verbs.mustache'
        );
        $javascript = file_get_contents($CFG->dirroot . '/local/subscriptions/amd/src/showroom.js');

        self::assertIsString($template);
        self::assertIsString($javascript);
        self::assertStringNotContainsString('data-pause-label=', $template);
        self::assertStringNotContainsString('overlay-icon--pause', $template);
        self::assertStringNotContainsString("setState('pause')", $javascript);
        self::assertStringContainsString('Native controls handle pausing', $javascript);
    }
}
