<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\cron\compat;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

/**
 * Compatibility guard for a test moved to tests/commerce/payment.
 */
final class commerce_796g4_link_checkout_boundary_test extends advanced_testcase {
    public function test_canonical_test_lives_in_payment_directory(): void {
        global $CFG;

        $this->assertFileExists(
            $CFG->dirroot . '/local/subscriptions/tests/commerce/payment/commerce_796g4_link_checkout_boundary_test.php'
        );
    }
}
