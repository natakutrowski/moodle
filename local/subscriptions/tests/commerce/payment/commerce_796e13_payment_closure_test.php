<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796e13_payment_closure_test extends advanced_testcase {
    public function test_both_current_providers_have_crm_reconciliation_entrypoints(): void {
        global $CFG;

        $index = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/purchases/index.php'
        );
        $view = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/purchases/view.php'
        );

        foreach ([
            'reconcile_alfa.php',
            'reconcile_stripe.php',
        ] as $route) {
            $this->assertStringContainsString($route, $index);
            $this->assertStringContainsString($route, $view);
        }
    }

    public function test_closure_cli_reuses_generic_architecture_certification(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/cli/commerce/audit/'
            . 'audit_796e13_payment_closure.php'
        );

        $this->assertStringContainsString(
            'CommercePaymentArchitectureCertificationService',
            $contents
        );
        $this->assertStringContainsString(
            'CommercePaymentArchitectureInspector',
            $contents
        );
        $this->assertStringContainsString(
            "'refundcertified'",
            $contents
        );
        $this->assertStringContainsString(
            "'refundhistorycontract'",
            $contents
        );
        $this->assertStringContainsString(
            'Commerce 7.96E closure: PASS',
            $contents
        );
    }

    public function test_closure_does_not_add_schema_upgrade(): void {
        global $CFG;

        $version = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/version.php'
        );

        $this->assertNotEmpty($version);
        $this->assertFileDoesNotExist(
            $CFG->dirroot
            . '/local/subscriptions/db/upgrade_796e13.php'
        );
    }
}
