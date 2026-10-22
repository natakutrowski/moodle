<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

/**
 * @coversNothing
 */
final class commerce_showroom_a1_tutor_chat_798e874_test extends \advanced_testcase {
    public function test_e874_keeps_certification_marker_and_polishes_desktop_tutor_layout(): void {
        global $CFG;

        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');

        self::assertIsString($css);
        self::assertStringContainsString('CampusFR 7.98E.8.7.1', $css);
        self::assertStringContainsString('CampusFR 7.98E.8.7.4', $css);
        self::assertStringContainsString('width: min(1320px, calc(100% - 2rem));', $css);
        self::assertStringContainsString('grid-template-columns: minmax(0, 1.08fr) minmax(400px, .92fr);', $css);
        self::assertStringContainsString('top: 58%;', $css);
        self::assertStringContainsString('right: 27.8rem;', $css);
        self::assertStringContainsString('padding-left: 1.5rem;', $css);
    }
}
