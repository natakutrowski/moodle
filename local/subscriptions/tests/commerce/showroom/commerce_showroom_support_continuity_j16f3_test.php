<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();


final class commerce_showroom_support_continuity_j16f3_test extends \advanced_testcase {
    public function test_faq_and_support_spacing_contract_is_kept_together(): void {
        global $CFG;
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        self::assertStringContainsString('.commerce-showroom-section.commerce-showroom-faq', $css);
        self::assertStringContainsString('.commerce-showroom-support.commerce-showroom-spacing--normal', $css);
        self::assertStringContainsString('content-visibility: visible;', $css);
    }
}
