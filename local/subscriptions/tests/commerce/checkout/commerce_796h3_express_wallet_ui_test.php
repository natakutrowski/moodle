<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h3_express_wallet_ui_test extends advanced_testcase {
    public function test_embedded_page_mounts_express_checkout_element(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/checkout/embedded_card.mustache'
        );
        $js = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/amd/src/stripe_embedded_card.js'
        );

        $this->assertStringContainsString(
            'data-stripe-express-section',
            $template
        );
        $this->assertStringContainsString(
            "'expressCheckout'",
            $js
        );
        $this->assertStringContainsString(
            "'availablepaymentmethodschange'",
            $js
        );
        $this->assertStringContainsString(
            "expressCheckout.on('confirm'",
            $js
        );
    }

    public function test_wallet_section_stays_hidden_when_no_wallet_is_available(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/checkout/embedded_card.mustache'
        );
        $js = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/amd/src/stripe_embedded_card.js'
        );

        $this->assertStringContainsString(
            'data-stripe-express-section',
            $template
        );
        $this->assertStringContainsString(
            'hidden>',
            $template
        );
        $this->assertStringContainsString(
            'expressSection.hidden = !hasWallet',
            $js
        );
    }
}
