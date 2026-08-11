<?php

declare(strict_types=1);

namespace local_campus;

defined('MOODLE_INTERNAL') || die();

final class trial_account_finalisation_test extends \advanced_testcase {
    public function test_trial_converts_provisional_checkout_account_without_forcing_password_change(): void {
        global $CFG, $DB;

        $this->resetAfterTest(true);
        require_once($CFG->dirroot . '/local/campus/lib.php');
        require_once($CFG->libdir . '/moodlelib.php');

        $password = 'CampusFR#Trial2026!';
        $user = $this->getDataGenerator()->create_user([
            'auth' => 'manual',
            'username' => 'checkout_' . bin2hex(random_bytes(12)),
            'email' => 'trial-provisional@example.test',
            'firstname' => 'Trial',
            'lastname' => 'Customer',
            'confirmed' => 0,
            'suspended' => 1,
        ]);
        set_user_preference('auth_forcepasswordchange', 1, (int)$user->id);
        $provisionalusername = (string)$user->username;

        $DB->insert_record('local_subs_commerce_guest', (object)[
            'reference' => 'gcs_' . bin2hex(random_bytes(12)),
            'token' => bin2hex(random_bytes(32)),
            'status' => 'provisional',
            'currency' => 'EUR',
            'userid' => (int)$user->id,
            'email' => $user->email,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'purchasereference' => null,
            'paymentreference' => null,
            'expiresat' => time() + DAYSECS,
            'metadatajson' => json_encode([
                'account_origin' => 'guest_checkout',
                'account_state' => 'provisional',
                'activation_requires_password_reset' => true,
            ], JSON_THROW_ON_ERROR),
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $final = local_campus_finalise_trial_account(
            $user,
            'Nata',
            'Test',
            $user->email,
            $password
        );

        self::assertFalse(str_starts_with((string)$final->username, 'checkout_'));
        self::assertSame(1, (int)$final->confirmed);
        self::assertSame(0, (int)$final->suspended);
        self::assertSame('Nata', (string)$final->firstname);
        self::assertSame('Test', (string)$final->lastname);
        self::assertTrue(validate_internal_user_password($final, $password));
        self::assertFalse((bool)get_user_preferences(
            'auth_forcepasswordchange',
            false,
            (int)$final->id
        ));

        $guest = $DB->get_record(
            'local_subs_commerce_guest',
            ['userid' => (int)$final->id],
            '*',
            MUST_EXIST
        );
        $metadata = json_decode((string)$guest->metadatajson, true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('trial', $metadata['account_origin']);
        self::assertSame('ready', $metadata['account_state']);
        self::assertFalse((bool)$metadata['activation_requires_password_reset']);
        self::assertSame($provisionalusername, $metadata['provisional_username']);
        self::assertSame((string)$final->username, $metadata['final_username']);
    }

    public function test_trial_does_not_mutate_checkout_that_already_has_payment(): void {
        global $CFG, $DB;

        $this->resetAfterTest(true);
        require_once($CFG->dirroot . '/local/campus/lib.php');

        $user = $this->getDataGenerator()->create_user([
            'username' => 'checkout_' . bin2hex(random_bytes(12)),
            'email' => 'trial-paid-checkout@example.test',
        ]);

        $guestid = $DB->insert_record('local_subs_commerce_guest', (object)[
            'reference' => 'gcs_' . bin2hex(random_bytes(12)),
            'token' => bin2hex(random_bytes(32)),
            'status' => 'payment_pending',
            'currency' => 'EUR',
            'userid' => (int)$user->id,
            'email' => $user->email,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'purchasereference' => 'cmp_trial_guard',
            'paymentreference' => 'pay_trial_guard',
            'expiresat' => time() + DAYSECS,
            'metadatajson' => json_encode([
                'account_origin' => 'guest_checkout',
                'account_state' => 'provisional',
                'activation_requires_password_reset' => true,
            ], JSON_THROW_ON_ERROR),
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        local_campus_finalise_trial_account(
            $user,
            'Paid',
            'Checkout',
            $user->email,
            'CampusFR#Trial2026!'
        );

        $guest = $DB->get_record(
            'local_subs_commerce_guest',
            ['id' => $guestid],
            '*',
            MUST_EXIST
        );
        $metadata = json_decode((string)$guest->metadatajson, true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('guest_checkout', $metadata['account_origin']);
        self::assertTrue((bool)$metadata['activation_requires_password_reset']);
    }
}