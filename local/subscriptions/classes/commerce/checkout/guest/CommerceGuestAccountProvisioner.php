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
        string $lastname,
        bool $allowprovisionalresume = false
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
            $existinguserid = (int)$existing->id;

            // M9: a checkout_* account with Guest Checkout provenance and
            // no customer-defined password is not an "existing account" in the
            // authentication sense. Resume that exact provisional userid.
            $resumable = (new CommerceUnfinishedGuestCheckoutRecoveryService(
                $this->database,
                $this->sessions
            ))->find_source_session($existinguserid);

            if ($resumable !== null) {
                $metadata = [
                    'identity_resolution' => 'unfinished_guest_checkout_resume',
                    'account_origin' => 'guest_checkout',
                    'account_state' => 'provisional',
                    'provisional_user_resumed_at' => time(),
                    'provisional_user_source_session_id' => $resumable->get_id(),
                    'm9_recovered_at' => time(),
                ];
                if ($resumable->get_purchase_reference() !== null) {
                    $metadata['resume_purchase_reference'] = $resumable->get_purchase_reference();
                }
                if ($resumable->get_payment_reference() !== null) {
                    $metadata['resume_payment_reference'] = $resumable->get_payment_reference();
                }

                return $this->sessions->update_identity(
                    $session,
                    $existinguserid,
                    $email,
                    $firstname,
                    $lastname,
                    'provisional',
                    $metadata
                );
            }

            return $this->sessions->update_identity(
                $session,
                $existinguserid,
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

    /**
     * Returns the original Guest Checkout session proving that an existing Moodle
     * account is one of our still-unactivated provisional checkout accounts.
     *
     * This is intentionally conservative: normal suspended Moodle accounts are
     * never resumable without authentication.
     */
    private function find_resumable_provisional_account(int $userid): ?CommerceGuestCheckoutSession {
        return (new CommerceUnfinishedGuestCheckoutRecoveryService(
            $this->database,
            $this->sessions
        ))->find_source_session($userid);
    }
}
