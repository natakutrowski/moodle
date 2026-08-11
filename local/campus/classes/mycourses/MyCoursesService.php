<?php

declare(strict_types=1);

namespace local_campus\mycourses;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\course\library\CommerceCourseAccessEnrichmentService;

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

        $items = [];
        foreach ($courses as $course) {
            $courseid = (int)$course->id;
            [$progress, $done, $total, $completed] = $this->calculate_progress($course, (int)$USER->id);
            $items[] = new MyCoursePresentation(
                $course,
                $progress,
                $done,
                $total,
                $completed,
                isset($trialcourses[$courseid]),
                $commerce->get($courseid)
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

    /** @return array{0:?float,1:?int,2:?int,3:bool} */
    private function calculate_progress(\stdClass $course, int $userid): array {
        global $CFG;

        $fullcourse = get_course((int)$course->id);
        $completion = new \completion_info($fullcourse);
        $modinfo = get_fast_modinfo($fullcourse, $userid);
        $done = 0;
        $total = 0;

        foreach ($modinfo->get_cms() as $cm) {
            if (!$cm->uservisible || !$completion->is_enabled($cm)) {
                continue;
            }
            $total++;
            $data = $completion->get_data($cm, true, $userid);
            if ((int)($data->completionstate ?? 0) !== 0) {
                $done++;
            }
        }

        if ($total > 0) {
            $progress = max(0.0, min(100.0, 100.0 * ($done / $total)));
            return [$progress, $done, $total, $done >= $total];
        }

        if (function_exists('local_campus_user_has_visited_course')
                && local_campus_user_has_visited_course($userid, (int)$course->id)) {
            return [100.0, null, null, true];
        }

        return [null, null, null, false];
    }
}
