<?php
declare(strict_types=1);

namespace local_subscriptions\commerce\customer\merge;

defined('MOODLE_INTERNAL') || die();

use moodle_database;

/** Verifies the effective database state before an account merge transaction is committed. */
final class CommerceCustomerMergeCertificationService {
    private const OWNERSHIP_REFERENCES = [
        'legacy_subscriptions' => ['user_subscription', 'userid'],
        'legacy_payment_requests' => ['subscription_payment_request', 'userid'],
        'legacy_reminders' => ['subscription_reminder_log', 'userid'],
        'legacy_digital_purchases' => ['subscription_digital_payment_request', 'userid'],
        'native_purchases' => ['local_subscriptions_commerce_purchase', 'userid'],
        'native_grants' => ['local_subs_commerce_grant', 'beneficiaryuserid'],
        'digital_accesses' => ['local_subs_commerce_dig_access', 'beneficiaryuserid'],
        'guest_checkout_sessions' => ['local_subs_commerce_guest', 'userid'],
        'personal_offers' => ['local_subs_commerce_offer', 'beneficiaryuserid'],
        'promotion_uses' => ['local_subs_commerce_promouse', 'userid'],
        'offer_campaign_members' => ['local_subs_commerce_offer_campaign_member', 'userid'],
        'grant_campaign_members' => ['local_subs_commerce_grant_campaign_member', 'userid'],
        'commerce_emails' => ['local_subs_commerce_mail', 'userid'],
        'automation_history' => ['local_subscriptions_automation_history', 'userid'],
        'customer_success_plans' => ['local_subscriptions_cs_plan', 'userid'],
        'work_items' => ['local_subscriptions_work_item', 'targetuserid'],
        'crm_notes' => ['local_subscriptions_user_note', 'userid'],
        'crm_scores' => ['local_subscriptions_crm_score', 'userid'],
        'inbox_contacts' => ['local_subscriptions_inbox_contact', 'matcheduserid'],
    ];

    private const LEARNING_REFERENCES = [
        'enrolments' => ['user_enrolments', 'userid'],
        'group_memberships' => ['groups_members', 'userid'],
        'course_completions' => ['course_completions', 'userid'],
        'completion_criteria' => ['course_completion_crit_compl', 'userid'],
        'activity_completions' => ['course_modules_completion', 'userid'],
        'grades' => ['grade_grades', 'userid'],
        'grade_history' => ['grade_grades_history', 'userid'],
        'quiz_attempts' => ['quiz_attempts', 'userid'],
        'quiz_grades' => ['quiz_grades', 'userid'],
        'h5p_attempts' => ['h5pactivity_attempts', 'userid'],
        'assignment_submissions' => ['assign_submission', 'userid'],
        'assignment_grades' => ['assign_grades', 'userid'],
        'question_steps' => ['question_attempt_steps', 'userid'],
        'course_last_access' => ['user_lastaccess', 'userid'],
        'badges' => ['badge_issued', 'userid'],
        'competencies' => ['competency_usercomp', 'userid'],
        'forum_posts' => ['forum_posts', 'userid'],
        'forum_discussions' => ['forum_discussions', 'userid'],
        'scorm_tracks' => ['scorm_scoes_track', 'userid'],
        'lesson_attempts' => ['lesson_attempts', 'userid'],
        'lesson_grades' => ['lesson_grades', 'userid'],
        'lesson_timer' => ['lesson_timer', 'userid'],
    ];

    public function __construct(private readonly moodle_database $database) {
    }

