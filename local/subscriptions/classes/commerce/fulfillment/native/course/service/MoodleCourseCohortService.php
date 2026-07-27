<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\fulfillment\native\course\service;

defined('MOODLE_INTERNAL') || die();

/** Native Moodle cohort-membership implementation. */
final class MoodleCourseCohortService implements CommerceCourseCohortService {
    public function apply(int $userid, array $cohortids, array $cohortnames, bool $dryrun): array {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/cohort/lib.php');

        $resolved = [];
        foreach ($cohortids as $cohortid) {
            $cohort = $DB->get_record('cohort', ['id' => (int) $cohortid], 'id,name,idnumber', IGNORE_MISSING);
            if (!$cohort) {
                throw new \coding_exception('Native course fulfillment cohort does not exist: ' . (int) $cohortid);
            }
            $resolved[(int) $cohort->id] = $cohort;
        }
        foreach ($cohortnames as $cohortname) {
            $cohort = $DB->get_record_select(
                'cohort',
                $DB->sql_compare_text('name') . ' = ' . $DB->sql_compare_text(':name'),
                ['name' => $cohortname],
                'id,name,idnumber',
                IGNORE_MISSING
            );
            if (!$cohort) {
                throw new \coding_exception('Native course fulfillment cohort does not exist: ' . $cohortname);
            }
            $resolved[(int) $cohort->id] = $cohort;
        }

        $results = [];
        foreach ($resolved as $cohort) {
            $exists = $DB->record_exists('cohort_members', ['cohortid' => $cohort->id, 'userid' => $userid]);
            if (!$dryrun && !$exists) {
                cohort_add_member((int) $cohort->id, $userid);
            }
            $results[] = [
                'cohortid' => (int) $cohort->id,
                'name' => (string) $cohort->name,
                'status' => $exists ? 'unchanged' : ($dryrun ? 'would_add' : 'added'),
            ];
        }
        return $results;
    }
}
