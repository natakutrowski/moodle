<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\checkout;

defined('MOODLE_INTERNAL') || die();

/** H3.1 regression check for inspectable checkout preparation failures. */
final class commerce_795h31_checkout_prepare_diagnostics_test extends \advanced_testcase {
    public function test_prepare_failure_is_logged_and_redirected_with_reference(): void {
        global $CFG;

        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/commerce_checkout.php');

        $this->assertIsString($source);
        $this->assertStringContainsString('[local_subscriptions][checkout_prepare]', $source);
        $this->assertStringContainsString(
            <<<'PHP'
'checkouterror' => $reference
PHP,
            $source
        );
        $this->assertStringContainsString('commerce_checkout_prepare_error_reference', $source);
        $this->assertStringContainsString('redirect(', $source);
    }
}
