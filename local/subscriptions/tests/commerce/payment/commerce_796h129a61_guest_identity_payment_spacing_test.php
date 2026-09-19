<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h129a61_guest_identity_payment_spacing_test extends \advanced_testcase {
    public function test_guest_identity_has_explicit_spacing_hook(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/guest_checkout.css'
        );

        self::assertStringContainsString(
            'data-checkout-guest-identity',
            $template
        );
        self::assertStringContainsString(
            '.commerce-checkout-identity[data-checkout-guest-identity]',
            $css
        );
        self::assertStringContainsString(
            'margin-bottom: 1rem !important;',
            $css
        );
    }
}
