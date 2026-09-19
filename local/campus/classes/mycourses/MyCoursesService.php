<?php

declare(strict_types=1);

namespace local_campus\mycourses;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\course\library\CommerceCourseAccessEnrichmentService;
use local_subscriptions\commerce\customer\course\CommerceCustomerCourseLearningStatusService;

/** Builds the My courses read model from real Moodle enrolments. */
final class MyCoursesService {
    public function __construct(private readonly \moodle_database $db) {
    }

    public function get_for_current_user(): MyCoursesCollection {
        global $CFG, $USER;

        require_once($CFG->libdir . '/completionlib.php');

        $courses = enrol_get_my_courses('*', 'fullname ASC');
        unset($courses[SITEID]);
        if ($courses === []) {
            return new MyCoursesCollection([]);
        }

        $trialcourses = $this->load_trial_course_map((int)$USER->id);
        $commerce = (new CommerceCourseAccessEnrichmentService($this->db))->get_for_customer(
            (int)$USER->id,
            (string)$USER->email,
            array_map('intval', array_keys($courses))
        );

        $learning = CommerceCustomerCourseLearningStatusService::create($this->db);
        $now = time();
        $items = [];
        foreach ($courses as $course) {
            $courseid = (int)$course->id;
            try {
                $status = $learning->resolve((int)$USER->id, $courseid, $now);
            } catch (\Throwable) {
                $status = [];
            }

            $progress = isset($status['progress']) && $status['progress'] !== null
                ? (float)$status['progress']
                : null;
            $done = isset($status['completedactivities']) && $status['completedactivities'] !== null
                ? (int)$status['completedactivities']
                : null;
            $total = isset($status['totalactivities']) && $status['totalactivities'] !== null
                ? (int)$status['totalactivities']
                : null;
            $completed = !empty($status['completed']);

            // Legacy fallback: courses without completion tracking used a mere
            // course_viewed event as a proxy for completion. That cannot be used
            // for progressive promotions: opening the first released lesson must
            // never make the whole course look 100% complete.
            if ($progress === null && empty($status['isprogressive'])
                    && function_exists('local_campus_user_has_visited_course')
                    && local_campus_user_has_visited_course((int)$USER->id, $courseid)) {
                $progress = 100.0;
                $completed = true;
            }

            $items[] = new MyCoursePresentation(
                $course,
                $progress,
                $done,
                $total,
                $completed,
                isset($trialcourses[$courseid]),
                $commerce->get($courseid),
                $status
            );
        }

        return new MyCoursesCollection($items);
    }

    /** @return array<int, bool> */
    private function load_trial_course_map(int $userid): array {
        $roleid = (int)$this->db->get_field('role', 'id', ['shortname' => 'trialstudent'], IGNORE_MISSING);
        if ($roleid <= 0) {
            return [];
        }
        $records = $this->db->get_records_sql(
            "SELECT ctx.instanceid AS courseid
               FROM {role_assignments} ra
               JOIN {context} ctx ON ctx.id = ra.contextid
              WHERE ra.userid = :userid
                AND ra.roleid = :roleid
                AND ctx.contextlevel = :courselevel",
            ['userid' => $userid, 'roleid' => $roleid, 'courselevel' => CONTEXT_COURSE]
        );
        $result = [];
        foreach ($records as $record) {
            $result[(int)$record->courseid] = true;
        }
        return $result;
    }


}
