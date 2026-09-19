<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\customer\course\CommerceCustomerCourseLearningStatusService;
use local_subscriptions\commerce\education\access\CommerceCourseAccessConfigurationRepository;
use local_subscriptions\commerce\education\access\CommerceCourseAccessMode;

final class commerce_797m63_customer_course_learning_status_test extends advanced_testcase {
    public function test_progressive_status_keeps_future_course_content_in_progress_denominator(): void {
        global $CFG, $DB;
        $this->resetAfterTest(true);
        require_once($CFG->libdir . '/completionlib.php');
        require_once($CFG->dirroot . '/course/lib.php');

        $now = time();
        $course = $this->getDataGenerator()->create_course([
            'fullname' => 'M6.3 Progressive',
            'enablecompletion' => 1,
            'format' => 'topics',
            'numsections' => 2,
        ]);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user((int)$user->id, (int)$course->id, 'student');

        $page1 = $this->getDataGenerator()->create_module('page', [
            'course' => (int)$course->id,
            'section' => 1,
            'name' => 'Lesson one activity',
            'completion' => COMPLETION_TRACKING_MANUAL,
        ]);
        $page2 = $this->getDataGenerator()->create_module('page', [
            'course' => (int)$course->id,
            'section' => 2,
            'name' => 'Lesson two activity',
            'completion' => COMPLETION_TRACKING_MANUAL,
        ]);

        $sections = $DB->get_records('course_sections', ['course' => (int)$course->id], 'section ASC');
        $bysection = [];
        foreach ($sections as $section) {
            $bysection[(int)$section->section] = $section;
        }
        $DB->set_field('course_sections', 'name', 'Nice — Lesson 1', ['id' => (int)$bysection[1]->id]);
        $DB->set_field('course_sections', 'name', 'Cannes — Lesson 2', ['id' => (int)$bysection[2]->id]);
        // Hide lesson 2 from this student until later. M6.3 progress must still
        // count its completion-enabled activity in the full-course denominator.
        $availability = json_encode((object)[
            'op' => '&',
            'c' => [(object)['type' => 'date', 'd' => '>=', 't' => $now + DAYSECS]],
            'showc' => [true],
        ]);
        $DB->set_field('course_sections', 'availability', $availability, ['id' => (int)$bysection[2]->id]);
        rebuild_course_cache((int)$course->id, true);

        $cm1 = get_coursemodule_from_instance('page', (int)$page1->id, (int)$course->id, false, MUST_EXIST);
        $completion = new \completion_info(get_course((int)$course->id));
        $completion->update_state($cm1, COMPLETION_COMPLETE, (int)$user->id);

        CommerceCourseAccessConfigurationRepository::create($DB)->set_mode(
            (int)$course->id,
            CommerceCourseAccessMode::PROMOTION
        );
        $promotionid = (int)$DB->insert_record('local_subs_commerce_ped_promo', (object)[
            'promotionkey' => 'm63-progressive',
            'name' => 'M6.3 October cohort',
            'courseid' => (int)$course->id,
            'status' => 'started',
            'published' => 1,
            'salesopensat' => null,
            'salesclosesat' => null,
            'startsat' => $now - HOURSECS,
            'endsat' => null,
            'capacitytotal' => 6,
            'createdby' => null,
            'modifiedby' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $DB->insert_record('local_subs_commerce_ped_access', (object)[
            'courseid' => (int)$course->id,
            'userid' => (int)$user->id,
            'promotionid' => $promotionid,
            'profile' => 'promotion_progressive',
            'createdby' => null,
            'modifiedby' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $DB->insert_record('local_subs_commerce_ped_cal', (object)[
            'promotionid' => $promotionid,
            'itemtype' => 'course_section',
            'itemid' => (int)$bysection[1]->id,
            'position' => 1,
            'unlocksat' => $now - HOURSECS,
            'createdby' => null,
            'modifiedby' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $DB->insert_record('local_subs_commerce_ped_cal', (object)[
            'promotionid' => $promotionid,
            'itemtype' => 'course_section',
            'itemid' => (int)$bysection[2]->id,
            'position' => 2,
            'unlocksat' => $now + DAYSECS,
            'createdby' => null,
            'modifiedby' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $status = CommerceCustomerCourseLearningStatusService::create($DB)->resolve(
            (int)$user->id,
            (int)$course->id,
            $now
        );

        self::assertTrue($status['haspromotion']);
        self::assertTrue($status['isprogressive']);
        self::assertFalse($status['hasfullaccess']);
        self::assertSame('M6.3 October cohort', $status['promotionname']);
        self::assertSame($now - HOURSECS, $status['promotionstartsat']);
        self::assertTrue($status['hasnextlesson']);
        self::assertSame('Cannes — Lesson 2', $status['nextsectionname']);
        self::assertSame($now + DAYSECS, $status['nextunlocksat']);
        self::assertSame(1, $status['completedactivities']);
        self::assertSame(2, $status['totalactivities']);
        self::assertEqualsWithDelta(50.0, (float)$status['progress'], 0.01);
        self::assertFalse($status['completed']);

        // Sanity check: the future section is indeed not user-visible. The old
        // M6 implementation would therefore have incorrectly reported 100%.
        $modinfo = get_fast_modinfo((int)$course->id, (int)$user->id);
        $cm2 = $modinfo->get_cm((int)get_coursemodule_from_instance('page', (int)$page2->id, (int)$course->id)->id);
        self::assertFalse($cm2->uservisible);
    }

    public function test_lifetime_owner_keeps_full_access_while_promotion_context_remains_visible(): void {
        global $DB;
        $this->resetAfterTest(true);

        $now = time();
        $course = $this->getDataGenerator()->create_course(['numsections' => 1]);
        $user = $this->getDataGenerator()->create_user();
        CommerceCourseAccessConfigurationRepository::create($DB)->set_mode(
            (int)$course->id,
            CommerceCourseAccessMode::PROMOTION
        );
        $section = $DB->get_record('course_sections', ['course' => (int)$course->id, 'section' => 1], '*', MUST_EXIST);
        $DB->set_field('course_sections', 'name', 'Antibes — Lesson', ['id' => (int)$section->id]);
        $promotionid = (int)$DB->insert_record('local_subs_commerce_ped_promo', (object)[
            'promotionkey' => 'm63-owner',
            'name' => 'M6.3 Owner cohort',
            'courseid' => (int)$course->id,
            'status' => 'scheduled',
            'published' => 1,
            'salesopensat' => null,
            'salesclosesat' => null,
            'startsat' => $now + DAYSECS,
            'endsat' => null,
            'capacitytotal' => 6,
            'createdby' => null,
            'modifiedby' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $DB->insert_record('local_subs_commerce_ped_access', (object)[
            'courseid' => (int)$course->id,
            'userid' => (int)$user->id,
            'promotionid' => $promotionid,
            'profile' => 'lifetime_full',
            'createdby' => null,
            'modifiedby' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $DB->insert_record('local_subs_commerce_ped_cal', (object)[
            'promotionid' => $promotionid,
            'itemtype' => 'course_section',
            'itemid' => (int)$section->id,
            'position' => 1,
            'unlocksat' => $now + DAYSECS,
            'createdby' => null,
            'modifiedby' => null,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $status = CommerceCustomerCourseLearningStatusService::create($DB)->resolve(
            (int)$user->id,
            (int)$course->id,
            $now
        );

        self::assertTrue($status['hasfullaccess']);
        self::assertFalse($status['isprogressive']);
        self::assertTrue($status['haspromotion']);
        self::assertSame('M6.3 Owner cohort', $status['promotionname']);
        self::assertTrue($status['hasnextlesson']);
        self::assertSame('Antibes — Lesson', $status['nextsectionname']);
    }

    public function test_mon_campus_template_exposes_learning_metadata(): void {
        global $CFG;
        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/customer/hub.mustache');
        self::assertIsString($template);
        self::assertStringContainsString('commerce-customer-hub__course-learning', $template);
        self::assertStringContainsString('{{#learningmeta}}', $template);
    }
}
