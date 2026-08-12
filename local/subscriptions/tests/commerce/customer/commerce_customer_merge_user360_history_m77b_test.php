<?php
declare(strict_types=1);

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\customer\merge\CommerceCustomerMergeHistoryService;

final class local_subscriptions_commerce_customer_merge_user360_history_m77b_test extends advanced_testcase {
    public function test_history_is_visible_from_retained_and_absorbed_accounts(): void {
        global $DB;

        $this->resetAfterTest(true);
        $g = $this->getDataGenerator();
        $target = $g->create_user(['email' => 'retained-history@example.test']);
        $source = $g->create_user(['email' => 'absorbed-history@example.test', 'suspended' => 1]);
        $actor = $g->create_user();

        $mergeid = (int)$DB->insert_record('local_subs_identity_merge', (object)[
            'mergeuuid' => bin2hex(random_bytes(16)),
            'targetuserid' => $target->id,
            'status' => 'completed',
            'planjson' => '{}',
            'resultjson' => json_encode([
                'targetuserid' => (int)$target->id,
                'sourceuserids' => [(int)$source->id],
                'transfers' => ['notes' => 2, 'learning_activitycompletions' => 3],
                'learningresolutions' => ['grade:1' => 'target'],
                'certification' => [
                    'passed' => true,
                    'summary' => ['passed' => 8, 'failed' => 0, 'manualdecisions' => 1],
                ],
            ]),
            'performedby' => $actor->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        $DB->insert_record('local_subs_identity_merge_source', (object)[
            'mergeid' => $mergeid,
            'sourceuserid' => $source->id,
            'sourceemail' => $source->email,
            'wassuspended' => 0,
            'timecreated' => time(),
        ]);

        $service = new CommerceCustomerMergeHistoryService($DB);
        $targethistory = $service->for_user((int)$target->id);
        $sourcehistory = $service->for_user((int)$source->id);

        self::assertCount(1, $targethistory);
        self::assertCount(1, $sourcehistory);
        self::assertTrue($targethistory[0]['isretained']);
        self::assertFalse($sourcehistory[0]['isretained']);
        self::assertTrue($targethistory[0]['certified']);
        self::assertSame(8, $targethistory[0]['certificationchecks']);
        self::assertSame(1, $targethistory[0]['manualdecisions']);
        self::assertSame((int)$target->id, $sourcehistory[0]['targetuserid']);
    }

    public function test_user360_merge_copy_has_no_internal_phase_labels(): void {
        global $CFG;

        foreach (['fr', 'en', 'ru'] as $lang) {
            $source = (string)file_get_contents(
                $CFG->dirroot . '/local/subscriptions/lang/' . $lang . '/local_subscriptions.php'
            );
            preg_match_all(
                '/^\$string\[[\'"]user360_merge_[^\'"]+[\'"]\]\s*=\s*([\'"])(.*?)\1;/ms',
                $source,
                $matches
            );
            self::assertDoesNotMatchRegularExpression(
                '/\bM7(?:\.\d+[A-Z]?)?\b/i',
                implode("\n", $matches[2])
            );
        }
    }
}
