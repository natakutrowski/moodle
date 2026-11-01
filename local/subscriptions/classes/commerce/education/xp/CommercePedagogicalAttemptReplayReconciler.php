<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\xp;

defined('MOODLE_INTERNAL') || die();

/**
 * Restore promotion-only XP for completed re-attempts which cannot trigger a
 * second Moodle completion or a personal Level Up reward.
 *
 * This is deliberately a separate, idempotent reconciliation path. It does
 * not change block_xp, the Moodle completion state, or personal XP logs.
 */
final class CommercePedagogicalAttemptReplayReconciler {
    public function __construct(private readonly \moodle_database $db) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        return new self($db ?? $DB);
    }

    /**
     * Reconcile a real personal completion reward against NEW
     * completed attempts for active members of explicitly configured contests.
     *
     * @return array{examined:int,created:int,existing:int,skipped:int}
     */
    public function run(?int $now = null): array {
        $now ??= time();
        $results = ['examined' => 0, 'created' => 0, 'existing' => 0, 'skipped' => 0];

        // Do not scan unrelated personal rewards or other courses. Personal
        // logs are needed to establish the actual value AND matched XP rule.
        $sql = "SELECT l.id, l.userid, l.points, l.ruleid, l.subtype,
                       l.timerecorded, pj.promotionid, pj.timecreated AS joinedat,
                       pp.xpstartsat, pp.courseid, cm.id AS cmid,
                       cm.instance AS instanceid, m.name AS modulename
                  FROM {local_subs_commerce_ped_promo} pp
                  JOIN {local_subs_commerce_ped_join} pj
                    ON pj.promotionid = pp.id AND pj.state = :joinstate
                  JOIN {local_subs_commerce_ped_access} pa
                    ON pa.userid = pj.userid AND pa.courseid = pj.courseid
                   AND pa.promotionid = pj.promotionid
                  JOIN {block_xp_logs} l ON l.userid = pj.userid
                  JOIN {context} ctx ON ctx.id = l.envid
                   AND ctx.contextlevel = :modulecontext
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid
                   AND cm.course = pp.courseid
                  JOIN {modules} m ON m.id = cm.module
                 WHERE pp.xpstartsat IS NOT NULL
                   AND pp.xpstartsat <= :now
                   AND (pp.endsat IS NULL OR pp.endsat >= :nowend)
                   AND pp.status IN ('scheduled', 'open', 'full', 'started')
                   AND l.points > 0 AND l.ruleid > 0
                   AND l.reason = :reason
                   AND l.timerecorded <= :lastrewardat
                   AND m.name IN ('h5pactivity', 'minilesson', 'readaloud', 'solo')
              ORDER BY l.id DESC";
        $rs = $this->db->get_recordset_sql($sql, [
            'joinstate' => 'active', 'modulecontext' => CONTEXT_MODULE,
            'now' => $now, 'nowend' => $now,
            'reason' => 'activity_completion', 'lastrewardat' => $now,
        ]);
        $seen = [];
        $gate = new CommercePedagogicalActivityXpGate($this->db);
        $xp = CommercePedagogicalXpService::create($this->db);

        try {
            foreach ($rs as $row) {
                $key = implode(':', [
                    $row->promotionid, $row->userid, $row->cmid,
                    $row->ruleid, $row->subtype ?? '',
                ]);
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $results['examined']++;
                $earliest = max((int)$row->joinedat, (int)$row->xpstartsat);
                $completedat = $this->completed_replay_time(
                    (string)$row->modulename, (int)$row->instanceid,
                    (int)$row->cmid, (int)$row->userid,
                    (int)$row->courseid, $earliest, $now
                );
                if ($completedat === null) {
                    $results['skipped']++;
                    continue;
                }
                $contextid = (int)\context_module::instance((int)$row->cmid)->id;
                if ($gate->eligible_cmid(
                    (int)$row->promotionid, (int)$row->courseid,
                    $contextid, $completedat
                ) === null) {
                    $results['skipped']++;
                    continue;
                }
                // Same identity as both existing (positive and suppressed)
                // XP bridges, so no double reward even if callbacks arrive
                // before or after this scheduled reconciliation.
                $sourcekey = CommercePedagogicalActivityXpGate::source_key(
                    (int)$row->cmid, (int)$row->ruleid,
                    'activity_completion', $row->subtype !== null ? (string)$row->subtype : null
                );
                $result = $xp->record_once(
                    (int)$row->promotionid, (int)$row->userid,
                    'block_xp', 'eligible_activity', $sourcekey,
                    (int)$row->points, $completedat, $now
                );
                $results[$result->was_created() ? 'created' : 'existing']++;
            }
        } finally {
            $rs->close();
        }
        return $results;
    }

    /**
     * Return the timestamp of a NEW completed attempt, or null. Never use a
     * view or xAPI statement as evidence of completion by itself.
     */
    public function completed_replay_time(
        string $modulename,
        int $instanceid,
        int $cmid,
        int $userid,
        int $courseid,
        int $since,
        int $now
    ): ?int {
        if ($instanceid <= 0 || $cmid <= 0 || $userid <= 0 || $since > $now) {
            return null;
        }
        if ($modulename === 'h5pactivity') {
            // Persisted H5P completion is authoritative; an individual H5P
            // play emits many statement_received events. Do NOT count those.
            $completedat = $this->db->get_field_sql(
                "SELECT MAX(a.timemodified)
                   FROM {h5pactivity_attempts} a
                  WHERE a.h5pactivityid = :instanceid AND a.userid = :userid
                    AND a.completion = 1
                    AND a.timecreated >= :sincecreated
                    AND a.timemodified >= :sincecompleted
                    AND a.timemodified <= :now",
                [
                    'instanceid' => $instanceid, 'userid' => $userid,
                    'sincecreated' => $since, 'sincecompleted' => $since, 'now' => $now,
                ]
            );
            return $completedat ? (int)$completedat : null;
        }

        // These three CampusFR modules emit submission / auto-grading events
        // for actual attempts. Mere page views and completion flag re-reads
        // are not evidence of a new attempt.
        $eventnames = match ($modulename) {
            'minilesson' => ['\\mod_minilesson\\event\\attempt_submitted'],
            'readaloud' => ['\\mod_readaloud\\event\\attempt_submitted'],
            'solo' => [
                '\\mod_solo\\event\\attempt_autograded',
                '\\mod_solo\\event\\attempt_submitted',
            ],
            default => [],
        };
        if (!$eventnames) {
            return null;
        }
        [$insql, $inparams] = $this->db->get_in_or_equal($eventnames, SQL_PARAMS_NAMED, 'cfreplayevent');
        $completedat = $this->db->get_field_sql(
            "SELECT MAX(l.timecreated)
               FROM {logstore_standard_log} l
              WHERE l.userid = :userid AND l.courseid = :courseid
                AND l.contextlevel = :contextlevel
                AND l.contextinstanceid = :cmid
                AND l.timecreated >= :since AND l.timecreated <= :now
                AND l.eventname {$insql}",
            [
                'userid' => $userid, 'courseid' => $courseid,
                'contextlevel' => CONTEXT_MODULE, 'cmid' => $cmid,
                'since' => $since, 'now' => $now,
            ] + $inparams
        );
        return $completedat ? (int)$completedat : null;
    }
}
