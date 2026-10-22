<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\donation;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\domain\CommerceCalculatedCartItem;
use local_subscriptions\commerce\cart\domain\CommerceCart;
use local_subscriptions\commerce\cart\domain\CommerceCartItem;
use local_subscriptions\commerce\cart\domain\CommerceCartSnapshot;
use local_subscriptions\commerce\cart\domain\CommerceCartTotals;
use local_subscriptions\commerce\donation\CommerceDonationAmountPolicy;
use local_subscriptions\commerce\donation\CommerceDonationIntentSession;
use local_subscriptions\commerce\donation\CommerceDonationStorefrontPresenter;
use local_subscriptions\commerce\domain\value\CommerceMoney;
use local_subscriptions\commerce\purchase\presentation\CommercePurchasePresentation;

/** 7.98B.4.3 — Donation amount intent across Boutique / Storefront. */
final class commerce_798b43_donation_storefront_intent_test extends \advanced_testcase {
    private const SKU = 'DONATION.B43.INTENT';

    public function test_default_and_presets_are_shared_by_donation_surfaces(): void {
        $this->resetAfterTest(true);
        $policy = new CommerceDonationAmountPolicy();

        self::assertSame(10, $policy->default_units());
        self::assertSame([5, 10, 25, 50], $policy->preset_units());

        set_config('commerce_donation_max_units', 8, 'local_subscriptions');
        $limited = new CommerceDonationAmountPolicy();
        self::assertSame(8, $limited->default_units());
        self::assertSame([5], $limited->preset_units());
    }

    public function test_session_intent_is_remembered_by_sku(): void {
        global $SESSION;
        $this->resetAfterTest(true);
        $SESSION = new \stdClass();

        $intent = new CommerceDonationIntentSession();
        self::assertSame(10, $intent->resolve(self::SKU));
        self::assertSame(25, $intent->remember(self::SKU, 25));
        self::assertSame(25, $intent->recall(self::SKU));
        self::assertSame(25, $intent->resolve(self::SKU));
    }

    public function test_frozen_cart_line_wins_over_session_intent_on_return(): void {
        global $SESSION;
        $this->resetAfterTest(true);
        $SESSION = new \stdClass();

        $intent = new CommerceDonationIntentSession();
        $intent->remember(self::SKU, 10);

        $money = CommerceMoney::from_minor(2500, 'EUR');
        $item = new CommerceCartItem(
            self::SKU,
            91,
            1,
            [CommerceDonationAmountPolicy::METADATA_UNITS => 25]
        );
        $calculated = new CommerceCalculatedCartItem(
            $item,
            'Donation',
            $money,
            $money,
            1,
            1,
            1,
            'donation'
        );
        $cart = new CommerceCart(str_repeat('a', 32), 42, 'EUR', [$item], [], 1, 1);
        $zero = CommerceMoney::from_minor(0, 'EUR');
        $snapshot = new CommerceCartSnapshot(
            $cart,
            [$calculated],
            new CommerceCartTotals($money, $zero, $zero, $money),
            time()
        );

        self::assertSame(25, $intent->resolve(self::SKU, $snapshot));
    }

    public function test_storefront_presenter_exposes_selected_customer_amount_not_catalogue_unit(): void {
        global $SESSION;
        $this->resetAfterTest(true);
        $SESSION = new \stdClass();
        (new CommerceDonationIntentSession())->remember(self::SKU, 25);

        $data = (new CommerceDonationStorefrontPresenter())->decorate([
            'sku' => self::SKU,
            'producttype' => 'donation',
            'prices' => [[
                'id' => 91,
                'currency' => 'EUR',
                'amountminor' => 100,
                'formatted' => '1,00 EUR',
            ]],
        ], 'EUR');

        self::assertTrue($data['isdonation']);
        self::assertSame(25, $data['donationdefaultunits']);
        self::assertSame(CommercePurchasePresentation::money(2500, 'EUR'), $data['donationamountformatted']);
        self::assertSame($data['donationamountformatted'], $data['prices'][0]['formatted']);
        self::assertSame('25.00', $data['donationamountinputvalue']);
    }

    public function test_boutique_storefront_have_editable_selector_but_cart_checkout_do_not(): void {
        global $CFG;
        $this->resetAfterTest(true);
        $root = $CFG->dirroot . '/local/subscriptions/';

        $card = (string)file_get_contents($root . 'templates/storefront/product_card.mustache');
        $panel = (string)file_get_contents($root . 'templates/storefront/product_commerce_panel.mustache');
        $selector = (string)file_get_contents($root . 'templates/storefront/donation_amount_selector.mustache');
        $cart = (string)file_get_contents($root . 'cart.php');
        $checkout = (string)file_get_contents($root . 'checkout.php');
        $js = (string)file_get_contents($root . 'amd/src/storefront_donation.js');

        self::assertStringContainsString('storefront/donation_amount_selector', $card);
        self::assertStringContainsString('storefront/donation_amount_selector', $panel);
        self::assertStringContainsString('data-storefront-donation', $selector);
        self::assertStringContainsString('data-storefront-donation-units', $card);
        self::assertStringContainsString('data-storefront-donation-units', $panel);
        self::assertStringContainsString('data-donation-intent-endpoint', $selector);
        self::assertStringContainsString('keepalive: true', $js);
        self::assertStringNotContainsString('data-storefront-donation', $cart);
        self::assertStringNotContainsString('data-storefront-donation', $checkout);
    }

    public function test_cart_action_persists_donation_intent_after_successful_surface_action(): void {
        global $CFG;
        $this->resetAfterTest(true);
        $source = (string)file_get_contents($CFG->dirroot . '/local/subscriptions/cart_action.php');

        self::assertStringContainsString('CommerceDonationIntentSession', $source);
        self::assertStringContainsString('$hasdonationcompanion ? $donationsku : $sku', $source);
        self::assertStringContainsString('->remember(', $source);
    }
}
