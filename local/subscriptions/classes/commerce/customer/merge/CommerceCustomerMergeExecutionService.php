<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\merge;

defined('MOODLE_INTERNAL') || die();

use moodle_database;

/**
 * Executes and certifies a CampusFR customer account merge.
 *
 * Learning state, commercial ownership and CRM references are consolidated in one
 * transaction. Mandatory post-merge integrity checks must pass before commit.
 */
final class CommerceCustomerMergeExecutionService {
    private const AUDIT = 'local_subs_identity_merge';
    private const AUDIT_SOURCE = 'local_subs_identity_merge_source';

    public const BLOCK_PEDAGOGICAL_HISTORY = 'pedagogical_history';
    public const BLOCK_LEGACY_SUBSCRIPTION = 'legacy_subscription';
    public const BLOCK_ALREADY_MERGED = 'already_merged';
    public const BLOCK_SUSPENDED_TARGET = 'suspended_target';
    public const BLOCK_PRIVILEGED_ACCOUNT = CommerceCustomerLearningMergeService::BLOCK_PRIVILEGED_ACCOUNT;

    public function __construct(
        private readonly moodle_database $database,
        private readonly CommerceCustomerMergePlanner $planner
    ) {
    }

    /**
     * @return array<int,array{type:string,userid:int,count:int}>
     */
    public function blockers(CommerceCustomerMergePlan $plan, array $learningresolutions = []): array {
        $blockers = [];

        if ((int)$plan->target_profile()->user->suspended === 1) {
            $blockers[] = [
                'type' => self::BLOCK_SUSPENDED_TARGET,
                'userid' => $plan->targetuserid,
                'count' => 1,
            ];
        }

        $learning = new CommerceCustomerLearningMergeService($this->database);
        $seen = [];
        foreach ($plan->source_profiles() as $profile) {
            $userid = $profile->userid();

            foreach ($learning->blockers($userid, $plan->targetuserid) as $blocker) {
                $key = $blocker['type'] . ':' . $blocker['userid'];
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $blockers[] = $blocker;
                }
            }
            foreach ($learning->conflicts($userid, $plan->targetuserid) as $conflict) {
                if (!in_array($learningresolutions[$conflict['id']] ?? '', ['source', 'target'], true)) {
                    $blockers[] = [
                        'type' => CommerceCustomerLearningMergeService::BLOCK_UNRESOLVED_CONFLICT,
                        'userid' => $userid,
                        'count' => 1,
                        'conflictid' => $conflict['id'],
                    ];
                }
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
        int $actoruserid,
        array $learningresolutions = [],
        ?int $preferredidentityuserid = null,
        ?int $preferredpassworduserid = null
    ): CommerceCustomerMergeExecutionResult {
        global $CFG;

        require_once($CFG->dirroot . '/user/lib.php');

        // Rebuild from current DB state at the exact moment of execution.
        $plan = $this->planner->build($userids, $targetuserid);
        $blockers = $this->blockers($plan, $learningresolutions);
        if ($blockers !== []) {
            throw new \moodle_exception(
                'commerce_identity_merge_execution_blocked',
                'local_subscriptions'
            );
        }

        $target = $plan->target_profile()->user;
        $sourceuserids = array_map(
            static fn(CommerceCustomerMergeAccountProfile $profile): int => $profile->userid(),
            $plan->source_profiles()
        );
        $learningservice = new CommerceCustomerLearningMergeService($this->database);
        $premergeconflicts = [];
        foreach ($sourceuserids as $sourceuserid) {
            foreach ($learningservice->conflicts($sourceuserid, $targetuserid) as $conflict) {
                $premergeconflicts[] = $conflict;
            }
        }

        if ($preferredidentityuserid !== null && $preferredidentityuserid !== $targetuserid
            && !in_array($preferredidentityuserid, $sourceuserids, true)) {
            throw new \moodle_exception('commerce_identity_merge_preferred_identity_invalid', 'local_subscriptions');
        }

        // Password choice is deliberately independent from email/username. By default the
        // retained Moodle account keeps its current password (historical M13 behaviour).
        $preferredpassworduserid ??= $targetuserid;
        $allowedpassworduserids = [$targetuserid];
        if ($preferredidentityuserid !== null && $preferredidentityuserid !== $targetuserid) {
            $allowedpassworduserids[] = $preferredidentityuserid;
        }
        if (!in_array($preferredpassworduserid, $allowedpassworduserids, true)) {
            throw new \moodle_exception('commerce_identity_merge_preferred_password_invalid', 'local_subscriptions');
        }

        $now = time();
        $transaction = $this->database->start_delegated_transaction();

        $identitytransfer = null;
        if ($preferredidentityuserid !== null && $preferredidentityuserid !== $targetuserid) {
            $identitytransfer = (new CommerceCustomerPreferredIdentityTransferService($this->database))->transfer(
                $targetuserid,
                $preferredidentityuserid,
                $preferredpassworduserid
            );
            // Downstream Legacy consolidation and certification must use the final identity.
            $target = $this->database->get_record('user', ['id' => $targetuserid], '*', MUST_EXIST);
        }

        $transfers = [
            'purchases' => 0,
            'grants' => 0,
            'digitalaccesses' => 0,
            'guestsessions' => 0,
            'legacydigital' => 0,
            'legacysubscriptions' => 0,
            'legacypaymentrequests' => 0,
            'legacyreminders' => 0,
            'offers' => 0,
            'promouses' => 0,
            'offercampaignmembers' => 0,
            'grantcampaignmembers' => 0,
            'commerceemails' => 0,
            'automationhistory' => 0,
            'csplans' => 0,
            'worktargets' => 0,
            'notes' => 0,
            'tags' => 0,
            'tagsdeduplicated' => 0,
            'crmscores' => 0,
            'inboxcontacts' => 0,
            'suspendedaccounts' => 0,
        ];

        $legacyservice = new CommerceCustomerLegacyConsolidationService($this->database);

        foreach ($plan->source_profiles() as $profile) {
            $sourceuserid = $profile->userid();

            foreach ($learningservice->merge($sourceuserid, $targetuserid, $learningresolutions) as $key => $count) {
                $transferkey = 'learning_' . $key;
                $transfers[$transferkey] = ($transfers[$transferkey] ?? 0) + $count;
            }

            foreach ($legacyservice->merge(
                $sourceuserid,
                $targetuserid,
                (string)$target->email
            ) as $key => $count) {
                $transfers[$key] = ($transfers[$key] ?? 0) + $count;
            }
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

        $certification = (new CommerceCustomerMergeCertificationService($this->database))->certify(
            $sourceuserids,
            $targetuserid,
            (string)$target->email,
            $premergeconflicts,
            $learningresolutions
        );
        if (!$certification['passed']) {
            throw new \moodle_exception('commerce_identity_merge_certification_failed', 'local_subscriptions');
        }

        $mergeuuid = bin2hex(random_bytes(16));
        $planpayload = $this->plan_payload($plan);
        $resultpayload = [
            'targetuserid' => $targetuserid,
            'sourceuserids' => $sourceuserids,
            'transfers' => $transfers,
            'learningresolutions' => $learningresolutions,
            'preferredidentityuserid' => $preferredidentityuserid,
            'preferredpassworduserid' => $preferredpassworduserid,
            'identitytransfer' => $identitytransfer,
            'certification' => $certification,
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
            $transfers,
            $certification
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
