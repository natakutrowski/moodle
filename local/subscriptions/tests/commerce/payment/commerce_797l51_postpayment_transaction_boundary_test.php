<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

/** Guards the L5.1 payment -> fulfillment transaction boundary regression. */
final class commerce_797l51_postpayment_transaction_boundary_test extends advanced_testcase {
    public function test_reconciliation_does_not_wrap_event_router_in_outer_transaction(): void {
        global $CFG;

        foreach ([
            'stripe/StripePaymentReconciliationService.php',
            'alfa/AlfaPaymentReconciliationService.php',
        ] as $relative) {
            $source = file_get_contents(
                $CFG->dirroot
                . '/local/subscriptions/classes/commerce/payment/reconciliation/'
                . $relative
            );

            self::assertIsString($source);
            self::assertStringContainsString('->finalize(', $source);
            self::assertStringNotContainsString('start_delegated_transaction()', $source);
        }
    }

    public function test_invoice_issuer_explicitly_rolls_back_its_own_transaction_on_failure(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/order/invoice/CommerceInvoiceIssuer.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString('start_delegated_transaction()', $source);
        self::assertStringContainsString('catch (\\Throwable $exception)', $source);
        self::assertStringContainsString('$transaction->rollback($exception);', $source);
    }
}
