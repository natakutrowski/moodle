<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\customer\merge\CommerceCustomerCourseAccessMergeService;
use local_subscriptions\commerce\customer\merge\CommerceCustomerLearningMergeService;

final class commerce_customer_merge_course_access_m13e_test extends advanced_testcase {
    public function test_paid_student_beats_trial_and_keeps_paid_interval(): void {
        global $DB;

        $this->resetAfterTest(true);
        [$course, $source, $target, $enrolid, $studentid, $trialid] = $this->fixture();

        $paidstart = 1700000000;
        $paidend = 1800000000;
        $trialstart = 1750000000;
        $trialend = 1750600000;

        $this->enrol($enrolid, (int)$target->id, $paidstart, $paidend);
        $this->enrol($enrolid, (int)$source->id, $trialstart, $trialend);
        $context = \context_course::instance((int)$course->id);
        role_assign($studentid, (int)$target->id, $context->id, 'enrol_manual', $enrolid);
        role_assign($trialid, (int)$source->id, $context->id, 'enrol_manual', $enrolid);

        $plan = (new CommerceCustomerCourseAccessMergeService($DB))
            ->plan((int)$source->id, (int)$target->id);
        $this->assertArrayHasKey((int)$course->id, $plan);
        $this->assertSame('student', $plan[(int)$course->id]['finalrole']);
        $this->assertSame('target', $plan[(int)$course->id]['winner']);
        $this->assertSame($paidstart, (int)$plan[(int)$course->id]['timestart']);
        $this->assertSame($paidend, (int)$plan[(int)$course->id]['timeend']);

        (new CommerceCustomerLearningMergeService($DB))->merge((int)$source->id, (int)$target->id);

        $ue = $DB->get_record('user_enrolments', [
            'userid' => (int)$target->id,
            'enrolid' => $enrolid,
        ], '*', MUST_EXIST);
        $this->assertSame($paidstart, (int)$ue->timestart);
        $this->assertSame($paidend, (int)$ue->timeend);
        $this->assertTrue(user_has_role_assignment((int)$target->id, $studentid, $context->id));
        $this->assertFalse(user_has_role_assignment((int)$target->id, $trialid, $context->id));
        $this->assertFalse($DB->record_exists('role_assignments', [
            'userid' => (int)$target->id,
            'roleid' => $trialid,
            'contextid' => $context->id,
        ]));
    }

    public function test_source_paid_student_beats_target_trial_and_keeps_source_interval(): void {
        global $DB;

        $this->resetAfterTest(true);
        [$course, $source, $target, $enrolid, $studentid, $trialid] = $this->fixture();

        $paidstart = 1680000000;
        $paidend = 1880000000;
        $trialstart = 1760000000;
        $trialend = 1760600000;

        $this->enrol($enrolid, (int)$source->id, $paidstart, $paidend);
        $this->enrol($enrolid, (int)$target->id, $trialstart, $trialend);
        $context = \context_course::instance((int)$course->id);
        role_assign($studentid, (int)$source->id, $context->id, 'enrol_manual', $enrolid);
        role_assign($trialid, (int)$target->id, $context->id, 'enrol_manual', $enrolid);

        $plan = (new CommerceCustomerCourseAccessMergeService($DB))
            ->plan((int)$source->id, (int)$target->id);
        $this->assertArrayHasKey((int)$course->id, $plan);
        $this->assertSame('student', $plan[(int)$course->id]['finalrole']);
        $this->assertSame('source', $plan[(int)$course->id]['winner']);
        $this->assertSame($paidstart, (int)$plan[(int)$course->id]['timestart']);
        $this->assertSame($paidend, (int)$plan[(int)$course->id]['timeend']);

        (new CommerceCustomerLearningMergeService($DB))->merge((int)$source->id, (int)$target->id);

        $ue = $DB->get_record('user_enrolments', [
            'userid' => (int)$target->id,
            'enrolid' => $enrolid,
        ], '*', MUST_EXIST);
        $this->assertSame($paidstart, (int)$ue->timestart);
        $this->assertSame($paidend, (int)$ue->timeend);
        $this->assertTrue(user_has_role_assignment((int)$target->id, $studentid, $context->id));
        $this->assertFalse(user_has_role_assignment((int)$target->id, $trialid, $context->id));
        $this->assertFalse($DB->record_exists('role_assignments', [
            'userid' => (int)$target->id,
            'roleid' => $trialid,
            'contextid' => $context->id,
        ]));
    }

    public function test_equal_student_accesses_take_union_of_intervals(): void {
        global $DB;

        $this->resetAfterTest(true);
        [$course, $source, $target, $enrolid, $studentid] = $this->fixture();

        $this->enrol($enrolid, (int)$target->id, 1700000000, 1750000000);
        $this->enrol($enrolid, (int)$source->id, 1680000000, 1800000000);
        $context = \context_course::instance((int)$course->id);
        role_assign($studentid, (int)$target->id, $context->id, 'enrol_manual', $enrolid);
        role_assign($studentid, (int)$source->id, $context->id, 'enrol_manual', $enrolid);

        (new CommerceCustomerLearningMergeService($DB))->merge((int)$source->id, (int)$target->id);

        $ue = $DB->get_record('user_enrolments', [
            'userid' => (int)$target->id,
            'enrolid' => $enrolid,
        ], '*', MUST_EXIST);
        $this->assertSame(1680000000, (int)$ue->timestart);
        $this->assertSame(1800000000, (int)$ue->timeend);
        $this->assertTrue(user_has_role_assignment((int)$target->id, $studentid, $context->id));
    }

