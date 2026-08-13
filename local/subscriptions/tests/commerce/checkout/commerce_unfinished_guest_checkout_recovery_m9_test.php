<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\checkout\guest\CommerceGuestAccountProvisioner;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutSessionRepository;
use local_subscriptions\commerce\checkout\guest\CommerceUnfinishedGuestCheckoutRecoveryService;

final class commerce_unfinished_guest_checkout_recovery_m9_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_unactivated_checkout_account_with_existing_purchase_is_recoverable(): void {
        global $DB;

        [$user, $source] = $this->seed_unfinished_account(true);
        $current = $this->seed_current_existing_account_session((int)$user->id, $user->email);

        $service = new CommerceUnfinishedGuestCheckoutRecoveryService(
            $DB,
            new CommerceGuestCheckoutSessionRepository($DB)
        );
        $recovered = $service->recover_session_if_possible($current);

        self::assertSame('provisional', $recovered->get_status());
        self::assertSame((int)$user->id, $recovered->get_user_id());
        self::assertSame('unfinished_guest_checkout_resume', $recovered->get_metadata()['identity_resolution']);
        self::assertSame($source->get_purchase_reference(), $recovered->get_metadata()['resume_purchase_reference']);
    }

    public function test_normal_suspended_account_is_never_recovered(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user([
            'auth' => 'manual',
            'confirmed' => 1,
            'suspended' => 1,
            'username' => 'normal-suspended-user',
        ]);
        $current = $this->seed_current_existing_account_session((int)$user->id, $user->email);

        $service = new CommerceUnfinishedGuestCheckoutRecoveryService(
            $DB,
            new CommerceGuestCheckoutSessionRepository($DB)
        );

        self::assertSame('existing_account', $service->recover_session_if_possible($current)->get_status());
    }

    public function test_cli_repair_only_changes_stuck_guest_sessions(): void {
        global $DB;

        [$user] = $this->seed_unfinished_account(false);
        $current = $this->seed_current_existing_account_session((int)$user->id, $user->email);

        $service = new CommerceUnfinishedGuestCheckoutRecoveryService(
            $DB,
            new CommerceGuestCheckoutSessionRepository($DB)
        );
        $result = $service->repair_stuck_sessions($user->email);

        self::assertSame(1, $result['users']);
        self::assertSame(1, $result['sessions']);
        self::assertSame(
            'provisional',
            (new CommerceGuestCheckoutSessionRepository($DB))
                ->require_by_id($current->get_id())
                ->get_status()
        );

        $updated = $DB->get_record('user', ['id' => $user->id], '*', MUST_EXIST);
        self::assertSame(1, (int)$updated->suspended);
        self::assertSame(0, (int)$updated->confirmed);
    }

    public function test_checkout_files_use_m9_self_healing(): void {
        global $CFG;

        $page = file_get_contents($CFG->dirroot . '/local/subscriptions/commerce_checkout.php');
        $action = file_get_contents($CFG->dirroot . '/local/subscriptions/commerce_checkout_action.php');

        self::assertStringContainsString('CommerceUnfinishedGuestCheckoutRecoveryService', $page);
        self::assertStringContainsString('CommerceUnfinishedGuestCheckoutRecoveryService', $action);
        self::assertStringContainsString('recover_session_if_possible', $page);
        self::assertStringContainsString('recover_session_if_possible', $action);
    }

    private function seed_unfinished_account(bool $withpurchase): array {
        global $DB;

        $now = time();
        $user = $this->getDataGenerator()->create_user([
            'username' => 'checkout_' . substr(hash('sha256', random_bytes(8)), 0, 24),
            'auth' => 'manual',
            'confirmed' => 0,
            'suspended' => 1,
        ]);

        $purchase = $withpurchase ? 'cmp_m9_pending' : null;
        if ($withpurchase) {
            $DB->insert_record('local_subscriptions_commerce_purchase', (object)[
                'purchaseuuid' => md5('m9-' . $user->id),
                'reference' => $purchase,
                'type' => 'digital',
                'legacyfamily' => null,
                'legacyid' => null,
                'userid' => $user->id,
                'customeremail' => $user->email,
                'status' => 'payment_pending',
                'currency' => 'EUR',
                'subtotalminor' => 3900,
                'discountminor' => 0,
                'totalminor' => 3900,
                'customerjson' => '{}',
                'snapshotjson' => '{}',
                'metadatajson' => '{}',
                'snapshotversion' => 1,
                'timecreated' => $now,
                'timemodified' => $now,
            ]);
        }

        $id = $DB->insert_record('local_subs_commerce_guest', (object)[
            'reference' => 'gcs_m9_source_' . $user->id,
            'token' => hash('sha256', 'source-' . $user->id),
            'status' => $withpurchase ? 'payment_pending' : 'provisional',
            'currency' => 'EUR',
            'userid' => $user->id,
            'email' => $user->email,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'purchasereference' => $purchase,
            'paymentreference' => $withpurchase ? 'pay_m9_pending' : null,
            'expiresat' => $now + 86400,
            'metadatajson' => json_encode([
                'account_origin' => 'guest_checkout',
                'account_state' => 'provisional',
            ]),
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $repo = new CommerceGuestCheckoutSessionRepository($DB);
        return [$user, $repo->require_by_id((int)$id)];
    }

    private function seed_current_existing_account_session(int $userid, string $email) {
        global $DB;

        $now = time();
        $id = $DB->insert_record('local_subs_commerce_guest', (object)[
            'reference' => 'gcs_m9_current_' . bin2hex(random_bytes(4)),
            'token' => hash('sha256', random_bytes(16)),
            'status' => 'existing_account',
            'currency' => 'EUR',
            'userid' => $userid,
            'email' => $email,
            'firstname' => 'Test',
            'lastname' => 'User',
            'purchasereference' => null,
            'paymentreference' => null,
            'expiresat' => $now + 86400,
            'metadatajson' => json_encode([
                'identity_resolution' => 'authentication_required',
            ]),
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        return (new CommerceGuestCheckoutSessionRepository($DB))->require_by_id((int)$id);
    }
}
