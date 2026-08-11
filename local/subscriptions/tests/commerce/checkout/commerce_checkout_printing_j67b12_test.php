<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** J6.7B12 Checkout and printable cart contract. */
final class commerce_checkout_printing_j67b12_test
        extends \advanced_testcase {

    public function test_checkout_only_shows_final_and_compare_price_per_item(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/checkout/page.mustache'
        );

        $this->assertIsString($template);
        $this->assertStringContainsString(
            '{{cartpricefinalformatted}}',
            $template
        );
        $this->assertStringContainsString(
            '{{cartpricecompareformatted}}',
            $template
        );
        $this->assertStringNotContainsString(
            'local_subscriptions/pricing/commercial_breakdown',
            $template
        );
    }

    public function test_checkout_summary_separates_trial_and_upgrade_credit(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/checkout/page.mustache'
        );

        $this->assertStringContainsString(
            'trialdiscounttotalformatted',
            $template
        );
        $this->assertStringContainsString(
            'upgradecredittotalformatted',
            $template
        );
        $this->assertStringContainsString(
            'commerce-checkout__totals--separated',
            $template
        );
    }

    public function test_printable_detailed_cart_is_linked_from_cart_and_checkout(): void {
        global $CFG;

        $cart = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/cart.php'
        );
        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );

        $this->assertStringContainsString('cart_print.php', $cart);
        $this->assertStringContainsString('cart_print.php', $checkout);
        $this->assertFileExists(
            $CFG->dirroot . '/local/subscriptions/cart_print.php'
        );
    }

    public function test_print_css_hides_interactive_checkout_elements(): void {
        global $CFG;

        $styles = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/styles/storefront.css'
        );

        $this->assertStringContainsString('@media print', $styles);
        $this->assertStringContainsString(
            '.commerce-checkout__payment',
            $styles
        );
        $this->assertStringContainsString(
            '.commerce-cart-print__actions',
            $styles
        );
    }
}
