<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\guest;

defined('MOODLE_INTERNAL') || die();

/** Resolves an existing account or creates a suspended provisional Moodle account. */
final class CommerceGuestAccountProvisioner {
    public function __construct(
        private readonly \moodle_database $database,
        private readonly CommerceGuestCheckoutSessionRepository $sessions
    ) {}

    public function provision(
        CommerceGuestCheckoutSession $session,
        string $email,
        string $firstname,
        string $lastname
    ): CommerceGuestCheckoutSession {
        global $CFG;

        $identity = CommerceGuestIdentityValidator::validate($email, $firstname, $lastname);
        $email = $identity['email'];
        $firstname = $identity['firstname'];
        $lastname = $identity['lastname'];

        $emailcondition = $this->database->sql_equal('email', ':email', false);
        $existingaccounts = $this->database->get_records_sql(
            "SELECT id, email
               FROM {user}
              WHERE {$emailcondition}
                AND deleted = 0
                AND mnethostid = :mnethostid
           ORDER BY id ASC",
            [
                'email' => $email,
                'mnethostid' => (int)$CFG->mnet_localhost_id,
            ],
            0,
            2
        );
        if (count($existingaccounts) > 1) {
            throw new \moodle_exception(
                'commerce_guest_checkout_duplicate_email_accounts',
                'local_subscriptions'
            );
        }
        $existing = reset($existingaccounts);
        if ($existing !== false) {
            return $this->sessions->update_identity(
                $session,
                (int) $existing->id,
                $email,
                $firstname,
                $lastname,
                'existing_account',
                ['identity_resolution' => 'authentication_required']
            );
        }

        require_once($CFG->dirroot . '/user/lib.php');
        $user = (object) [
            'auth' => 'manual',
            'confirmed' => 0,
            'suspended' => 1,
            'mnethostid' => (int) $CFG->mnet_localhost_id,
            'username' => 'checkout_' . substr(hash('sha256', $session->get_reference()), 0, 24),
            'password' => 'Aa#' . bin2hex(random_bytes(24)),
            'email' => $email,
            'firstname' => trim($firstname),
            'lastname' => trim($lastname),
            'lang' => current_language(),
            'description' => 'CampusFR provisional Guest Checkout account.',
        ];
        $userid = user_create_user($user, true, false);

        return $this->sessions->update_identity(
            $session,
            (int) $userid,
            $email,
            $firstname,
            $lastname,
            'provisional',
            [
                'account_origin' => 'guest_checkout',
                'account_state' => 'provisional',
                'provisional_user_created_at' => time(),
            ]
        );
    }
}