    public function certify(
        array $sourceuserids,
        int $targetuserid,
        string $targetemail,
        array $premergeconflicts,
        array $learningresolutions
    ): array {
        $checks = [];
        $target = $this->database->get_record('user', ['id' => $targetuserid, 'deleted' => 0]);
        $checks[] = $this->check('primary_account_active',
            $target !== false && (int)$target->suspended === 0, $targetuserid);

        foreach ($sourceuserids as $sourceuserid) {
            $source = $this->database->get_record('user', ['id' => $sourceuserid, 'deleted' => 0]);
            $checks[] = $this->check('merged_account_suspended',
                $source !== false && (int)$source->suspended === 1, $sourceuserid);

            foreach (self::OWNERSHIP_REFERENCES as $name => [$table, $field]) {
                $remaining = $this->count_if_supported($table, $field, $sourceuserid);
                if ($remaining !== null) {
                    $checks[] = $this->check('ownership_transferred', $remaining === 0, $sourceuserid,
                        ['area' => $name, 'remaining' => $remaining]);
                }
            }
            foreach (self::LEARNING_REFERENCES as $name => [$table, $field]) {
                $remaining = $this->count_if_supported($table, $field, $sourceuserid);
                if ($remaining !== null) {
                    $checks[] = $this->check('learning_state_transferred', $remaining === 0, $sourceuserid,
                        ['area' => $name, 'remaining' => $remaining]);
                }
            }
        }

        foreach ($premergeconflicts as $conflict) {
            $choice = $learningresolutions[$conflict['id']] ?? '';
            $expected = $choice === 'source' ? (string)$conflict['sourcevalue'] : (string)$conflict['targetvalue'];
            $actual = $this->resolved_value($conflict, $targetuserid);
            $checks[] = $this->check(
                'manual_learning_decision_applied',
                $actual !== null && $this->same_value($conflict['type'], $actual, $expected),
                (int)$conflict['sourceuserid'],
                [
                    'conflictid' => $conflict['id'],
                    'conflicttype' => $conflict['type'],
                    'itemid' => (int)$conflict['itemid'],
                    'choice' => $choice,
                    'expected' => $expected,
                    'actual' => $actual,
                ]
            );
        }

        foreach ([
            ['local_subs_commerce_grant', 'beneficiaryuserid'],
            ['local_subs_commerce_dig_access', 'beneficiaryuserid'],
            ['local_subs_commerce_offer', 'beneficiaryuserid'],
        ] as [$table, $userfield]) {
            if (!$this->has_field($table, $userfield) || !$this->has_field($table, 'beneficiaryemail')) {
                continue;
            }
            $mismatch = (int)$this->database->count_records_select(
                $table,
                $userfield . ' = :userid AND beneficiaryemail <> :email',
                ['userid' => $targetuserid, 'email' => $targetemail]
            );
            $checks[] = $this->check('customer_email_aligned', $mismatch === 0, $targetuserid,
                ['table' => $table, 'mismatches' => $mismatch]);
        }

        $failed = count(array_filter($checks, static fn(array $check): bool => !$check['passed']));
        return [
            'passed' => $failed === 0,
            'checks' => $checks,
            'summary' => [
                'total' => count($checks),
                'passed' => count($checks) - $failed,
                'failed' => $failed,
                'manualdecisions' => count($premergeconflicts),
            ],
        ];
    }

    private function check(string $type, bool $passed, int $userid, array $details = []): array {
        return ['type' => $type, 'passed' => $passed, 'userid' => $userid, 'details' => $details];
    }

    private function resolved_value(array $conflict, int $targetuserid): ?string {
        if ($conflict['type'] === 'activity_completion') {
            $record = $this->database->get_record('course_modules_completion', [
                'userid' => $targetuserid, 'coursemoduleid' => (int)$conflict['itemid'],
            ]);
            return $record ? (string)(int)$record->completionstate : null;
        }
        if ($conflict['type'] === 'grade') {
            $record = $this->database->get_record('grade_grades', [
                'userid' => $targetuserid, 'itemid' => (int)$conflict['itemid'],
            ]);
            return $record && $record->finalgrade !== null ? (string)$record->finalgrade : null;
        }
        return null;
    }

    private function same_value(string $type, string $actual, string $expected): bool {
        return $type === 'grade'
            ? abs((float)$actual - (float)$expected) < 0.000001
            : $actual === $expected;
    }

    private function count_if_supported(string $table, string $field, int $userid): ?int {
        return $this->has_field($table, $field)
            ? (int)$this->database->count_records($table, [$field => $userid])
            : null;
    }

    private function has_field(string $table, string $field): bool {
        $manager = $this->database->get_manager();
        $xmldbtable = new \xmldb_table($table);
        return $manager->table_exists($xmldbtable)
            && $manager->field_exists($xmldbtable, new \xmldb_field($field));
    }
}
