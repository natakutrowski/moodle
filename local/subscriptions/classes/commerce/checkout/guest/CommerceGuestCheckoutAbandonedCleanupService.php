<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\checkout\guest;

defined('MOODLE_INTERNAL') || die();

final class CommerceGuestCheckoutAbandonedCleanupService {
    public function __construct(
        private readonly \moodle_database $database,
        private readonly CommerceUnfinishedGuestCheckoutRecoveryService $recovery
    ) {
    }

    public static function create(): self {
        global $DB;
        return new self($DB, CommerceUnfinishedGuestCheckoutRecoveryService::create());
    }

    public function run(int $minimumage, int $limit): array {
        $minimumage = max(DAYSECS, $minimumage);
        $limit = max(1, min(100, $limit));
        $cutoff = time() - $minimumage;

        $result = [
            'candidates' => 0,
            'deleted_users' => 0,
            'skipped_activity' => 0,
            'skipped_recent' => 0,
            'failed' => 0,
        ];

        foreach ($this->recovery->audit() as $candidate) {
            if ($result['candidates'] >= $limit) {
                break;
            }
            if ($candidate['purchases'] !== []) {
                continue;
            }
            if ($candidate['source_status'] !== 'provisional') {
                continue;
            }

            $result['candidates']++;
            $created = $this->database->get_field(
                'local_subs_commerce_guest',
                'timecreated',
                ['id' => (int)$candidate['source_session_id']],
                IGNORE_MISSING
            );
            if ($created === false || (int)$created > $cutoff) {
                $result['skipped_recent']++;
                continue;
            }

            $userid = (int)$candidate['userid'];
            if ($this->has_business_activity($userid)) {
                $result['skipped_activity']++;
                continue;
            }

            try {
                $user = $this->database->get_record(
                    'user',
                    ['id' => $userid, 'deleted' => 0],
                    '*',
                    MUST_EXIST
                );

                $transaction = $this->database->start_delegated_transaction();

                // Guest sessions contain only checkout-resume state at this point.
                $this->database->delete_records('local_subs_commerce_guest', ['userid' => $userid]);

                if (!delete_user($user)) {
                    throw new \RuntimeException('Moodle refused to delete provisional checkout user #' . $userid);
                }

                $transaction->allow_commit();
                $result['deleted_users']++;
            } catch (\Throwable $exception) {
                $result['failed']++;
                mtrace('[Guest checkout cleanup] ERROR user=' . $userid . ' ' . $exception->getMessage());
            }
        }

        return $result;
    }

    private function has_business_activity(int $userid): bool {
        if ($this->database->record_exists('user_enrolments', ['userid' => $userid])) {
            return true;
        }
        if ($this->database->record_exists('local_subs_commerce_grant', ['beneficiaryuserid' => $userid])) {
            return true;
        }
        if ($this->database->record_exists('local_subs_commerce_dig_access', ['userid' => $userid])) {
            return true;
        }
        if ($this->database->record_exists('local_subscriptions_commerce_purchase', ['userid' => $userid])) {
            return true;
        }
        if ($this->database->record_exists('local_subs_commerce_offer', ['customerid' => $userid])) {
            return true;
        }

        return false;
    }
}
