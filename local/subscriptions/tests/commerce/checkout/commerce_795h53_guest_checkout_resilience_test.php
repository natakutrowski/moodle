<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\checkout\guest\CommerceGuestAccountPurgeService;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutLifecycleService;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutService;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutSessionRepository;

final class commerce_795h53_guest_checkout_resilience_test extends \advanced_testcase {
    public function test_payment_failure_is_retained_for_thirty_days(): void {
        global $DB;
        $this->resetAfterTest(true);

        $service = CommerceGuestCheckoutService::create();
        $session = $service->identify(
            $service->start('EUR'),
            'failed-h53@example.test',
            'Failed',
            'Guest'
        );
        $repository = new CommerceGuestCheckoutSessionRepository($DB);
        $session = $repository->attach_payment($session, 'purchase-h53', 'payment-h53');
        $failed = (new CommerceGuestCheckoutLifecycleService($repository))
            ->mark_payment_failed('purchase-h53');

        $this->assertSame('payment_failed', $failed?->get_status());
        $this->assertGreaterThanOrEqual(
            time() + CommerceGuestCheckoutService::PAYMENT_FAILURE_TTL - 5,
            $failed?->get_expires_at()
        );
    }

    public function test_dependency_free_provisional_account_is_purgeable_in_dry_run(): void {
        global $DB;
        $this->resetAfterTest(true);

        $service = CommerceGuestCheckoutService::create();
        $session = $service->identify(
            $service->start('EUR'),
            'purge-h53@example.test',
            'Purge',
            'Guest'
        );
        $repository = new CommerceGuestCheckoutSessionRepository($DB);
        $session = $repository->transition($session, 'provisional', ['expiresat' => time() - 1]);
        $purger = new CommerceGuestAccountPurgeService(
            $DB,
            $repository,
            new CommerceGuestCheckoutLifecycleService($repository)
        );

        $result = $purger->process($session, false);
        $this->assertTrue($result['purgeable']);
        $this->assertSame('purge', $result['action']);
        $this->assertSame(0, (int) $DB->get_field('user', 'deleted', ['id' => $session->get_user_id()]));
    }

    public function test_purchase_reference_blocks_account_purge(): void {
        global $DB;
        $this->resetAfterTest(true);

        $service = CommerceGuestCheckoutService::create();
        $session = $service->identify(
            $service->start('EUR'),
            'retain-h53@example.test',
            'Retain',
            'Guest'
        );
        $repository = new CommerceGuestCheckoutSessionRepository($DB);
        $session = $repository->attach_payment($session, 'purchase-retain-h53', 'payment-retain-h53');
        $session = $repository->transition($session, 'payment_failed', ['expiresat' => time() - 1]);
        $purger = new CommerceGuestAccountPurgeService(
            $DB,
            $repository,
            new CommerceGuestCheckoutLifecycleService($repository)
        );

        $result = $purger->process($session, false);
        $this->assertFalse($result['purgeable']);
        $this->assertContains('purchase_reference_present', $result['reasons']);
        $this->assertContains('payment_reference_present', $result['reasons']);
    }
}
