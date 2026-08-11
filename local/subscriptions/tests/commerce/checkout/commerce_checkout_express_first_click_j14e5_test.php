<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/**
 * Structural regression coverage for first-click Express checkout eligibility.
 *
 * @coversNothing
 */
final class commerce_checkout_express_first_click_j14e5_test extends \advanced_testcase {
    public function test_direct_purchase_candidate_is_checked_before_cart_materialisation(): void {
        global $CFG;

        $javascript = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/provider_experience.js'
        );
        $endpoint = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/ajax/checkout_express_eligibility.php'
        );
        $service = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/checkout/express/CommerceCheckoutExpressService.php'
        );

        self::assertIsString($javascript);
        self::assertIsString($endpoint);
        self::assertIsString($service);
        self::assertStringContainsString("sku: hiddenValue(form, 'sku')", $javascript);
        self::assertStringContainsString("priceid: hiddenValue(form, 'priceid')", $javascript);
        self::assertStringContainsString('direct_purchase_ineligibility_reason', $endpoint);
        self::assertStringContainsString('direct_purchase_ineligibility_reason', $service);
    }

    public function test_provider_launch_requires_explicit_modal_confirmation(): void {
        global $CFG;

        $javascript = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/provider_experience.js'
        );
        $cartaction = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/cart_action.php'
        );

        self::assertIsString($javascript);
        self::assertIsString($cartaction);
        self::assertStringContainsString("appendHidden(form, 'providerconfirmed', '1')", $javascript);
        self::assertStringContainsString("optional_param('providerconfirmed', 0, PARAM_BOOL)", $cartaction);
        self::assertStringContainsString('$express && $providerconfirmed && $customerid > 0', $cartaction);
    }
}
