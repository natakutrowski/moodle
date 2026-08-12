<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\merge;

defined('MOODLE_INTERNAL') || die();

use moodle_database;

/**
 * Executes only the identity transfers certified safe by M4.2D.
 *
 * Moodle pedagogical history is never rewritten. A source account containing
 * pedagogical history, or unsupported Legacy subscription ownership, is a hard
 * blocker and cannot be suspended by this service.
 */
final class CommerceCustomerMergeExecutionService {
    private const AUDIT = 'local_subs_identity_merge';
    private const AUDIT_SOURCE = 'local_subs_identity_merge_source';

    public const BLOCK_PEDAGOGICAL_HISTORY = 'pedagogical_history';
    public const BLOCK_LEGACY_SUBSCRIPTION = 'legacy_subscription';
    public const BLOCK_ALREADY_MERGED = 'already_merged';
    public const BLOCK_SUSPENDED_TARGET = 'suspended_target';

    public function __construct(
        private readonly moodle_database $database,
        private readonly CommerceCustomerMergePlanner $planner
    ) {
    }

    /**
     * @return array<int,array{type:string,userid:int,count:int}>
     */
    public function blockers(CommerceCustomerMergePlan $plan): array {
        $blockers = [];

        if ((int)$plan->target_profile()->user->suspended === 1) {
            $blockers[] = [
                'type' => self::BLOCK_SUSPENDED_TARGET,
                'userid' => $plan->targetuserid,
                'count' => 1,
            ];
        }

        foreach ($plan->source_profiles() as $profile) {
            $userid = $profile->userid();

            if ($profile->has_pedagogical_history()) {
                $blockers[] = [
                    'type' => self::BLOCK_PEDAGOGICAL_HISTORY,
                    'userid' => $userid,
                    'count' =>
                        $profile->enrolledcourses
                        + $profile->completedactivities
                        + $profile->gradecount,
                ];
            }

            $legacy = $this->database->count_records('user_subscription', [
                'userid' => $userid,
            ]) + $this->database->count_records('subscription_payment_request', [
                'userid' => $userid,
            ]);
            if ($legacy > 0) {
                $blockers[] = [
                    'type' => self::BLOCK_LEGACY_SUBSCRIPTION,
                    'userid' => $userid,
                    'count' => $legacy,
                ];
            }

            if ($this->database->record_exists(self::AUDIT_SOURCE, [
                'sourceuserid' => $userid,
            ])) {
                $blockers[] = [
                    'type' => self::BLOCK_ALREADY_MERGED,
                    'userid' => $userid,
                    'count' => 1,
                ];
            }
        }

        return $blockers;
    }

