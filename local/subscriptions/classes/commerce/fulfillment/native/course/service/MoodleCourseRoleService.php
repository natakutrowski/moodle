<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\fulfillment\native\course\service;

defined('MOODLE_INTERNAL') || die();

/** Native Moodle course-context role implementation. */
final class MoodleCourseRoleService implements CommerceCourseRoleService {
    public function apply(int $userid, int $courseid, string $roleshortname, bool $dryrun): array {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/lib/accesslib.php');

        $roleid = (int) $DB->get_field('role', 'id', ['shortname' => $roleshortname], IGNORE_MISSING);
        if ($roleid <= 0) {
            throw new \coding_exception('Native course role does not exist: ' . $roleshortname);
        }

        $context = \context_course::instance($courseid);
        $remove = match ($roleshortname) {
            'trialstudent' => [],
            'grammarstudent' => ['trialstudent'],
            'student' => ['trialstudent', 'grammarstudent'],
            default => ['trialstudent'],
        };

        if ($roleshortname === 'trialstudent') {
            foreach (['student', 'grammarstudent'] as $higherrole) {
                $higherroleid = (int) $DB->get_field('role', 'id', ['shortname' => $higherrole], IGNORE_MISSING);
                if ($higherroleid > 0 && user_has_role_assignment($userid, $higherroleid, $context->id)) {
                    return ['status' => 'skipped_higher_role', 'roleid' => $roleid, 'removed' => []];
                }
            }
        }

        $removed = [];
        foreach ($remove as $shortname) {
            $removeid = (int) $DB->get_field('role', 'id', ['shortname' => $shortname], IGNORE_MISSING);
            if ($removeid > 0 && user_has_role_assignment($userid, $removeid, $context->id)) {
                $removed[] = $shortname;
                if (!$dryrun) {
                    role_unassign($removeid, $userid, $context->id);
                }
            }
        }

        $alreadyassigned = user_has_role_assignment($userid, $roleid, $context->id);
        if (!$dryrun && !$alreadyassigned) {
            role_assign($roleid, $userid, $context->id);
        }

        return [
            'status' => $alreadyassigned ? 'unchanged' : ($dryrun ? 'would_assign' : 'assigned'),
            'roleid' => $roleid,
            'roleshortname' => $roleshortname,
            'removed' => $removed,
        ];
    }
}
