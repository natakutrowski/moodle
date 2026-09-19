<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h129a5_guest_multi_attempt_order_ownership_test extends \advanced_testcase {

    public function test_order_result_uses_current_guest_token_and_multi_attempt_purchase_ownership(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/order_result.php'
        );
        self::assertIsString($source);

        self::assertStringContainsString('$guestsessions->find_by_token($token)', $source);
        self::assertStringNotContainsString('find_by_purchase_reference(', $source);
        self::assertStringContainsString('find_for_guest_session(', $source);
        self::assertStringContainsString('get_purchase_reference()', $source);
        self::assertStringContainsString("['resume_purchase_reference']", $source);
    }


    public function test_post_payment_pollers_use_same_guest_session_ownership_contract(): void {
        global $CFG;
        foreach ([
            'payment/fulfillment_status.php',
            'payment/stripe_return_poll.php',
            'payment/alfa_return_poll.php',
        ] as $relative) {
            $source = file_get_contents($CFG->dirroot . '/local/subscriptions/' . $relative);
            self::assertIsString($source);
            self::assertStringContainsString('find_by_token(', $source, $relative);
            self::assertStringContainsString('find_for_guest_session(', $source, $relative);
            self::assertStringNotContainsString('find_by_purchase_reference(', $source, $relative);
        }
    }


    public function test_guest_session_must_still_be_live_and_bound_to_a_user(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/order_result.php'
        );
        self::assertIsString($source);

        self::assertStringContainsString('$guestsession->is_expired()', $source);
        self::assertStringContainsString('$guestsession->get_user_id() === null', $source);
    }

}
