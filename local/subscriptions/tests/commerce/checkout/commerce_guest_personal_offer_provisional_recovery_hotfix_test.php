<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutService;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutSessionRepository;

final class commerce_guest_personal_offer_provisional_recovery_hotfix_test extends \advanced_testcase {
    public function test_personal_offer_can_resume_our_unactivated_provisional_checkout_account(): void {
        global $DB;

        $this->resetAfterTest(true);

        $service = CommerceGuestCheckoutService::create();
        $original = $service->identify(
            $service->start('EUR'),
            'resume-personal-offer@example.test',
            'Resume',
            'Customer'
        );

        $this->assertSame('provisional', $original->get_status());
        $userid = $original->get_user_id();
        $this->assertNotNull($userid);

        // Simulate a later browser/session after the historical bug created another
        // identity_pending Guest Checkout session for the same Personal Offer email.
        $retry = $service->start('EUR', ['checkout_source' => 'personaloffer']);
        $resumed = $service->identify(
            $retry,
            'resume-personal-offer@example.test',
            'Resume',
            'Customer',
            true
        );

        $this->assertSame('provisional', $resumed->get_status());
        $this->assertSame($userid, $resumed->get_user_id());
        $this->assertSame('provisional_resume', $resumed->get_metadata()['identity_resolution']);
        $this->assertSame('guest_checkout', $resumed->get_metadata()['account_origin']);
        $this->assertSame('provisional', $resumed->get_metadata()['account_state']);
        $this->assertSame(1, $DB->count_records('user', [
            'email' => 'resume-personal-offer@example.test',
            'deleted' => 0,
        ]));
    }

    public function test_normal_checkout_does_not_resume_provisional_account_without_explicit_authorisation(): void {
        $this->resetAfterTest(true);

        $service = CommerceGuestCheckoutService::create();
        $original = $service->identify(
            $service->start('EUR'),
            'normal-no-resume@example.test',
            'Normal',
            'Customer'
        );

        $retry = $service->start('EUR');
        $resolved = $service->identify(
            $retry,
            'normal-no-resume@example.test',
            'Normal',
            'Customer'
        );

        $this->assertSame('existing_account', $resolved->get_status());
        $this->assertSame($original->get_user_id(), $resolved->get_user_id());
    }

    public function test_real_existing_moodle_account_still_requires_authentication_even_for_personal_offer(): void {
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user([
            'email' => 'real-account@example.test',
        ]);

        $resolved = CommerceGuestCheckoutService::create()->identify(
            CommerceGuestCheckoutService::create()->start('EUR'),
            'real-account@example.test',
            'Real',
            'Account',
            true
        );

        $this->assertSame('existing_account', $resolved->get_status());
        $this->assertSame((int)$user->id, $resolved->get_user_id());
    }

    public function test_purchase_blocks_silent_provisional_resume(): void {
        global $DB;

        $this->resetAfterTest(true);

        $service = CommerceGuestCheckoutService::create();
        $original = $service->identify(
            $service->start('EUR'),
            'purchase-blocks-resume@example.test',
            'Purchased',
            'Customer'
        );
        $userid = $original->get_user_id();

        // A minimal Native purchase dependency is enough to make the account
        // non-resumable through the pre-payment Personal Offer recovery path.
        $now = time();
        $reference = 'cmp_resume_block_' . bin2hex(random_bytes(4));
        $DB->insert_record('local_subscriptions_commerce_purchase', (object)[
            'purchaseuuid' => substr(hash('sha256', $reference), 0, 32),
            'reference' => $reference,
            'type' => 'digital',
            'legacyfamily' => null,
            'legacyid' => null,
            'userid' => $userid,
            'customeremail' => 'purchase-blocks-resume@example.test',
            'status' => 'payment_pending',
            'currency' => 'EUR',
            'subtotalminor' => 100,
            'discountminor' => 0,
            'totalminor' => 100,
            'customerjson' => '{}',
            'snapshotjson' => '{}',
            'metadatajson' => '{}',
            'snapshotversion' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $retry = $service->start('EUR', ['checkout_source' => 'personaloffer']);
        $resolved = $service->identify(
            $retry,
            'purchase-blocks-resume@example.test',
            'Purchased',
            'Customer',
            true
        );

        $this->assertSame('existing_account', $resolved->get_status());
        $this->assertSame($userid, $resolved->get_user_id());
    }

    public function test_public_personal_offer_checkout_opts_into_recovery_but_normal_checkout_does_not(): void {
        global $CFG;

        $page = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        $action = (string)file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout_action.php'
        );

        $this->assertStringContainsString(
            "(string)\$personalofferidentity['lastname'],\n                true",
            $page
        );
        $this->assertStringContainsString(
            '$personalofferidentity !== null',
            $action
        );
    }
}