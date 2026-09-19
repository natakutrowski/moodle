<?php

declare(strict_types=1);

namespace local_subscriptions;

use local_subscriptions\commerce\order\presentation\CommerceOrderPresentationService;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h129a55_guest_session_purchase_binding_test extends \advanced_testcase {
    public function test_guest_lookup_requires_exact_session_purchase_binding(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/order/presentation/'
            . 'CommerceOrderPresentationService.php'
        );

        self::assertStringContainsString(
            'public function find_for_guest_session(',
            $source
        );
        self::assertStringContainsString(
            '!hash_equals($reference, $sessionpurchasereference)',
            $source
        );
        self::assertStringContainsString(
            '!hash_equals($reference, $resumepurchasereference)',
            $source
        );
        self::assertStringContainsString(
            '$this->purchases->find_by_reference($reference)',
            $source
        );
    }

    public function test_guest_result_activation_and_pollers_use_session_bound_lookup(): void {
        global $CFG;

        foreach ([
            'order_result.php',
            'guest_account_activation_start.php',
            'payment/fulfillment_status.php',
            'payment/stripe_return_poll.php',
            'payment/alfa_return_poll.php',
        ] as $relative) {
            $source = file_get_contents(
                $CFG->dirroot . '/local/subscriptions/' . $relative
            );

            self::assertStringContainsString(
                'find_for_guest_session(',
                $source,
                $relative
            );
            self::assertStringContainsString(
                'resume_purchase_reference',
                $source,
                $relative
            );
        }
    }

    public function test_generic_user_lookup_is_not_used_for_guest_order_result(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/order_result.php'
        );

        $guestbranch = substr(
            $source,
            strpos($source, '} else {'),
            strpos($source, '} catch', strpos($source, '} else {'))
                - strpos($source, '} else {')
        );

        self::assertStringContainsString(
            'find_for_guest_session(',
            $guestbranch
        );
        self::assertStringNotContainsString(
            'find_for_user(',
            $guestbranch
        );
    }
}
