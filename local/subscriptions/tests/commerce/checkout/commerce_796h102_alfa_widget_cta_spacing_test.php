<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h102_alfa_widget_cta_spacing_test extends advanced_testcase {
    public function test_alfa_executor_uses_distinct_preparation_cta_and_hides_it_after_widget_ready(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/amd/src/checkout_alfa_widget.js'
        );

        foreach ([
            'config.openLabel',
            'config.defaultLabel',
            'submit.hidden = true;',
            'refreshSubmit();',
        ] as $expected) {
            $this->assertStringContainsString($expected, $js);
        }
    }

    public function test_payment_methods_have_breathing_room(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/styles/guest_checkout.css'
        );

        $this->assertStringContainsString(
            '/* H10.2 — Alfa CTA clarity + payment-method breathing room. */',
            $css
        );
        $this->assertStringContainsString(
            'margin-top: 1.2rem;',
            $css
        );
    }
}
