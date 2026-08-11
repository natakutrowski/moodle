<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** J6.7B4 exact owned credit and shared presentation contract. */
final class commerce_commercial_pricing_j67b4_test
        extends \advanced_testcase {

    public function test_credit_resolver_prefers_actual_fulfilled_purchase(): void {
        global $DB;

        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user();
        $now = time();

        $productid = (int)$DB->insert_record(
            'local_subs_commerce_product',
            (object)[
                'sku' => 'COURSE.SOURCE.TEST',
                'type' => 'course_access',
                'status' => 'active',
                'name' => 'Source course',
                'description' => '',
                'metadatajson' => '{}',
                'availablefrom' => null,
                'availableuntil' => null,
                'timecreated' => $now,
                'timemodified' => $now,
            ]
        );

        $DB->insert_record(
            'local_subs_commerce_prod_map',
            (object)[
                'productid' => $productid,
                'legacyfamily' => 'subscription',
                'legacytable' => 'subscription_plan',
                'legacyid' => 31,
                'timecreated' => $now,
                'timemodified' => $now,
            ]
        );

        $purchaseid = (int)$DB->insert_record(
            'local_subscriptions_commerce_purchase',
            (object)[
                'purchaseuuid' => str_repeat('a', 32),
                'reference' => 'cmp_b4_credit_test',
                'type' => 'subscription',
                'legacyfamily' => null,
                'legacyid' => null,
                'userid' => $user->id,
                'customeremail' => $user->email,
                'status' => 'fulfilled',
                'currency' => 'EUR',
                'subtotalminor' => 8000,
                'discountminor' => 0,
                'totalminor' => 8000,
                'customerjson' => '{}',
                'snapshotjson' => '{}',
                'metadatajson' => '{}',
                'snapshotversion' => 1,
                'timecreated' => $now,
                'timemodified' => $now,
            ]
        );

        $DB->insert_record(
            'local_subscriptions_commerce_purchase_item',
            (object)[
                'purchaseid' => $purchaseid,
                'position' => 0,
                'itemtype' => 'subscription',
                'itemreference' => 'COURSE.SOURCE.TEST',
                'label' => 'Source course',
                'quantity' => 1,
                'currency' => 'EUR',
                'unitminor' => 8000,
                'grossminor' => 8000,
                'discountminor' => 0,
                'netminor' => 8000,
                'pricingjson' => '{}',
                'fulfillmentjson' => '{}',
                'metadatajson' => json_encode([
                    'pricing_final_unit_minor' => 8000,
                ]),
            ]
        );

        $resolver = new \local_subscriptions\commerce\pricing\CommerceOwnedProductCreditResolver($DB);

        $this->assertSame(
            8000,
            $resolver->resolve(
                (int)$user->id,
                31,
                'EUR',
                20,
                9120
            )
        );
    }

    public function test_all_surfaces_use_one_shared_multiline_partial(): void {
        global $CFG;
        $storefront = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/storefront/product_price.mustache');
        $cart = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/cart/price.mustache');
        $checkout = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache');
        $this->assertStringContainsString('commerce-storefront-price', $storefront);
        $this->assertStringContainsString('commerce-cart-price', $cart);
        $this->assertStringContainsString('cartpricefinalformatted', $checkout);
        $this->assertStringContainsString('cartpricecompareformatted', $checkout);
    }

    public function test_featured_product_keeps_full_width(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/storefront/'
            . 'product_card.mustache'
        );

        $this->assertStringContainsString(
            '{{#featured}}col-12{{/featured}}',
            $source
        );
        $this->assertStringContainsString(
            '{{^featured}}col-12 col-lg-6{{/featured}}',
            $source
        );
    }

    public function test_cart_and_checkout_remove_redundant_brand_label(): void {
        global $CFG;

        foreach (['cart/page.mustache', 'checkout/page.mustache'] as $relative) {
            $source = file_get_contents(
                $CFG->dirroot
                . '/local/subscriptions/templates/'
                . $relative
            );

            $this->assertStringNotContainsString(
                'CampusFR Commerce',
                $source
            );
        }
    }
}