    public function execute(
        array $userids,
        int $targetuserid,
        int $actoruserid
    ): CommerceCustomerMergeExecutionResult {
        global $CFG;

        require_once($CFG->dirroot . '/user/lib.php');

        // Rebuild from current DB state at the exact moment of execution.
        $plan = $this->planner->build($userids, $targetuserid);
        $blockers = $this->blockers($plan);
        if ($blockers !== []) {
            throw new \moodle_exception(
                'commerce_identity_merge_execution_blocked',
                'local_subscriptions'
            );
        }

        $target = $plan->target_profile()->user;
        $now = time();
        $transaction = $this->database->start_delegated_transaction();

        $transfers = [
            'purchases' => 0,
            'grants' => 0,
            'digitalaccesses' => 0,
            'guestsessions' => 0,
            'legacydigital' => 0,
            'offers' => 0,
            'promouses' => 0,
            'notes' => 0,
            'tags' => 0,
            'tagsdeduplicated' => 0,
            'crmscores' => 0,
            'inboxcontacts' => 0,
            'suspendedaccounts' => 0,
        ];

        foreach ($plan->source_profiles() as $profile) {
            $sourceuserid = $profile->userid();

            $transfers['purchases'] += $this->move_simple(
                'local_subscriptions_commerce_purchase',
                'userid',
                $sourceuserid,
                $targetuserid,
                true
            );
            $transfers['grants'] += $this->move_simple(
                'local_subs_commerce_grant',
                'beneficiaryuserid',
                $sourceuserid,
                $targetuserid,
                true
            );
            $transfers['digitalaccesses'] += $this->move_simple(
                'local_subs_commerce_dig_access',
                'beneficiaryuserid',
                $sourceuserid,
                $targetuserid,
                true
            );
            $transfers['guestsessions'] += $this->move_simple(
                'local_subs_commerce_guest',
                'userid',
                $sourceuserid,
                $targetuserid,
                true
            );
            $transfers['legacydigital'] += $this->move_simple(
                'subscription_digital_payment_request',
                'userid',
                $sourceuserid,
                $targetuserid,
                false,
                'last_update'
            );
            $transfers['offers'] += $this->move_simple(
                'local_subs_commerce_offer',
                'beneficiaryuserid',
                $sourceuserid,
                $targetuserid,
                true
            );
            $transfers['promouses'] += $this->move_simple(
                'local_subs_commerce_promouse',
                'userid',
                $sourceuserid,
                $targetuserid,
                true
            );
            $transfers['notes'] += $this->move_simple(
                'local_subscriptions_user_note',
                'userid',
                $sourceuserid,
                $targetuserid
            );
            $transfers['crmscores'] += $this->move_simple(
                'local_subscriptions_crm_score',
                'userid',
                $sourceuserid,
                $targetuserid
            );
            $transfers['inboxcontacts'] += $this->move_simple(
                'local_subscriptions_inbox_contact',
                'matcheduserid',
                $sourceuserid,
                $targetuserid
            );

            [$movedtags, $deduplicatedtags] = $this->move_tags(
                $sourceuserid,
                $targetuserid
            );
            $transfers['tags'] += $movedtags;
            $transfers['tagsdeduplicated'] += $deduplicatedtags;

            $user = $this->database->get_record(
                'user',
                ['id' => $sourceuserid, 'deleted' => 0],
                '*',
                MUST_EXIST
            );
            if ((int)$user->suspended === 0) {
                $user->suspended = 1;
                $user->timemodified = $now;
                // Same core API already used by CampusFR CRM suspension.
                user_update_user($user, false, false);
                $transfers['suspendedaccounts']++;
            }
        }

        $mergeuuid = bin2hex(random_bytes(16));
        $planpayload = $this->plan_payload($plan);
        $resultpayload = [
            'targetuserid' => $targetuserid,
            'sourceuserids' => array_map(
                static fn(CommerceCustomerMergeAccountProfile $p): int => $p->userid(),
                $plan->source_profiles()
            ),
            'transfers' => $transfers,
        ];

        $mergeid = (int)$this->database->insert_record(self::AUDIT, (object)[
            'mergeuuid' => $mergeuuid,
            'targetuserid' => $targetuserid,
            'status' => 'completed',
            'planjson' => json_encode(
                $planpayload,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ),
            'resultjson' => json_encode(
                $resultpayload,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ),
            'performedby' => $actoruserid,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        foreach ($plan->source_profiles() as $profile) {
            $this->database->insert_record(self::AUDIT_SOURCE, (object)[
                'mergeid' => $mergeid,
                'sourceuserid' => $profile->userid(),
                'sourceemail' => (string)$profile->user->email,
                'wassuspended' => (int)$profile->user->suspended,
                'timecreated' => $now,
            ]);
        }

        $transaction->allow_commit();

        return new CommerceCustomerMergeExecutionResult(
            $mergeid,
            $mergeuuid,
            $targetuserid,
            $resultpayload['sourceuserids'],
            $transfers
        );
    }

    private function move_simple(
        string $table,
        string $field,
        int $sourceuserid,
        int $targetuserid,
        bool $hasstandardtimemodified = false,
        ?string $customtimefield = null
    ): int {
        $count = (int)$this->database->count_records($table, [
            $field => $sourceuserid,
        ]);
        if ($count === 0) {
            return 0;
        }

        $sets = [$field . ' = :targetuserid'];
        $params = [
            'targetuserid' => $targetuserid,
            'sourceuserid' => $sourceuserid,
        ];
        if ($hasstandardtimemodified) {
            $sets[] = 'timemodified = :now';
            $params['now'] = time();
        } elseif ($customtimefield !== null) {
            $sets[] = $customtimefield . ' = :now';
            $params['now'] = time();
        }

        $this->database->execute(
            'UPDATE {' . $table . '}
                SET ' . implode(', ', $sets) . '
              WHERE ' . $field . ' = :sourceuserid',
            $params
        );

        return $count;
    }

    /**
     * @return array{0:int,1:int}
     */
    private function move_tags(int $sourceuserid, int $targetuserid): array {
        $tags = $this->database->get_records(
            'local_subscriptions_user_tag',
            ['userid' => $sourceuserid]
        );
        $moved = 0;
        $deduplicated = 0;

        foreach ($tags as $tag) {
            if ($this->database->record_exists(
                'local_subscriptions_user_tag',
                ['userid' => $targetuserid, 'tag' => (string)$tag->tag]
            )) {
                $this->database->delete_records(
                    'local_subscriptions_user_tag',
                    ['id' => (int)$tag->id]
                );
                $deduplicated++;
                continue;
            }

            $tag->userid = $targetuserid;
            $this->database->update_record(
                'local_subscriptions_user_tag',
                $tag
            );
            $moved++;
        }

        return [$moved, $deduplicated];
    }

    private function plan_payload(CommerceCustomerMergePlan $plan): array {
        return [
            'recommendedtargetuserid' => $plan->recommendedtargetuserid,
            'targetuserid' => $plan->targetuserid,
            'sharedcoursecount' => $plan->sharedcoursecount,
            'warnings' => $plan->warnings,
            'profiles' => array_map(
                static fn(CommerceCustomerMergeAccountProfile $p): array => [
                    'userid' => $p->userid(),
                    'email' => (string)$p->user->email,
                    'pedagogicalscore' => $p->pedagogical_score(),
                    'commercescore' => $p->commerce_score(),
                    'enrolledcourses' => $p->enrolledcourses,
                    'completedcourses' => $p->completedcourses,
                    'completedactivities' => $p->completedactivities,
                    'gradecount' => $p->gradecount,
                    'averagegradepercent' => $p->averagegradepercent,
                ],
                $plan->profiles
            ),
        ];
    }
}
