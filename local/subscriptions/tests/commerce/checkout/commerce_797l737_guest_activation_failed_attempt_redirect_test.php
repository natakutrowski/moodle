<?php

declare(strict_types=1);

namespace local_subscriptions;

/**
 * Regression contract for 7.97 L7.3.7.
 *
 * A guest activation link can legitimately retain the reference of an older failed payment
 * attempt. Completing the account must not redirect to that order result, otherwise the user sees
 * "payment failed" immediately after a successful account finalisation. The payment record itself
 * remains untouched; only the post-activation destination changes.
 */
final class commerce_797l737_guest_activation_failed_attempt_redirect_test extends \advanced_testcase {
    public function test_successful_account_finalisation_redirects_to_my_campus_not_payment_result(): void {
        global $CFG;

        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/guest_account_activate.php');
        self::assertIsString($source);

        self::assertStringContainsString(
            "get_string('commerce_guest_activation_ready_confirmation', 'local_subscriptions')",
            $source
        );
        self::assertStringContainsString('\\core\\notification::success(', $source);
        self::assertStringContainsString('redirect(UrlFactory::my_campus());', $source);

        self::assertStringNotContainsString("'accountfinalised' => 1", $source);
        self::assertStringNotContainsString('UrlFactory::order_result([', $source);
    }

    public function test_activation_reference_is_still_kept_for_activation_validation_contract(): void {
        global $CFG;

        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/guest_account_activate.php');
        self::assertIsString($source);

        self::assertStringContainsString("\$reference = optional_param('reference', '', PARAM_ALPHANUMEXT);", $source);
        self::assertStringContainsString("'reference' => \$reference", $source);
        self::assertStringContainsString("'reference' => \$reference,", $source);
    }
}
