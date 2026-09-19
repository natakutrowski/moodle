<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h6_payment_orchestration_test extends \advanced_testcase {

    public function test_inline_and_express_execution_are_route_driven(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        self::assertIsString($checkout);

        self::assertStringContainsString('$inlineroutes = array_values(', $checkout);
        self::assertStringContainsString('$expressroutes = array_values(', $checkout);
        self::assertStringContainsString('static fn(CommerceCheckoutPaymentRoute $route): bool =>', $checkout);
        self::assertStringContainsString('$route->is_inline()', $checkout);
        self::assertStringContainsString('$route->is_express()', $checkout);
    }

}
