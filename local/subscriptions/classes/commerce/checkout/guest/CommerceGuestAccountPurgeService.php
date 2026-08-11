<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\guest;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\persistence\CommercePersistenceSchema;

/** Conservatively expires sessions and deletes dependency-free provisional users. */
final class CommerceGuestAccountPurgeService {
    public function __construct(
        private readonly \moodle_database $database,
        private readonly CommerceGuestCheckoutSessionRepository $sessions,
        private readonly CommerceGuestCheckoutLifecycleService $lifecycle
    ) {}

    /** @return array<string, mixed> */
    public function process(CommerceGuestCheckoutSession $session, bool $execute): array {
        $reasons = $this->blocking_reasons($session);
        $purgeable = $reasons === [];
        $action = $purgeable ? 'purge' : 'retain';

        if (!$execute) {
            return $this->result($session, $action, $purgeable, $reasons, false);
        }

        if (!$purgeable) {
            $this->lifecycle->expire_abandoned($session);
            return $this->result($session, 'retained_expired', false, $reasons, true);
        }

        $userid = $session->get_user_id();
        if ($userid !== null) {
            global $CFG;
            require_once($CFG->dirroot . '/user/lib.php');
            $user = $this->database->get_record('user', ['id' => $userid, 'deleted' => 0]);
            if ($user !== false && !delete_user($user)) {
                throw new \RuntimeException('Unable to delete the provisional Guest Checkout user.');
            }
        }

        $this->sessions->transition($session, 'purged', [
            'metadatajson' => array_replace($session->get_metadata(), [
                'purged_at' => time(),
                'purged_userid' => $userid,
            ]),
        ]);

        return $this->result($session, 'purged', true, [], true);
    }

    /** @return string[] */
    private function blocking_reasons(CommerceGuestCheckoutSession $session): array {
        $reasons = [];
        $metadata = $session->get_metadata();
        if (($metadata['account_origin'] ?? '') !== 'guest_checkout') {
            $reasons[] = 'not_provisional_guest_account';
        }
        if ($session->get_purchase_reference() !== null) {
            $reasons[] = 'purchase_reference_present';
        }
        if ($session->get_payment_reference() !== null) {
            $reasons[] = 'payment_reference_present';
        }

        $userid = $session->get_user_id();
        if ($userid === null) {
            return $reasons;
        }

        if ($this->database->record_exists(CommercePersistenceSchema::TABLE_PURCHASE, ['userid' => $userid])) {
            $reasons[] = 'native_purchase_present';
        }
        if ($this->database->record_exists('user_enrolments', ['userid' => $userid])) {
            $reasons[] = 'enrolment_present';
        }
        if ($this->database->record_exists('role_assignments', ['userid' => $userid])) {
            $reasons[] = 'role_assignment_present';
        }

        return array_values(array_unique($reasons));
    }

    /** @return array<string, mixed> */
    private function result(
        CommerceGuestCheckoutSession $session,
        string $action,
        bool $purgeable,
        array $reasons,
        bool $executed
    ): array {
        return [
            'reference' => $session->get_reference(),
            'userid' => $session->get_user_id(),
            'status' => $session->get_status(),
            'action' => $action,
            'purgeable' => $purgeable,
            'reasons' => $reasons,
            'executed' => $executed,
        ];
    }
}
