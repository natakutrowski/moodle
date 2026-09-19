<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h12612_sbp_route_candidate_loading_test extends \advanced_testcase {

    public function test_sbp_candidate_is_resolved_from_all_payment_routes(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        self::assertIsString($checkout);

        self::assertMatchesRegularExpression('/\\$hassbpcandidate\\s*=\\s*count\\(\\s*array_filter\\(\\s*\\$paymentroutes,/s', $checkout);
    }


    public function test_sbp_candidate_loads_driver_and_renders_surface(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        self::assertIsString($checkout);
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        self::assertIsString($template);

        self::assertStringContainsString('if ($hassbpcandidate) {', $checkout);
        self::assertStringContainsString("'local_subscriptions/checkout_alfa_sbp'", $checkout);
        self::assertStringContainsString("'hassbpcandidate' => \$hassbpcandidate", $checkout);
        self::assertStringContainsString('{{#hassbpcandidate}}', $template);
        self::assertStringContainsString('data-checkout-alfa-sbp', $template);
    }


    public function test_sbp_driver_owns_the_quick_action_directly(): void {
        global $CFG;

        $amd = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_alfa_sbp.js'
        );
        self::assertIsString($amd);

        self::assertStringContainsString('[data-quick-payment-action="sbp"]', $amd);
        self::assertStringContainsString('event.stopImmediatePropagation();', $amd);
    }

}
