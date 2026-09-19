<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\payment_h9;

defined('MOODLE_INTERNAL') || die();

/**
 * Compatibility guard for the former payment/ location.
 *
 * The canonical H9 contract now lives in tests/commerce/checkout/.
 */
final class commerce_796h9_stripe_express_intent_method_test extends \advanced_testcase {
    public function test_canonical_test_lives_in_checkout_suite(): void {
        global $CFG;

        self::assertFileExists(
            $CFG->dirroot
            . '/local/subscriptions/tests/commerce/checkout/'
            . 'commerce_796h9_stripe_express_intent_method_test.php'
        );
    }
}
