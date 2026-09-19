<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h91_wallet_diagnostic_dependencies_test extends advanced_testcase {
    public function test_wallet_diagnostic_dependencies_are_defined_before_use(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/commerce_checkout.php'
        );

        $policy = strpos(
            $checkout,
            '$presentationpolicy ='
        );
        $provider = strpos(
            $checkout,
            '$stripeprovider ='
        );
        $diagnostics = strpos(
            $checkout,
            '$walletserverdiagnostics = ['
        );

        $this->assertNotFalse($policy);
        $this->assertNotFalse($provider);
        $this->assertNotFalse($diagnostics);

        $this->assertLessThan(
            $diagnostics,
            $policy
        );
        $this->assertLessThan(
            $diagnostics,
            $provider
        );
    }

    public function test_h9_express_contract_remains_present(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/commerce_checkout.php'
        );

        foreach ([
            '$expresspaymentmethods',
            '$expressroutes',
            '$route->is_express()',
        ] as $expected) {
            $this->assertStringContainsString(
                $expected,
                $checkout
            );
        }
    }
}
