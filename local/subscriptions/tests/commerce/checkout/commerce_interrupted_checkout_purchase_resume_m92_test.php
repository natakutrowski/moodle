<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_interrupted_checkout_purchase_resume_m92_test extends advanced_testcase {
    public function test_runtime_has_safe_resume_fallback(): void {
        global $CFG;

        $runtime = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/checkout/unified/CommerceCheckoutRuntime.php'
        );
        $persister = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/checkout/unified/CommerceCheckoutPurchasePersister.php'
        );

        self::assertStringContainsString('resume_purchase_reference', $runtime);
        self::assertStringContainsString('CommerceInterruptedCheckoutResumeMismatchException', $runtime);
        self::assertStringContainsString('assert_resume_matches', $persister);
        self::assertStringContainsString('PAYMENT_PENDING', $persister);
    }

    public function test_checkout_passes_recovered_purchase_reference_to_runtime(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout_action.php'
        );

        self::assertStringContainsString('resume_purchase_reference', $source);
    }
}
