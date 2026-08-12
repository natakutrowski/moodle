<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_personal_offer_checkout_context_m3h10_test extends \advanced_testcase {
    public function test_direct_checkout_back_link_targets_showroom_for_showroom_campaign(): void {
        global $CFG;

        $source = (string)file_get_contents($CFG->dirroot . '/local/subscriptions/offer.php');

        $this->assertStringContainsString('$campaigndestination = $destination;', $source);
        $this->assertStringContainsString('CommerceShowroomUrl::make(', $source);
        $this->assertStringContainsString("\$checkoutparams['originreturn'] = \$originreturn", $source);
        $this->assertStringNotContainsString(
            "'originreturn' => '/local/subscriptions/offer.php?token='",
            $source
        );
    }

    public function test_showroom_buy_now_promotes_checkout_source_to_personal_offer_after_server_validation(): void {
        global $CFG;

        $source = (string)file_get_contents($CFG->dirroot . '/local/subscriptions/cart_action.php');

        $this->assertStringContainsString(
            "\$metadata = array_replace(\$metadata, \$personal['metadata']);",
            $source
        );
        $this->assertStringContainsString("\$source = 'personaloffer';", $source);
        $this->assertStringContainsString("\$checkoutparams['source'] = \$source;", $source);
        $this->assertStringContainsString("\$checkoutparams['showroom'] = \$showroom;", $source);
        $this->assertStringContainsString("\$checkoutparams['originreturn'] = \$returnurl;", $source);
    }

    public function test_checkout_personal_offer_ui_is_driven_by_personaloffer_source_and_cart_metadata(): void {
        global $CFG;

        $checkout = (string)file_get_contents($CFG->dirroot . '/local/subscriptions/commerce_checkout.php');

        $this->assertStringContainsString("\$ispersonaloffer = \$source === 'personaloffer';", $checkout);
        $this->assertStringContainsString('get_cart_offer((int)$customerid, $currency)', $checkout);
        $this->assertStringContainsString('get_beneficiary_identity($personaloffer)', $checkout);
        $this->assertStringContainsString('get_available_currencies($personaloffer)', $checkout);
    }
}
