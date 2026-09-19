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

        $resetprovisionaluserid =
            (int)(
                $session->get_metadata()['identity_reset_provisional_userid']
                ?? 0
            );

        if ($resetprovisionaluserid > 0) {
            $owned = $this->database->get_record(
                'user',
                [
                    'id' => $resetprovisionaluserid,
                    'deleted' => 0,
                    'mnethostid' => (int)$CFG->mnet_localhost_id,
                ],
                'id,username,email,confirmed,suspended',
                IGNORE_MISSING
            );

            $expectedusername =
                'checkout_'
                . substr(
                    hash(
                        'sha256',
                        $session->get_reference()
                    ),
                    0,
                    24
                );

            if (
                $owned !== false
                && (string)$owned->username === $expectedusername
                && (int)$owned->confirmed === 0
                && (int)$owned->suspended === 1
            ) {
                require_once(
                    $CFG->dirroot . '/user/lib.php'
                );

                $owned->email = $email;
                $owned->firstname = trim($firstname);
                $owned->lastname = trim($lastname);
                user_update_user(
                    $owned,
                    false,
                    false
                );

                $metadata =
                    $session->get_metadata();
                unset(
                    $metadata['identity_reset_provisional_userid']
                );
                $metadata = array_replace(
                    $metadata,
                    [
                        'identity_resolution' =>
                            'same_checkout_provisional_reuse',
                        'account_origin' => 'guest_checkout',
                        'account_state' => 'provisional',
                        'provisional_user_reused_at' => time(),
                    ]
                );

                return $this->sessions->update_identity(
                    $session,
                    $resetprovisionaluserid,
                    $email,
                    $firstname,
                    $lastname,
                    'provisional',
                    $metadata
                );
            }
        }

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

            // L7.3.8: OTP verification is an AJAX boundary and a duplicate
            // request can still hold the pre-provisioning session object while
            // the first request has already provisioned this very Guest
            // Checkout row. Refresh the authoritative row before classifying
            // the freshly-created checkout_* user as a real existing account.
            $current = $this->sessions->require_by_id($session->get_id());
            $currentmetadata = $current->get_metadata();
            $currentemail = \core_text::strtolower(
                trim((string)($current->get_email() ?? ''))
            );

            if (
                $current->get_user_id() === $existinguserid
                && $current->get_status() === 'provisional'
                && $currentemail === $email
                && ($currentmetadata['account_origin'] ?? '') === 'guest_checkout'
                && ($currentmetadata['account_state'] ?? '') === 'provisional'
                && empty($currentmetadata['password_set_at'])
            ) {
                return $this->sessions->update_identity(
                    $current,
                    $existinguserid,
                    $email,
                    $firstname,
                    $lastname,
                    'provisional',
                    [
                        'identity_resolution' => 'same_checkout_provisional_resume',
                        'same_checkout_provisional_resumed_at' => time(),
                    ]
                );
            }

            // M9: a checkout_* account with Guest Checkout provenance and
            // no customer-defined password is not an "existing account" in the
            // authentication sense. Resume that exact provisional userid.
            $resumable = (new CommerceUnfinishedGuestCheckoutRecoveryService(
                $this->database,
                $this->sessions
            ))->find_source_session(
                $existinguserid,
                $session->get_id()
            );

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
