<?php

declare(strict_types=1);

namespace local_subscriptions;

final class commerce_guest_checkout_security_j12e_test extends \advanced_testcase {
    public function test_guest_checkout_assets_and_modal_are_present(): void {
        $root = dirname(__DIR__, 3);
        $checkout = file_get_contents($root . '/commerce_checkout.php');
        $template = file_get_contents($root . '/templates/checkout/page.mustache');
        $result = file_get_contents($root . '/order_result.php');
        $dialog = file_get_contents($root . '/templates/commerce/guest_account_dialog.mustache');
        self::assertStringContainsString('guest_checkout_security', $checkout);
        self::assertStringContainsString('data-guest-checkout-form', $template);
        self::assertStringContainsString("render_from_template(\n        'local_subscriptions/commerce/guest_account_dialog'", $result);
        self::assertStringContainsString('data-guest-account-dialog', $dialog);
        self::assertStringContainsString('accountfinalised', $result);

    }
}
