<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\checkout\guest\CommerceGuestAccountActivationService;
use local_subscriptions\commerce\checkout\guest\CommerceGuestAccountActivator;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutSessionRepository;

/** L7.3.10 regression coverage for paid Guest Checkout account activation. */
final class commerce_797l7310_guest_activation_reconciliation_test extends \advanced_testcase {
    public function test_active_checkout_status_does_not_hide_unactivated_provisional_user(): void {
        global $DB;

        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user([
            'auth' => 'manual',
            'confirmed' => 0,
            'suspended' => 1,
        ]);

        $sessionid = $DB->insert_record('local_subs_commerce_guest', (object)[
            'reference' => 'gcs_' . bin2hex(random_bytes(8)),
            'token' => bin2hex(random_bytes(32)),
            'status' => 'active',
            'currency' => 'EUR',
            'userid' => $user->id,
            'email' => $user->email,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'purchasereference' => 'cmp_l7310_paid',
            'paymentreference' => 'cmp_l7310_paid',
            'expiresat' => 0,
            'metadatajson' => json_encode([
                'account_origin' => 'guest_checkout',
                'account_state' => 'provisional',
                // Avoid exercising mail delivery here; this reproduces the
                // historical partial state after the password form was posted.
                'password_set_at' => time(),
                'activation_requires_password_reset' => false,
            ]),
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $repository = new CommerceGuestCheckoutSessionRepository($DB);
        $resolved = (new CommerceGuestAccountActivator($DB, $repository))
            ->activate_for_purchase('cmp_l7310_paid');

        $updated = $DB->get_record('user', ['id' => $user->id], '*', MUST_EXIST);
        self::assertSame(1, (int)$updated->confirmed);
        self::assertSame(0, (int)$updated->suspended);
        self::assertSame('active', $resolved?->get_status());
        self::assertSame('ready', $resolved?->get_metadata()['account_state']);
        self::assertGreaterThan(0, (int)$resolved?->get_metadata()['activated_at']);
    }

    public function test_activation_service_refuses_unactivated_provisional_user_before_password_mutation(): void {
        global $DB;

        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user([
            'auth' => 'manual',
            'confirmed' => 0,
            'suspended' => 1,
        ]);

        $sessionid = $DB->insert_record('local_subs_commerce_guest', (object)[
            'reference' => 'gcs_' . bin2hex(random_bytes(8)),
            'token' => bin2hex(random_bytes(32)),
            'status' => 'active',
            'currency' => 'EUR',
            'userid' => $user->id,
            'email' => $user->email,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'purchasereference' => 'cmp_l7310_guard',
            'paymentreference' => 'cmp_l7310_guard',
            'expiresat' => 0,
            'metadatajson' => json_encode([
                'account_origin' => 'guest_checkout',
                'account_state' => 'provisional',
            ]),
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $repository = new CommerceGuestCheckoutSessionRepository($DB);
        $session = $repository->require_by_id((int)$sessionid);
        $service = new CommerceGuestAccountActivationService($DB, $repository);
        $url = $service->issue_activation_url($session);
        $params = $url->params();

        $this->expectException(\moodle_exception::class);
        $service->complete(
            (string)$params['key'],
            (int)$user->id,
            (int)$sessionid,
            'CampusFR#L7310Guard2026!',
            false
        );
    }

    public function test_failed_order_result_does_not_advertise_confirmed_purchase_activation(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents($root . '/order_result.php');
        $start = file_get_contents($root . '/guest_account_activation_start.php');

        self::assertStringContainsString('$order->is_paid()', $source);
        self::assertStringContainsString("\$state->code === 'success'", $source);
        self::assertStringContainsString("\$order === null || !\$order->is_paid()", $start);
        self::assertStringContainsString('CommerceGuestAccountActivator', $start);
    }
}
