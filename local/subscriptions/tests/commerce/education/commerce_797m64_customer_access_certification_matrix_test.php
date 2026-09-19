<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\customer\access\CommerceCustomerAccessPromisePresenter;
use local_subscriptions\commerce\customer\access\CommerceCustomerAccessPromiseService;

/** Final M6 customer-information matrix for non-course and shared Commerce surfaces. */
final class commerce_797m64_customer_access_certification_matrix_test extends advanced_testcase {
    public function test_digital_and_bundle_contracts_remain_distinct_and_truthful(): void {
        global $DB;
        $this->resetAfterTest(true);

        $service = CommerceCustomerAccessPromiseService::create($DB);
        $presenter = new CommerceCustomerAccessPromisePresenter();

        $digital = $service->resolve('M64-DIGITAL', 'digital', false, null);
        $owneddigital = $service->resolve('M64-DIGITAL', 'digital', true, null);
        $bundle = $service->resolve('M64-BUNDLE', 'bundle', false, null);

        self::assertSame('immediate_digital', $digital['kind']);
        self::assertSame('owned_digital', $owneddigital['kind']);
        self::assertSame('bundle', $bundle['kind']);
        self::assertFalse($bundle['progressive']);
        self::assertFalse($bundle['haspromotion']);

        $digitalview = $presenter->present($digital);
        $bundleview = $presenter->present($bundle);

        self::assertSame(
            get_string('commerce_m61_access_lead_immediate_digital', 'local_subscriptions'),
            $digitalview['accesspromiselead']
        );
        self::assertSame(
            get_string('commerce_m61_access_lead_bundle', 'local_subscriptions'),
            $bundleview['accesspromiselead']
        );
        self::assertNotSame(
            $digitalview['accesspromiselead'],
            $bundleview['accesspromiselead']
        );
    }

    public function test_customer_surfaces_share_the_access_contract_without_checkout_duplication(): void {
        global $CFG;

        $storefront = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/storefront/product_commerce_panel.mustache'
        );
        $cart = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/cart/page.mustache'
        );
        $print = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/cart/print.mustache'
        );
        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );

        self::assertIsString($storefront);
        self::assertIsString($cart);
        self::assertIsString($print);
        self::assertIsString($checkout);

        self::assertStringContainsString(
            'local_subscriptions/customer/access_promise',
            $storefront
        );
        self::assertStringContainsString(
            'local_subscriptions/customer/access_promise_compact',
            $cart
        );
        self::assertStringContainsString(
            'local_subscriptions/customer/access_promise_compact',
            $print
        );
        self::assertStringNotContainsString(
            'local_subscriptions/customer/access_promise',
            $checkout
        );
        self::assertStringContainsString(
            '{{accesspromisepromotionlabel}}',
            $checkout
        );
    }

    public function test_bundle_copy_does_not_claim_blanket_immediate_access(): void {
        $lead = get_string('commerce_m61_access_lead_bundle', 'local_subscriptions');
        $detail = get_string('commerce_m62_access_bundle', 'local_subscriptions');

        self::assertNotSame('', trim($lead));
        self::assertNotSame('', trim($detail));
        self::assertStringNotContainsString(
            get_string('commerce_m61_access_lead_immediate_digital', 'local_subscriptions'),
            $lead
        );
    }
}
