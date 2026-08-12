<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\customer\merge\CommerceCustomerLearningMergeService;

/** @covers \local_subscriptions\commerce\customer\merge\CommerceCustomerLearningMergeService */
final class commerce_customer_merge_m75b_learning_conflict_resolution_test extends advanced_testcase {
    public function test_activity_conflict_requires_and_honours_per_item_choice(): void {
        global $DB;
        $this->resetAfterTest(true);

        $target = $this->getDataGenerator()->create_user();
        $source = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $page = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_MANUAL,
        ]);
        $cm = get_coursemodule_from_instance('page', $page->id, $course->id, false, MUST_EXIST);
        $now = time();

        $DB->insert_record('course_modules_completion', (object)[
            'coursemoduleid' => $cm->id, 'userid' => $target->id,
            'completionstate' => COMPLETION_COMPLETE_PASS, 'viewed' => 1, 'timemodified' => $now,
        ]);
        $DB->insert_record('course_modules_completion', (object)[
            'coursemoduleid' => $cm->id, 'userid' => $source->id,
            'completionstate' => COMPLETION_COMPLETE_FAIL, 'viewed' => 1, 'timemodified' => $now + 1,
        ]);

        $service = new CommerceCustomerLearningMergeService($DB);
        $conflicts = $service->conflicts((int)$source->id, (int)$target->id);
        self::assertCount(1, $conflicts);
        self::assertSame('activity_completion', $conflicts[0]['type']);

        $service->merge((int)$source->id, (int)$target->id, [$conflicts[0]['id'] => 'target']);
        $final = $DB->get_record('course_modules_completion', [
            'coursemoduleid' => $cm->id, 'userid' => $target->id,
        ], '*', MUST_EXIST);
        self::assertSame(COMPLETION_COMPLETE_PASS, (int)$final->completionstate);
        self::assertFalse($DB->record_exists('course_modules_completion', [
            'coursemoduleid' => $cm->id, 'userid' => $source->id,
        ]));
    }
}
