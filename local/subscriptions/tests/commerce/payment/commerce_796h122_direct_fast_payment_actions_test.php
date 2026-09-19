<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h122_direct_fast_payment_actions_test extends advanced_testcase {
    public function test_alfa_pay_and_sbp_are_direct_actions_not_radio_cards(): void {
        global $CFG;

        $intent = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_payment_intent.js'
        );
        $presenter = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/unified/presentation/'
            . 'CommerceCheckoutPaymentMethodPresenter.php'
        );
        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );

        self::assertStringContainsString(
            '[CommercePaymentMethod::ALFA_PAY, CommercePaymentMethod::SBP]',
            $presenter
        );
        self::assertStringContainsString("'quickmethods' => \$quick", $presenter);
        self::assertStringContainsString("'hasquickmethods' => \$quick !== []", $presenter);
        self::assertStringContainsString('data-quick-payment-action="{{key}}"', $template);
        self::assertStringContainsString('data-quick-payment-radio="{{key}}"', $template);
        self::assertStringContainsString('form.requestSubmit(', $intent);
    }

    public function test_quick_action_uses_normal_form_validation_and_existing_rails(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $intent = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_payment_intent.js'
        );
        $action = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout_action.php'
        );

        // requestSubmit keeps browser validation active, including identity and CGV.
        self::assertStringContainsString('requestSubmit', $intent);
        self::assertStringContainsString('data-checkout-terms', $template);
        self::assertStringContainsString('paymentmethod', $action);
        self::assertStringContainsString('route_for(', $action);
        self::assertStringContainsString("=== 'alfa_sbp'", $action);
    }

    public function test_link_and_klarna_wordmarks_survive_unified_action_cards(): void {
        global $CFG;

        $presenter = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/unified/presentation/'
            . 'CommerceCheckoutPaymentMethodPresenter.php'
        );
        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );

        self::assertStringContainsString(
            "CommercePaymentMethod::LINK => 'link.svg'",
            $presenter
        );
        self::assertStringContainsString(
            "CommercePaymentMethod::KLARNA => 'klarna.svg'",
            $presenter
        );
        self::assertStringContainsString(
            '{{#hasiconurl}}<img src="{{iconurl}}" alt="">{{/hasiconurl}}',
            $template
        );
    }
}
