<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h61_wallet_diagnostic_dependency_test extends advanced_testcase {
    public function test_retained_wallet_diagnostics_have_explicit_dependencies(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/commerce_checkout.php'
        );

        $policypos = strpos(
            $checkout,
            '$presentationpolicy ='
        );
        $providerpos = strpos(
            $checkout,
            '$stripeprovider ='
        );
        $diagnosticpos = strpos(
            $checkout,
            '$walletserverdiagnostics = ['
        );

        $this->assertNotFalse($policypos);
        $this->assertNotFalse($providerpos);
        $this->assertNotFalse($diagnosticpos);

        $this->assertLessThan(
            $diagnosticpos,
            $policypos
        );
        $this->assertLessThan(
            $diagnosticpos,
            $providerpos
        );
    }

    public function test_h6_still_uses_dynamic_inline_route_contract(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/commerce_checkout.php'
        );

        foreach ([
            'CommerceCheckoutPaymentOrchestrator',
            '$route->is_inline()',
            "'inlineMethods' => \$inlinepaymentmethods",
        ] as $expected) {
            $this->assertStringContainsString(
                $expected,
                $checkout
            );
        }
    }
}
