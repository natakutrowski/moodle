<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\customer\access\CommerceCustomerAccessPromiseService;

final class commerce_797m62_cart_checkout_access_promise_test extends advanced_testcase {
    public function test_promotion_join_cart_contract_is_pinned_to_the_joined_promotion(): void {
        global $DB;
        $this->resetAfterTest(true);

        $now = time();
        $course = $this->getDataGenerator()->create_course();
        $promotionid = (int)$DB->insert_record('local_subs_commerce_ped_promo', (object)[
            'promotionkey' => 'm62-owner-join',
            'name' => 'M6.2 Owner Join',
            'courseid' => (int)$course->id,
            'status' => 'scheduled',
            'published' => 1,
            'salesopensat' => $now - HOURSECS,
            'salesclosesat' => $now + HOURSECS,
            'startsat' => $now + DAYSECS,
            'endsat' => null,
            'capacitytotal' => 6,
            'createdby' => null,
            'modifiedby' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $promise = CommerceCustomerAccessPromiseService::create($DB)->resolve_cart_item(
            'M62-OWNER',
            'course_access',
            'promotion_join',
            ['promotion_join_promotion_id' => $promotionid],
            true,
            $now
        );

        self::assertSame('owner_promotion_join', $promise['kind']);
        self::assertSame($promotionid, $promise['promotionid']);
        self::assertSame('M6.2 Owner Join', $promise['promotionname']);
        self::assertTrue($promise['preservesexistingaccess']);
    }

    public function test_cart_is_concise_and_checkout_avoids_repeating_the_access_block(): void {
        global $CFG;

        $cart = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/cart/page.mustache'
        );
        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $compact = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/customer/access_promise_compact.mustache'
        );

        self::assertIsString($cart);
        self::assertIsString($checkout);
        self::assertIsString($compact);
        self::assertStringContainsString(
            'local_subscriptions/customer/access_promise_compact',
            $cart
        );
        self::assertStringNotContainsString(
            'local_subscriptions/customer/access_promise',
            $checkout
        );
        self::assertStringContainsString(
            '{{accesspromisepromotionlabel}}',
            $checkout
        );
        self::assertStringNotContainsString('accesspromisedetails', $compact);
    }

    public function test_checkout_does_not_claim_blanket_immediate_access(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString(
            "'commerce_m62_checkout_access_truth'",
            $source
        );
    }

    public function test_owner_price_on_product_page_uses_standard_price_component(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/storefront/product_commerce_panel.mustache'
        );

        self::assertIsString($template);
        self::assertStringContainsString(
            'commerce-storefront-price--promotion-join',
            $template
        );
        self::assertStringContainsString(
            '{{promotionjoinownerpricelabel}}',
            $template
        );
        self::assertStringContainsString(
            '{{promotionjoinownerpricehelp}}',
            $template
        );
    }
    public function test_owned_promotion_join_card_uses_a_normal_price_box_and_price_free_cta(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/storefront/product_card.mustache'
        );

        self::assertIsString($template);
        self::assertStringContainsString(
            'commerce-storefront-price--promotion-join',
            $template
        );
        self::assertStringContainsString(
            '{{promotionjoinpriceformatted}}',
            $template
        );
        self::assertStringContainsString(
            '{{promotionjoinactionlabel}}</button>',
            $template
        );
        self::assertStringNotContainsString(
            '{{promotionjoinactionpricelabel}}</button>',
            $template
        );
    }

    public function test_cart_print_preserves_customer_access_and_promotion_information(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/cart/print.mustache'
        );

        self::assertIsString($template);
        self::assertStringContainsString(
            '{{> local_subscriptions/customer/access_promise_compact }}',
            $template
        );
    }

}
