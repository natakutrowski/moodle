<?php

declare(strict_types=1);

namespace local_subscriptions;

use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutSession;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h129a56_active_guest_session_expiry_semantics_test extends \advanced_testcase {
    private function session_with_expiry(int $expiresat): CommerceGuestCheckoutSession {
        return new CommerceGuestCheckoutSession((object)[
            'id' => 1,
            'reference' => 'gcs_h129a56',
            'token' => 'token_h129a56',
            'status' => 'active',
            'currency' => 'EUR',
            'userid' => 42,
            'email' => 'guest@example.test',
            'firstname' => 'Guest',
            'lastname' => 'Customer',
            'purchasereference' => 'cmp_h129a56',
            'paymentreference' => 'pay_h129a56',
            'expiresat' => $expiresat,
            'metadatajson' => '{}',
            'timecreated' => 1,
            'timemodified' => 1,
        ]);
    }

    public function test_zero_expiry_means_non_expiring_active_session(): void {
        $session = $this->session_with_expiry(0);

        self::assertFalse(
            $session->is_expired(2000000000)
        );
    }

    public function test_positive_expiry_in_past_is_expired(): void {
        $session = $this->session_with_expiry(100);

        self::assertTrue(
            $session->is_expired(101)
        );
    }

    public function test_positive_expiry_in_future_is_not_expired(): void {
        $session = $this->session_with_expiry(200);

        self::assertFalse(
            $session->is_expired(199)
        );
    }

    public function test_guest_account_activator_uses_zero_as_non_expiring_sentinel(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/checkout/guest/'
            . 'CommerceGuestAccountActivator.php'
        );

        self::assertStringContainsString(
            "'expiresat' => 0",
            $source
        );
    }

    public function test_order_result_can_keep_is_expired_guard(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/order_result.php'
        );

        self::assertStringContainsString(
            '$guestsession->is_expired()',
            $source
        );
    }
}
