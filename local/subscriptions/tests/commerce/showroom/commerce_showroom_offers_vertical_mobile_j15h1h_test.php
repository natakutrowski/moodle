<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Showroom offer hierarchy and mobile stacking coverage.
 *
 * @package    local_subscriptions
 * @copyright  2026 CampusFR
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/**
 * Protects the J15H.1H vertical mobile layout and featured bundle styling.
 */
final class commerce_showroom_offers_vertical_mobile_j15h1h_test extends \advanced_testcase {
    public function test_mobile_offers_are_stacked_without_horizontal_slider(): void {
        global $CFG;
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        self::assertStringContainsString('.commerce-showroom-offer--bundle { order: 1 !important; }', $css);
        self::assertStringContainsString('.commerce-showroom-offer--course { order: 2 !important; }', $css);
        self::assertStringContainsString('.commerce-showroom-offer--pdf { order: 3 !important; }', $css);
        self::assertStringContainsString('commerce-showroom-offers--mobile-stack', $css);

    }

    public function test_featured_bundle_has_stronger_desktop_emphasis(): void {
        global $CFG;

        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');

        self::assertIsString($css);
        self::assertStringContainsString('transform: scale(1.065)', $css);
        self::assertStringContainsString('0 0 0 5px rgba(246, 31, 130, .075)', $css);
        self::assertStringContainsString('font-size: .84rem', $css);
    }
}
