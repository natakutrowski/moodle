<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();


final class commerce_showroom_bundle_height_j16a5_test extends \advanced_testcase {
    public function test_featured_bundle_uses_height_and_hover_lift_without_scale_enlargement(): void {
        global $CFG;
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        self::assertIsString($css);
        self::assertMatchesRegularExpression('/\\.commerce-showroom-offer--bundle\\.is-featured\\s*\\{[^}]*height:\\s*calc\\(100% \\+ 1\\.5rem\\)/s', $css);
        self::assertMatchesRegularExpression('/\\.commerce-showroom-offer--bundle\\.is-featured:hover[^{]*\\{[^}]*translateY\\(-16px\\)/s', $css);
        self::assertStringContainsString('transform: none !important;', $css);
    }
}
