<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_797l735_guest_failed_cart_continuity_test extends \advanced_testcase {
    public function test_payment_failed_remains_authoritative_for_guest_cart_and_recovery(): void {
        global $CFG;

        $resolver = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/checkout/guest/'
            . 'CommerceGuestCartCustomerResolver.php'
        );
        $recovery = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/checkout/guest/'
            . 'CommerceGuestCartRecoveryService.php'
        );

        self::assertStringContainsString("'payment_failed',", $resolver);
        self::assertStringContainsString("'payment_failed',", $recovery);
    }

    public function test_empty_cart_continue_shopping_cta_is_vertically_centred(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/storefront.css'
        );

        self::assertStringContainsString('.commerce-cart-empty .btn {', $css);
        self::assertStringContainsString('align-items: center;', $css);
        self::assertStringContainsString('justify-content: center;', $css);
    }
}
