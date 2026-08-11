<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_learning_method_j16k2_test extends \advanced_testcase {
    public function test_visual_polish_contract_is_present(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/showroom.css'
        );

        self::assertStringContainsString(
            '.commerce-showroom-driving-method__badge {',
            $css
        );
        self::assertStringContainsString('min-height: 2.45rem;', $css);
        self::assertStringContainsString(
            ".commerce-showroom-driving-method__route {\n    z-index: 0;",
            $css
        );
        self::assertStringContainsString(
            'background: rgba(255, 249, 252, .80);',
            $css
        );
        self::assertStringContainsString(
            '.commerce-showroom-driving-method__thought:nth-child(5)::after',
            $css
        );
        self::assertStringContainsString(
            '.commerce-showroom-driving-method__summary-arrow::after',
            $css
        );
    }
}
