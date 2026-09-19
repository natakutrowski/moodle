<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_797l733_guest_failed_payment_retry_test extends \advanced_testcase {
    public function test_failed_guest_payment_remains_retryable_without_reverification(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        $resolver = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/checkout/guest/'
            . 'CommerceCheckoutIdentityResolver.php'
        );
        $gate = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/checkout/guest/'
            . 'CommerceGuestPaymentGate.php'
        );

        self::assertStringContainsString(
            "['provisional', 'payment_pending', 'payment_failed']",
            $checkout
        );
        self::assertStringContainsString(
            "['provisional', 'payment_pending', 'payment_failed']",
            $resolver
        );
        self::assertStringContainsString(
            "'payment_failed',",
            $gate
        );
    }

    public function test_storefront_links_use_real_cta_selectors_for_vertical_alignment(): void {
        global $CFG;

        $css = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/styles/storefront.css'
        );

        self::assertStringContainsString(
            '.commerce-product-card__cart-actions > .btn,',
            $css
        );
        self::assertStringContainsString(
            '.commerce-storefront__filters .commerce-storefront__reset',
            $css
        );
        self::assertStringContainsString(
            'min-height: 2.75rem;',
            $css
        );
    }
}
