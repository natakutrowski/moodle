<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h92_stripe_link_intent_contract_test extends advanced_testcase {
    public function test_link_payment_intent_uses_card_and_link(): void {
        global $CFG;

        $gateway = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/payment/provider/stripe/'
            . 'LegacyStripePaymentGateway.php'
        );

        $this->assertStringContainsString(
            "'link' => ['card', 'link']",
            $gateway
        );
        $this->assertStringContainsString(
            "'klarna' => ['klarna']",
            $gateway
        );
    }
}
