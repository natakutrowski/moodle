<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\fulfillment\native\course\service;

defined('MOODLE_INTERNAL') || die();

/** Native Moodle course-group membership implementation. */
final class MoodleCourseGroupService implements CommerceCourseGroupService {
    public function apply(int $userid, int $courseid, array $groupids, array $groupnames, bool $dryrun): array {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/group/lib.php');

        $resolved = [];
        foreach ($groupids as $groupid) {
            $group = $DB->get_record('groups', ['id' => (int) $groupid, 'courseid' => $courseid], '*', IGNORE_MISSING);
            if (!$group) {
                throw new \coding_exception('Native course fulfillment group does not exist in the course: ' . (int) $groupid);
            }
            $resolved[(int) $group->id] = $group;
        }
        foreach ($groupnames as $groupname) {
            $group = $DB->get_record('groups', ['courseid' => $courseid, 'name' => $groupname], '*', IGNORE_MISSING);
            if (!$group && !$dryrun) {
                $group = (object) [
                    'courseid' => $courseid,
                    'name' => $groupname,
                    'description' => '',
                    'descriptionformat' => FORMAT_HTML,
                    'timecreated' => time(),
                    'timemodified' => time(),
                ];
                $group->id = groups_create_group($group);
            }
            if (!$group) {
                $group = (object) ['id' => 0, 'courseid' => $courseid, 'name' => $groupname];
            }
            $resolved['name:' . \core_text::strtolower($groupname)] = $group;
        }

        $results = [];
        foreach ($resolved as $group) {
            $exists = (int) $group->id > 0 && groups_is_member((int) $group->id, $userid);
            if (!$dryrun && !$exists) {
                groups_add_member((int) $group->id, $userid);
            }
            $results[] = [
                'groupid' => (int) $group->id,
                'name' => (string) $group->name,
                'status' => $exists ? 'unchanged' : ($dryrun ? 'would_add' : 'added'),
            ];
        }
        return $results;
    }
}
