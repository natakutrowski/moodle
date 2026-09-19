<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h4_wallet_direct_ux_contract_test extends advanced_testcase {
    public function test_wallet_sheet_can_only_be_triggered_by_real_express_button(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/amd/src/'
            . 'stripe_embedded_card.js'
        );

        $this->assertStringContainsString(
            "elements.create(",
            $js
        );
        $this->assertStringContainsString(
            "'expressCheckout'",
            $js
        );
        $this->assertStringContainsString(
            "expressCheckout.on('confirm'",
            $js
        );
        $this->assertStringContainsString(
            "'availablepaymentmethodschange'",
            $js
        );
    }

    public function test_link_and_klarna_are_ordered_inside_payment_element(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/amd/src/'
            . 'stripe_embedded_card.js'
        );

        $this->assertStringContainsString(
            'config.paymentElementMethods',
            $js
        );
        $this->assertStringContainsString(
            'paymentMethodOrder',
            $js
        );
    }
}
