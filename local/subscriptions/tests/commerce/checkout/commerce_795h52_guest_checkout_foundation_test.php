<?php

declare(strict_types=1);

namespace local_subscriptions;

use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutService;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutSessionRepository;

final class commerce_795h52_guest_checkout_foundation_test extends \advanced_testcase {
    public function test_session_is_durable_and_uses_provisional_lifecycle(): void {
        global $DB;
        $this->resetAfterTest();

        $service = CommerceGuestCheckoutService::create();
        $session = $service->start('EUR', ['source' => 'phpunit']);

        $this->assertStringStartsWith('gcs_', $session->get_reference());
        $this->assertSame('identity_pending', $session->get_status());
        $this->assertSame('EUR', $session->get_currency());
        $this->assertGreaterThan(time(), $session->get_expires_at());

        $reloaded = (new CommerceGuestCheckoutSessionRepository($DB))->find_by_token($session->get_token());
        $this->assertNotNull($reloaded);
        $this->assertSame($session->get_reference(), $reloaded->get_reference());
    }

    public function test_new_identity_creates_suspended_provisional_user(): void {
        global $DB;
        $this->resetAfterTest();

        $service = CommerceGuestCheckoutService::create();
        $session = $service->start('EUR');
        $identified = $service->identify($session, 'guest-checkout@example.test', 'Nata', 'CampusFR');

        $this->assertSame('provisional', $identified->get_status());
        $this->assertNotNull($identified->get_user_id());
        $user = $DB->get_record('user', ['id' => $identified->get_user_id()], '*', MUST_EXIST);
        $this->assertSame(1, (int) $user->suspended);
        $this->assertSame(0, (int) $user->confirmed);
    }

    public function test_existing_email_requires_authentication_and_creates_no_duplicate(): void {
        global $DB;
        $this->resetAfterTest();
        $existing = $this->getDataGenerator()->create_user(['email' => 'existing@example.test']);

        $service = CommerceGuestCheckoutService::create();
        $identified = $service->identify(
            $service->start('EUR'),
            'existing@example.test',
            'Existing',
            'Customer'
        );

        $this->assertSame('existing_account', $identified->get_status());
        $this->assertSame((int) $existing->id, $identified->get_user_id());
        $this->assertSame(1, $DB->count_records('user', ['email' => 'existing@example.test', 'deleted' => 0]));
    }
}