    public function test_equal_student_access_with_unlimited_end_remains_unlimited(): void {
        global $DB;

        $this->resetAfterTest(true);
        [$course, $source, $target, $enrolid, $studentid] = $this->fixture();

        $this->enrol($enrolid, (int)$target->id, 1700000000, 1750000000);
        $this->enrol($enrolid, (int)$source->id, 1690000000, 0);
        $context = \context_course::instance((int)$course->id);
        role_assign($studentid, (int)$target->id, $context->id, 'enrol_manual', $enrolid);
        role_assign($studentid, (int)$source->id, $context->id, 'enrol_manual', $enrolid);

        (new CommerceCustomerLearningMergeService($DB))->merge((int)$source->id, (int)$target->id);

        $ue = $DB->get_record('user_enrolments', [
            'userid' => (int)$target->id,
            'enrolid' => $enrolid,
        ], '*', MUST_EXIST);
        $this->assertSame(1690000000, (int)$ue->timestart);
        $this->assertSame(0, (int)$ue->timeend);
    }

    public function test_paid_and_trial_access_are_resolved_across_different_enrol_plugins(): void {
        global $DB;

        $this->resetAfterTest(true);
        [$course, $source, $target, $manualenrolid, $studentid, $trialid] = $this->fixture();

        $otherenrolid = (int)$DB->insert_record('enrol', (object)[
            'enrol' => 'self',
            'status' => ENROL_INSTANCE_ENABLED,
            'courseid' => (int)$course->id,
            'sortorder' => 10,
            'name' => 'Merge test alternate enrol',
            'enrolperiod' => 0,
            'enrolstartdate' => 0,
            'enrolenddate' => 0,
            'expirynotify' => 0,
            'expirythreshold' => 0,
            'notifyall' => 0,
            'password' => '',
            'cost' => null,
            'currency' => null,
            'roleid' => 0,
            'customint1' => null,
            'customint2' => null,
            'customint3' => null,
            'customint4' => null,
            'customint5' => null,
            'customint6' => null,
            'customint7' => null,
            'customint8' => null,
            'customchar1' => null,
            'customchar2' => null,
            'customchar3' => null,
            'customdec1' => null,
            'customdec2' => null,
            'customtext1' => null,
            'customtext2' => null,
            'customtext3' => null,
            'customtext4' => null,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $paidstart = 1680000000;
        $paidend = 1880000000;
        $trialstart = 1760000000;
        $trialend = 1760600000;

        $this->enrol($manualenrolid, (int)$target->id, $paidstart, $paidend);
        $this->enrol($otherenrolid, (int)$source->id, $trialstart, $trialend);

        $context = \context_course::instance((int)$course->id);
        role_assign($studentid, (int)$target->id, $context->id, 'enrol_manual', 0);
        role_assign($trialid, (int)$source->id, $context->id, 'enrol_self', 0);

        $plan = (new CommerceCustomerCourseAccessMergeService($DB))
            ->plan((int)$source->id, (int)$target->id);
        $this->assertArrayHasKey((int)$course->id, $plan);
        $this->assertSame('student', $plan[(int)$course->id]['finalrole']);
        $this->assertSame('target', $plan[(int)$course->id]['winner']);
        $this->assertSame($paidstart, (int)$plan[(int)$course->id]['timestart']);
        $this->assertSame($paidend, (int)$plan[(int)$course->id]['timeend']);

        (new CommerceCustomerLearningMergeService($DB))->merge((int)$source->id, (int)$target->id);

        $this->assertTrue(user_has_role_assignment((int)$target->id, $studentid, $context->id));
        $this->assertFalse($DB->record_exists('role_assignments', [
            'userid' => (int)$target->id,
            'roleid' => $trialid,
            'contextid' => $context->id,
        ]));

        $targetenrolments = $DB->get_records('user_enrolments', ['userid' => (int)$target->id]);
        $this->assertNotEmpty($targetenrolments);
        foreach ($targetenrolments as $ue) {
            $instancecourse = (int)$DB->get_field('enrol', 'courseid', ['id' => (int)$ue->enrolid], MUST_EXIST);
            if ($instancecourse !== (int)$course->id) {
                continue;
            }
            $this->assertSame($paidstart, (int)$ue->timestart);
            $this->assertSame($paidend, (int)$ue->timeend);
        }
    }

    private function fixture(): array {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $source = $this->getDataGenerator()->create_user();
        $target = $this->getDataGenerator()->create_user();

        // create_course() already provisions the standard manual enrol instance.
        // Reuse it instead of attempting to create a duplicate instance, which may
        // return 0 and produce orphan user_enrolments in the test fixture.
        $enrolid = (int)$DB->get_field(
            'enrol',
            'id',
            [
                'courseid' => (int)$course->id,
                'enrol' => 'manual',
            ],
            MUST_EXIST
        );

        $studentid = (int)$DB->get_field('role', 'id', ['shortname' => 'student'], MUST_EXIST);
        $trialid = (int)$DB->get_field('role', 'id', ['shortname' => 'trialstudent'], IGNORE_MISSING);
        if ($trialid <= 0) {
            $trialid = create_role('Trial student', 'trialstudent', 'Trial student');
        }

        return [$course, $source, $target, $enrolid, $studentid, $trialid];
    }

    private function enrol(int $enrolid, int $userid, int $start, int $end): void {
        global $DB;

        $DB->insert_record('user_enrolments', (object)[
            'status' => ENROL_USER_ACTIVE,
            'enrolid' => $enrolid,
            'userid' => $userid,
            'timestart' => $start,
            'timeend' => $end,
            'modifierid' => 0,
            'timecreated' => $start,
            'timemodified' => $start,
        ]);
    }
}
