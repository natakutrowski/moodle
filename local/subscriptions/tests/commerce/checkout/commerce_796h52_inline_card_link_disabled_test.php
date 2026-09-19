<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h52_inline_card_link_disabled_test extends advanced_testcase {
    public function test_card_payment_element_explicitly_disables_link_and_wallets(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/amd/src/checkout_inline_card.js'
        );

        foreach ([
            "applePay: 'never'",
            "googlePay: 'never'",
            "link: 'never'",
            "paymentMethodOrder:",
            "['card']",
        ] as $expected) {
            $this->assertStringContainsString(
                $expected,
                $js
            );
        }
    }

    public function test_h52_keeps_identity_prefill_and_locale(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/amd/src/checkout_inline_card.js'
        );

        foreach ([
            'config.locale',
            'defaultValues:',
            'billingDetails:',
            '[name="email"]',
            '[name="firstname"]',
            '[name="lastname"]',
        ] as $expected) {
            $this->assertStringContainsString(
                $expected,
                $js
            );
        }
    }
}
