<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h51_embedded_card_localisation_prefill_test extends advanced_testcase {
    public function test_checkout_passes_moodle_locale_to_stripe(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );

        $this->assertStringContainsString(
            "'locale' => current_language()",
            $checkout
        );
    }

    public function test_payment_element_prefills_existing_identity(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_inline_card.js'
        );

        foreach ([
            'locale:',
            'config.locale',
            'defaultValues:',
            'billingDetails:',
            '[name="email"]',
            '[name="firstname"]',
            '[name="lastname"]',
            'email,',
            'name: fullname',
        ] as $expected) {
            $this->assertStringContainsString($expected, $js);
        }
    }

    public function test_inline_card_has_space_before_reassurance(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/guest_checkout.css'
        );

        $this->assertStringContainsString(
            '.commerce-checkout-inline-card'
            . PHP_EOL
            . '    + .commerce-checkout-trust-strip',
            $css
        );
        $this->assertStringContainsString(
            'margin-top: 1.35rem;',
            $css
        );
    }
}
