<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\payment_h4;

defined('MOODLE_INTERNAL') || die();

/**
 * Compatibility guard for the former payment/ location.
 *
 * The canonical H4 contract now lives in tests/commerce/checkout/.
 */
final class commerce_796h4_link_klarna_embedded_test extends \advanced_testcase {
    public function test_canonical_test_lives_in_checkout_suite(): void {
        global $CFG;

        self::assertFileExists(
            $CFG->dirroot
            . '/local/subscriptions/tests/commerce/checkout/'
            . 'commerce_796h4_link_klarna_embedded_test.php'
        );
    }
}
