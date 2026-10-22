<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\checkout;

use local_subscriptions\commerce\checkout\guest\CommerceGuestAccountActivationService;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutSessionRepository;

/** C5.14.1 regression coverage for Guest Checkout activation idempotence. */
final class commerce_798c5141_guest_activation_idempotence_test extends \advanced_testcase {
    public function test_repeated_issue_reuses_the_same_valid_activation_key(): void {
        global $DB;

        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user([
            'auth' => 'manual',
            'confirmed' => 1,
            'suspended' => 0,
        ]);

        $sessionid = $DB->insert_record('local_subs_commerce_guest', (object)[
            'reference' => 'gcs-c5141-' . bin2hex(random_bytes(6)),
            'token' => bin2hex(random_bytes(32)),
            'status' => 'active',
            'currency' => 'EUR',
            'userid' => $user->id,
            'email' => $user->email,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'purchasereference' => 'cmp-c5141',
            'paymentreference' => 'pay-c5141',
            'expiresat' => 0,
            'metadatajson' => json_encode([
                'account_origin' => 'guest_checkout',
                'account_state' => 'active',
                'activation_requires_password_reset' => true,
            ], JSON_THROW_ON_ERROR),
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $sessions = new CommerceGuestCheckoutSessionRepository($DB);
        $service = new CommerceGuestAccountActivationService($DB, $sessions);

        $first = $service->issue_activation_url($sessions->require_by_id((int)$sessionid));
        $second = $service->issue_activation_url($sessions->require_by_id((int)$sessionid));

        $this->assertSame((string)$first, (string)$second);
        $firstparams = $first->params();
        $secondparams = $second->params();
        $this->assertSame((string)$firstparams['key'], (string)$secondparams['key']);

        $keys = $DB->get_records('user_private_key', [
            'script' => CommerceGuestAccountActivationService::KEY_SCRIPT,
            'userid' => $user->id,
            'instance' => $sessionid,
        ]);
        $this->assertCount(1, $keys);

        $session = $sessions->require_by_id((int)$sessionid);
        $metadata = $session->get_metadata();
        $this->assertGreaterThan(0, (int)($metadata['activation_link_issued_at'] ?? 0));
        $this->assertGreaterThan(time(), (int)($metadata['activation_link_expires_at'] ?? 0));
    }

    public function test_activation_mail_uses_one_stable_intent_per_guest_session(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
                . '/local/subscriptions/classes/commerce/checkout/guest/'
                . 'CommerceGuestAccountActivator.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            "'guest-account-activation:' . \$session->get_id()",
            $source
        );
        $this->assertStringNotContainsString(
            "hash('sha256', \$activationurl->out(false))",
            $source
        );
    }
}
