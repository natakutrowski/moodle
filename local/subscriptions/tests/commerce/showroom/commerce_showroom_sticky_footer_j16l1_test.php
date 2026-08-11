<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();


final class commerce_showroom_sticky_footer_j16l1_test extends \advanced_testcase {
    public function test_current_sticky_footer_contract_is_responsive_and_comparison_driven(): void {
        global $CFG;
        $css = file_get_contents($CFG->dirroot . '/local/subscriptions/styles/showroom.css');
        $js = file_get_contents($CFG->dirroot . '/local/subscriptions/amd/src/showroom.js');
        self::assertStringContainsString('.commerce-showroom-sticky-cta', $css);
        self::assertStringContainsString('border-radius: 999px !important;', $css);
        self::assertStringContainsString("const SELECTOR_STICKY = '[data-showroom-sticky-cta]'", $js);
        self::assertStringContainsString('comparisonPassed', $js);
    }
}
