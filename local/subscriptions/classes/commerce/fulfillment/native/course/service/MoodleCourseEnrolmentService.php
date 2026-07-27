<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\fulfillment\native\course\service;

defined('MOODLE_INTERNAL') || die();

/** Native Moodle manual-enrolment implementation. */
final class MoodleCourseEnrolmentService implements CommerceCourseEnrolmentService {
    public function apply(
        int $userid,
        int $courseid,
        string $roleshortname,
        int $validfrom,
        ?int $validuntil,
        bool $dryrun
    ): array {
        global $DB;

        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], 'id', IGNORE_MISSING);
        if (!$user) {
            throw new \coding_exception('Native course fulfillment beneficiary does not exist.');
        }

        $course = $DB->get_record('course', ['id' => $courseid], '*', IGNORE_MISSING);
        if (!$course) {
            throw new \coding_exception('Native course fulfillment course does not exist.');
        }

        $manual = enrol_get_plugin('manual');
        if (!$manual) {
            throw new \coding_exception('The Moodle manual enrolment plugin is unavailable.');
        }

        $roleid = (int) $DB->get_field('role', 'id', ['shortname' => $roleshortname], IGNORE_MISSING);
        if ($roleid <= 0) {
            throw new \coding_exception('Native course fulfillment role does not exist: ' . $roleshortname);
        }

        $instance = $this->find_instance($courseid);
        $wouldcreateinstance = $instance === null;
        $existing = $instance === null ? null : $DB->get_record('user_enrolments', [
            'enrolid' => $instance->id,
            'userid' => $userid,
        ], '*', IGNORE_MISSING);

        $timeend = $validuntil ?? 0;
        if ($dryrun) {
            return [
                'status' => $existing ? 'would_update' : 'would_create',
                'courseid' => $courseid,
                'userid' => $userid,
                'roleid' => $roleid,
                'timestart' => $validfrom,
                'timeend' => $timeend,
                'wouldcreateinstance' => $wouldcreateinstance,
            ];
        }

        if ($instance === null) {
            $instanceid = $manual->add_instance($course, ['status' => ENROL_INSTANCE_ENABLED]);
            if (!$instanceid) {
                throw new \RuntimeException('Unable to create a Native manual enrolment instance.');
            }
            $instance = $DB->get_record('enrol', ['id' => $instanceid], '*', MUST_EXIST);
        }

        if ($existing) {
            $newstart = (int) ($existing->timestart ?? 0);
            if ($validfrom <= time()) {
                $newstart = $newstart > 0 ? min($newstart, $validfrom) : $validfrom;
            }
            $newend = $timeend === 0 || (int) $existing->timeend === 0
                ? 0
                : max((int) $existing->timeend, $timeend);
            $manual->update_user_enrol($instance, $userid, ENROL_USER_ACTIVE, $newstart, $newend);
            $status = 'updated';
        } else {
            if ($validfrom > time()) {
                return [
                    'status' => 'scheduled',
                    'courseid' => $courseid,
                    'userid' => $userid,
                    'timestart' => $validfrom,
                    'timeend' => $timeend,
                ];
            }
            $manual->enrol_user($instance, $userid, $roleid, $validfrom, $timeend, ENROL_USER_ACTIVE);
            $status = 'created';
        }

        return [
            'status' => $status,
            'courseid' => $courseid,
            'userid' => $userid,
            'enrolid' => (int) $instance->id,
            'roleid' => $roleid,
            'timestart' => $validfrom,
            'timeend' => $timeend,
        ];
    }

    private function find_instance(int $courseid): ?\stdClass {
        foreach (enrol_get_instances($courseid, true) as $instance) {
            if ($instance->enrol === 'manual' && (int) $instance->status === ENROL_INSTANCE_ENABLED) {
                return $instance;
            }
        }
        return null;
    }
}
