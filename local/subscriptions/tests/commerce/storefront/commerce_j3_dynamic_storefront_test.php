<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** Static contract tests for the J3 Storefront and cart UX integration. */
final class commerce_j3_dynamic_storefront_test extends \advanced_testcase {
    public function test_storefront_supports_toggle_buy_now_and_added_modal(): void {
        global $CFG;
        $card = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/storefront/product_card.mustache');
        $catalog = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/storefront/catalog.mustache');
        $this->assertStringContainsString('name="action" value="{{toggleaction}}"', $card);
        $this->assertStringContainsString('name="action" value="buynow"', $card);
        $modal = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/storefront/'
            . 'cart_added_modal.mustache'
        );
        $this->assertStringContainsString(
            'local_subscriptions/storefront/cart_added_modal',
            $catalog
        );
        $this->assertStringContainsString(
            'commerce-cart-feedback',
            $modal
        );
    }

    public function test_cart_clear_uses_a_styled_modal_and_not_browser_confirm(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/cart/page.mustache'
        );

        $this->assertStringContainsString('commerce-clear-cart-modal', $template);
        $this->assertStringContainsString('data-bs-toggle="modal"', $template);
        $this->assertStringContainsString('id="commerce-clear-cart-modal"', $template);
        $this->assertStringContainsString('name="action" value="clear"', $template);
        $this->assertStringNotContainsString('onsubmit="return confirm(', $template);
    }

    public function test_bundle_ownership_is_checked_before_cart_addition(): void {
        global $CFG;

        $service = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/cart/service/CommerceCartService.php'
        );
        $runtime = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/cart/service/CommerceCartRuntimeFactory.php'
        );

        $this->assertStringContainsString('bundle_all_owned', $service);
        $this->assertStringContainsString('bundle_partial_owned', $service);
        $this->assertStringContainsString('CommerceBundlePurchaseEligibilityService', $runtime);
    }
}
