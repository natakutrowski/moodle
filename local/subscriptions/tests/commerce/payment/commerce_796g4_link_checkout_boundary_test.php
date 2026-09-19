<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796g4_link_checkout_boundary_test extends advanced_testcase {
    public function test_current_checkout_separates_express_routes_from_standard_methods(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/commerce_checkout.php'
        );
        self::assertIsString($checkout);

        self::assertStringContainsString(
            'static fn(CommerceCheckoutPaymentRoute $route): bool =>',
            $checkout
        );
        self::assertStringContainsString(
            '$route->is_express()',
            $checkout
        );
        self::assertStringContainsString(
            '$expresspaymentmethods',
            $checkout
        );
        self::assertStringContainsString(
            '!in_array(',
            $checkout
        );
        self::assertStringContainsString(
            '$expresswalletmethods =',
            $checkout
        );
    }

    public function test_architecture_matrix_can_show_link_provider_capability(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/configuration/'
            . 'payment_architecture.php'
        );

        $this->assertStringContainsString(
            'CommercePaymentMethodCatalogue::keys()',
            $contents
        );
    }
}
