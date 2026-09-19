<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\domain\CommerceCart;
use local_subscriptions\commerce\cart\repository\CommerceSessionCartRepository;
use local_subscriptions\commerce\cart\service\CommerceCartSessionKeyResolver;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCartTransferService;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutService;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutSessionRepository;
use local_subscriptions\commerce\checkout\guest\CommerceGuestIdentityVerificationService;

final class commerce_797l738_guest_otp_direct_purchase_regression_test extends \advanced_testcase {
    public function test_empty_anonymous_cart_is_a_valid_direct_purchase_noop(): void {
        global $SESSION;

        $this->resetAfterTest(true);
        unset($SESSION->local_subscriptions_commerce_carts);

        $repository = new CommerceSessionCartRepository();
        $keys = new CommerceCartSessionKeyResolver();
        $repository->save(
            $keys->resolve(0, 'EUR'),
            new CommerceCart(
                bin2hex(random_bytes(16)),
                0,
                'EUR',
                []
            )
        );

        $transfer = new CommerceGuestCartTransferService(
            $repository,
            $keys,
            null
        );

        self::assertNull($transfer->capture('EUR'));
        self::assertNull($transfer->transfer(123, 'EUR'));
    }

    public function test_stale_duplicate_identify_reuses_same_checkout_provisional_user(): void {
        global $DB;

        $this->resetAfterTest(true);

        $service = CommerceGuestCheckoutService::create();
        $stalesession = $service->start('EUR', [
            'purchase_flow' => 'direct',
            'direct_purchase' => [
                'currency' => 'EUR',
                'sku' => 'TEST-DIRECT',
                'priceid' => 1,
                'quantity' => 1,
                'metadata' => [],
                'cartuuid' => bin2hex(random_bytes(16)),
            ],
        ]);

        $first = $service->identify(
            $stalesession,
            'l738-direct@example.test',
            'Direct',
            'Guest',
            true
        );

        self::assertSame('provisional', $first->get_status());
        self::assertNotNull($first->get_user_id());

        // Simulate a duplicate OTP AJAX request that still carries the session
        // object loaded before the first request provisioned the Moodle user.
        $second = $service->identify(
            $stalesession,
            'l738-direct@example.test',
            'Direct',
            'Guest',
            true
        );

        self::assertSame('provisional', $second->get_status());
        self::assertSame($first->get_user_id(), $second->get_user_id());
        self::assertSame(
            'same_checkout_provisional_resume',
            $second->get_metadata()['identity_resolution'] ?? null
        );
        self::assertSame(1, $DB->count_records('user', [
            'email' => 'l738-direct@example.test',
            'deleted' => 0,
        ]));
    }

    public function test_mark_verified_is_idempotent_for_same_locked_email(): void {
        global $DB;

        $this->resetAfterTest(true);

        $repository = new CommerceGuestCheckoutSessionRepository($DB);
        $session = CommerceGuestCheckoutService::create()->start('EUR');
        $verification = new CommerceGuestIdentityVerificationService(
            $repository,
            null
        );

        $pending = $verification->begin_verification(
            $session,
            'l738-idempotent@example.test',
            'OTP',
            'Guest'
        );
        $locked = $verification->mark_verified(
            $pending,
            'l738-idempotent@example.test',
            1789219000
        );
        $again = $verification->mark_verified(
            $locked,
            'l738-idempotent@example.test',
            1789219001
        );

        self::assertSame($locked->get_id(), $again->get_id());
        self::assertSame(
            'l738-idempotent@example.test',
            $again->get_metadata()['identity_verified_email'] ?? null
        );
    }
}
