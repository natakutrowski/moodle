<?php

declare(strict_types=1);

namespace local_subscriptions;

final class commerce_guest_account_finalisation_j12d_test extends \advanced_testcase {
    public function test_activation_finalises_username_and_password_ui(): void {
        global $CFG;
        $service = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/checkout/guest/CommerceGuestAccountActivationService.php');
        $page = file_get_contents($CFG->dirroot . '/local/subscriptions/guest_account_activate.php');
        $amd = file_get_contents($CFG->dirroot . '/local/subscriptions/amd/src/guest_account_activation.js');
        self::assertStringContainsString("str_starts_with(\$previoususername, 'checkout_')", $service);
        self::assertStringContainsString('local_subscriptions_generate_unique_username(', $service);
        self::assertStringContainsString("'final_username'", $service);
        self::assertStringContainsString("'passwordpolicy' => \$passwordpolicy", $page);
        self::assertStringContainsString('Object.values(state).every(Boolean)', $amd);
        self::assertStringContainsString("submit.disabled = !valid", $amd);
    }
}
