<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\guest;

defined('MOODLE_INTERNAL') || die();

/**
 * Recovers Guest Checkout accounts that were provisioned but never activated.
 *
 * The policy is deliberately strict: a normal suspended Moodle account is
 * never recoverable here. We require the original checkout_* identity AND a
 * Guest Checkout provenance record with no password_set_at marker.
 */
final class CommerceUnfinishedGuestCheckoutRecoveryService {
    private const SOURCE_STATUSES = [
        'provisional',
        'payment_pending',
        'paid_pending_activation',
        'active',
    ];

    public function __construct(
        private readonly \moodle_database $database,
        private readonly CommerceGuestCheckoutSessionRepository $sessions
    ) {
    }

    public static function create(): self {
        global $DB;
        return new self($DB, new CommerceGuestCheckoutSessionRepository($DB));
    }

    public function find_source_session(int $userid): ?CommerceGuestCheckoutSession {
        if (!$this->is_unactivated_provisional_user($userid)) {
            return null;
        }

        $records = $this->database->get_records(
            'local_subs_commerce_guest',
            ['userid' => $userid],
            'id DESC'
        );

        foreach ($records as $record) {
            $session = new CommerceGuestCheckoutSession($record);
            $metadata = $session->get_metadata();

            if (($metadata['account_origin'] ?? '') !== 'guest_checkout') {
                continue;
            }
            if (!empty($metadata['password_set_at'])) {
                return null;
            }
            if (($metadata['account_state'] ?? '') === 'ready') {
                return null;
            }
            if (!in_array($session->get_status(), self::SOURCE_STATUSES, true)) {
                continue;
            }

            return $session;
        }

        return null;
    }

    /**
     * Converts a newly-created "existing_account" checkout session back to the
     * same safe provisional identity when that Moodle account never had a
     * password available to the customer.
     */
    public function recover_session_if_possible(
        CommerceGuestCheckoutSession $session
    ): CommerceGuestCheckoutSession {
        if ($session->get_status() !== 'existing_account' || $session->get_user_id() === null) {
            return $session;
        }

        $source = $this->find_source_session($session->get_user_id());
        if ($source === null) {
            return $session;
        }

        $metadata = array_replace($session->get_metadata(), [
            'identity_resolution' => 'unfinished_guest_checkout_resume',
            'account_origin' => 'guest_checkout',
            'account_state' => 'provisional',
            'm9_recovered_at' => time(),
            'm9_recovery_source_session_id' => $source->get_id(),
        ]);

        if ($source->get_purchase_reference() !== null) {
            $metadata['resume_purchase_reference'] = $source->get_purchase_reference();
        }
        if ($source->get_payment_reference() !== null) {
            $metadata['resume_payment_reference'] = $source->get_payment_reference();
        }

        return $this->sessions->update_identity(
            $session,
            $session->get_user_id(),
            (string)($session->get_email() ?? $source->get_email() ?? ''),
            (string)($session->get_first_name() ?? $source->get_first_name() ?? ''),
            (string)($session->get_last_name() ?? $source->get_last_name() ?? ''),
            'provisional',
            $metadata
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function audit(?string $email = null): array {
        global $CFG;

        $params = ['mnethostid' => (int)$CFG->mnet_localhost_id];
        $where = [
            'u.deleted = 0',
            'u.mnethostid = :mnethostid',
            'u.auth = :auth',
            'u.confirmed = 0',
            'u.suspended = 1',
            $this->database->sql_like('u.username', ':username', false),
        ];
        $params['auth'] = 'manual';
        $params['username'] = 'checkout_%';

        if ($email !== null && trim($email) !== '') {
            $where[] = $this->database->sql_equal('u.email', ':email', false);
            $params['email'] = \core_text::strtolower(trim($email));
        }

        $users = $this->database->get_records_sql(
            'SELECT u.id,u.username,u.email,u.firstname,u.lastname,u.timecreated,u.timemodified
               FROM {user} u
              WHERE ' . implode(' AND ', $where) . '
           ORDER BY u.id ASC',
            $params
        );

        $out = [];
        foreach ($users as $user) {
            $source = $this->find_source_session((int)$user->id);
            if ($source === null) {
                continue;
            }

            $purchases = $this->database->get_records(
                'local_subscriptions_commerce_purchase',
                ['userid' => (int)$user->id],
                'timecreated DESC',
                'id,reference,status,currency,totalminor,timecreated'
            );
            $stucksessions = 0;
            foreach ($this->database->get_records(
                'local_subs_commerce_guest',
                ['userid' => (int)$user->id, 'status' => 'existing_account'],
                'id DESC'
            ) as $unused) {
                $stucksessions++;
            }

            $out[] = [
                'userid' => (int)$user->id,
                'username' => (string)$user->username,
                'email' => (string)$user->email,
                'source_session_id' => $source->get_id(),
                'source_status' => $source->get_status(),
                'purchase_reference' => $source->get_purchase_reference(),
                'payment_reference' => $source->get_payment_reference(),
                'stuck_sessions' => $stucksessions,
                'purchases' => array_values($purchases),
            ];
        }

        return $out;
    }

    /**
     * Repairs currently-stuck browser sessions. User suspension/confirmation is
     * intentionally left untouched until the normal paid-account activation.
     */
    public function repair_stuck_sessions(?string $email = null): array {
        $result = ['users' => 0, 'sessions' => 0];

        foreach ($this->audit($email) as $candidate) {
            $result['users']++;
            $records = $this->database->get_records(
                'local_subs_commerce_guest',
                [
                    'userid' => (int)$candidate['userid'],
                    'status' => 'existing_account',
                ],
                'id ASC'
            );

            foreach ($records as $record) {
                $session = new CommerceGuestCheckoutSession($record);
                $recovered = $this->recover_session_if_possible($session);
                if ($recovered->get_status() === 'provisional') {
                    $result['sessions']++;
                }
            }
        }

        return $result;
    }

    private function is_unactivated_provisional_user(int $userid): bool {
        $user = $this->database->get_record(
            'user',
            ['id' => $userid, 'deleted' => 0],
            'id,username,auth,confirmed,suspended',
            IGNORE_MISSING
        );

        return $user !== false
            && str_starts_with((string)$user->username, 'checkout_')
            && (string)$user->auth === 'manual'
            && (int)$user->confirmed === 0
            && (int)$user->suspended === 1;
    }
}
