<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutService;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutSessionRepository;

final class commerce_796h129a52_guest_payment_attempt_continuity_test extends \advanced_testcase {
    public function test_attach_payment_keeps_one_resumable_purchase_across_method_changes(): void {
        global $DB;
        $this->resetAfterTest(true);

        $service = CommerceGuestCheckoutService::create();
        $session = $service->start('EUR');
        $session = $service->identify(
            $session,
            'h129a52@example.test',
            'Guest',
            'Checkout'
        );

        $repository = new CommerceGuestCheckoutSessionRepository($DB);

        $session = $repository->attach_payment(
            $session,
            'cmp_same_purchase',
            'paypal_attempt'
        );
        self::assertSame(
            'cmp_same_purchase',
            $session->get_metadata()['resume_purchase_reference'] ?? null
        );

        $session = $repository->attach_payment(
            $session,
            'cmp_same_purchase',
            'stripe_attempt'
        );
        self::assertSame(
            'cmp_same_purchase',
            $session->get_metadata()['resume_purchase_reference'] ?? null
        );
        self::assertSame(
            'cmp_same_purchase',
            $session->get_purchase_reference()
        );
        self::assertSame(
            'stripe_attempt',
            $session->get_payment_reference()
        );
    }

    public function test_attach_payment_advances_resume_reference_if_cart_forced_new_purchase(): void {
        global $DB;
        $this->resetAfterTest(true);

        $service = CommerceGuestCheckoutService::create();
        $session = $service->start('EUR');
        $session = $service->identify(
            $session,
            'h129a52-cart@example.test',
            'Guest',
            'Checkout'
        );

        $repository = new CommerceGuestCheckoutSessionRepository($DB);
        $session = $repository->attach_payment($session, 'cmp_old', 'pay_old');
        $session = $repository->attach_payment($session, 'cmp_new', 'pay_new');

        self::assertSame(
            'cmp_new',
            $session->get_metadata()['resume_purchase_reference'] ?? null
        );
    }

    public function test_guest_result_and_activation_share_the_same_session_purchase_ownership_contract(): void {
        global $CFG;

        foreach ([
            'order_result.php',
            'guest_account_activation_start.php',
        ] as $relative) {
            $source = file_get_contents(
                $CFG->dirroot . '/local/subscriptions/' . $relative
            );
            self::assertIsString($source);

            self::assertStringContainsString(
                'find_by_token(',
                $source,
                $relative
            );
            self::assertStringContainsString(
                'get_purchase_reference()',
                $source,
                $relative
            );
            self::assertStringContainsString(
                "['resume_purchase_reference']",
                $source,
                $relative
            );
            self::assertStringContainsString(
                'find_for_guest_session(',
                $source,
                $relative
            );
        }
    }
}
