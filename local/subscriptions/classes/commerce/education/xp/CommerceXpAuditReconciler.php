<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\xp;

defined('MOODLE_INTERNAL') || die();

/**
 * Observes candidate missing XP rewards without ever touching reward balances,
 * Level Up logs, promotion ledgers, Moodle completion or grade data.
 *
 * First observational scope: persisted successful H5P attempts. A successful
 * replay is not by itself proof of a new personal reward entitlement.
 */
final class CommerceXpAuditReconciler {
    public const START_AT = 1794143594; // 2026-11-08 14:13:14 Europe/Paris, B1 deletion.
    public const MAX_PAIRS = 800;
    public const SETTLE_SECONDS = 900;
    public const SLICE_SECONDS = 6 * HOURSECS;
    public const OVERLAP_SECONDS = 10 * MINSECS;

    public function __construct(private readonly \moodle_database $db) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        return new self($db ?? $DB);
    }

    /**
     * Select uniquely matched supported Level Up completion rule; never infer a
     * nominal reward when multiple rules share the highest applicable priority.
     * This matches the badge presentation's precedence and ambiguity guard.
     *
     * @param object[] $rules Effective rules, filtered for the course world.
     */
    public static function matched_rule(array $rules, int $cmid, string $cmname): ?object {
        $choices = [];
        foreach ($rules as $rule) {
            if (!isset($rule->id, $rule->type, $rule->filter, $rule->points) ||
                    (int)$rule->points <= 0 || (string)$rule->type !== 'cm_completion') {
                continue;
            }
            // Reuse the exact supported filter predicates of the learner badge.
            if (CommercePedagogicalCompactPointsBadgeService::nominal_points([$rule], $cmid, $cmname)
                    !== (int)$rule->points) {
                continue;
            }
            $priority = match ((string)$rule->filter) {
                'cm' => 9000,
                'cmname' => 2000,
                'anycm' => 9,
                default => 0,
            };
            if ($priority) {
                $choices[$priority][] = $rule;
            }
        }
        if (!$choices) {
            return null;
        }
        $best = $choices[max(array_keys($choices))];
        return count($best) === 1 ? $best[0] : null;
    }

    /** @return array{examined:int,candidates:int,new:int,seen:int,resolved:int,skipped:int,partial:int} */
    public function run(?int $now = null): array {
        $now ??= time();
        $stats = ['examined' => 0, 'candidates' => 0, 'new' => 0, 'seen' => 0,
            'resolved' => 0, 'skipped' => 0, 'partial' => 0];
        // Persistent forward cursor covers the B1 gap in small bounded slices;
        // an interruption does not make us skip events. Small overlap handles
        // timestamp-boundary races, fingerprints prevent duplicate findings.
        $cursor = (int)get_config('local_subscriptions', 'xp_audit_cursor');
        $cursor = $cursor > 0 ? max(self::START_AT, $cursor) : self::START_AT;
        $since = max(self::START_AT, $cursor - self::OVERLAP_SECONDS);
        $until = min($now - self::SETTLE_SECONDS, $cursor + self::SLICE_SECONDS);
        if ($until <= $since) {
            return $stats;
        }

        $runid = $this->db->insert_record('local_subs_xp_audit_run', (object)[
            'startedat' => $now, 'finishedat' => null,
            'fromtime' => $since, 'totime' => $until, 'state' => 'running',
            'examined' => 0, 'candidates' => 0, 'newitems' => 0, 'resolved' => 0,
            'details' => null,
        ]);
        try {
            $params = ['h5pmod' => 'h5pactivity', 'fromtime' => $since,
                'totime' => $until];
            // MAX(id) is a stable row key. Only persisted completed and
            // successful attempts; never xAPI statement_received events.
            $sql = "SELECT MAX(a.id) AS id, a.userid, cm.id AS cmid, cm.course AS courseid,
                           cm.instance AS instanceid, h.name AS cmname,
                           MAX(a.timemodified) AS evidenceat
                      FROM {h5pactivity_attempts} a
                      JOIN {course_modules} cm ON cm.instance = a.h5pactivityid
                      JOIN {modules} m ON m.id = cm.module AND m.name = :h5pmod
                      JOIN {h5pactivity} h ON h.id = cm.instance
                      JOIN {user} u ON u.id = a.userid AND u.deleted = 0 AND u.suspended = 0
                     WHERE a.success = 1 AND a.completion = 1
                       AND a.timemodified >= :fromtime AND a.timemodified <= :totime
                  GROUP BY a.userid, cm.id, cm.course, cm.instance, h.name
                  ORDER BY evidenceat DESC";
            $pairs = $this->db->get_records_sql($sql, $params, 0, self::MAX_PAIRS + 1);
            if (count($pairs) > self::MAX_PAIRS) {
                $stats['partial'] = 1;
                // Explicit partial run: do not mark initial coverage complete.
                $pairs = array_slice($pairs, 0, self::MAX_PAIRS, true);
            }
            $rulecache = [];
            $gate = new CommercePedagogicalActivityXpGate($this->db);
            foreach ($pairs as $pair) {
                $stats['examined']++;
                $courseid = (int)$pair->courseid;
                $cmid = (int)$pair->cmid;
                $userid = (int)$pair->userid;
                if (is_siteadmin($userid)) {
                    // Administrators' test completions are not XP-eligible.
                    $stats['skipped']++;
                    continue;
                }
                $when = (int)$pair->evidenceat;
                if (!isset($rulecache[$courseid])) {
                    $rulecache[$courseid] = $this->effective_rules($courseid);
                }
                $rule = self::matched_rule($rulecache[$courseid], $cmid, (string)$pair->cmname);
                if (!$rule) {
                    $stats['skipped']++;
                    continue;
                }
                $env = \context_module::instance($cmid, IGNORE_MISSING);
                if (!$env) {
                    $stats['skipped']++;
                    continue;
                }
                // Badge proof uses any positive completion reward for this CMID,
                // not a brittle name-derived or amount-only match.
                $personal = $this->db->get_records_sql(
                    "SELECT id, points, ruleid, subtype
                       FROM {block_xp_logs}
                      WHERE userid = :userid AND envid = :envid AND points > 0
                        AND ruleid > 0 AND reason = :reason
                   ORDER BY id DESC",
                    ['userid' => $userid, 'envid' => (int)$env->id,
                        'reason' => 'activity_completion'], 0, 30
                );
                $personalkey = self::fingerprint('personal', $userid, $cmid, (int)$rule->id, 0);
                if (!$personal) {
                    // A replay might be suppressed legitimately; review only.
                    $this->observe($personalkey, 'personal', $userid, $courseid, $cmid,
                        (int)$rule->id, 0, (int)$rule->points, $when,
                        'no_personal_completion_log', $now, $stats);
                } else {
                    $this->resolve($personalkey, $now, $stats);
                }

                $relations = $this->db->get_records_sql(
                    "SELECT p.id, p.xpstartsat, j.timecreated AS joinedat
                       FROM {local_subs_commerce_ped_promo} p
                       JOIN {local_subs_commerce_ped_join} j
                         ON j.promotionid = p.id AND j.courseid = p.courseid
                        AND j.userid = :juser AND j.state = :joinstate
                       JOIN {local_subs_commerce_ped_access} ac
                         ON ac.promotionid = p.id AND ac.courseid = p.courseid
                        AND ac.userid = j.userid
                      WHERE p.courseid = :pcourse AND p.xpstartsat IS NOT NULL
                        AND p.xpstartsat <= :pwhen
                        AND (p.endsat IS NULL OR p.endsat >= :evidenceend)
                        AND p.status IN ('scheduled', 'open', 'full', 'started')",
                    ['juser' => $userid, 'joinstate' => 'active', 'pcourse' => $courseid,
                        'pwhen' => $when, 'evidenceend' => $when]
                );
                foreach ($relations as $promotion) {
                    $promotionid = (int)$promotion->id;
                    $key = self::fingerprint('promotion', $userid, $cmid, (int)$rule->id, $promotionid);
                    if ($when < max((int)$promotion->xpstartsat, (int)$promotion->joinedat) ||
                            $gate->eligible_cmid($promotionid, $courseid, (int)$env->id, $when) === null) {
                        $stats['skipped']++;
                        continue;
                    }
                    $logged = $personal ?: [(object)['ruleid' => $rule->id,
                        'points' => $rule->points, 'subtype' => null]];
                    $found = false;
                    foreach ($logged as $personalrow) {
                        $sourcekey = CommercePedagogicalActivityXpGate::source_key(
                            $cmid, (int)$personalrow->ruleid, 'activity_completion',
                            $personalrow->subtype !== null ? (string)$personalrow->subtype : null
                        );
                        if ($this->db->record_exists('local_subs_commerce_ped_xp', [
                            'promotionid' => $promotionid, 'userid' => $userid,
                            'sourcecomponent' => 'block_xp', 'sourcetype' => 'eligible_activity',
                            'sourcekey' => $sourcekey,
                        ])) {
                            $found = true;
                            break;
                        }
                    }
                    if ($found) {
                        $this->resolve($key, $now, $stats);
                    } else {
                        // May be a legitimate unearned promotion award. A human
                        // reviews; audit mode never calls record_once().
                        $this->observe($key, 'promotion', $userid, $courseid, $cmid,
                            (int)$rule->id, $promotionid,
                            $personal ? (int)reset($personal)->points : (int)$rule->points,
                            $when, 'no_promotion_source_ledger', $now, $stats);
                    }
                }
            }
            // Separate reconciliation of past candidates: a reward can arrive
            // after the original successful H5P event was scanned.
            $this->refresh_open_candidates($now, $stats);
            if (!$stats['partial']) {
                set_config('xp_audit_cursor', (string)$until, 'local_subscriptions');
            }
            $this->db->update_record('local_subs_xp_audit_run', (object)[
                'id' => $runid, 'finishedat' => time(), 'state' => $stats['partial'] ? 'partial' : 'ok',
                'examined' => $stats['examined'], 'candidates' => $stats['candidates'],
                'newitems' => $stats['new'], 'resolved' => $stats['resolved'],
                'details' => $stats['partial'] ? 'Pair limit reached; initial coverage incomplete' : null,
            ]);
        } catch (\Throwable $e) {
            $this->db->update_record('local_subs_xp_audit_run', (object)[
                'id' => $runid, 'finishedat' => time(), 'state' => 'error',
                'details' => substr(get_class($e) . ': ' . $e->getMessage(), 0, 1000),
            ]);
            throw $e;
        }
        return $stats;
    }

    /**
     * Refresh open observations independently of the original H5P attempt.
     * This is an observation only; it never calls Level Up or XP grant APIs.
     * Process oldest unresolved candidates first. The small batch is enough for
     * routine operation, while the next pass picks up remaining candidates.
     *
     * @param array<string,int> $stats
     */
    private function refresh_open_candidates(int $now, array &$stats): void {
        $open = $this->db->get_records('local_subs_xp_audit', ['status' => 'review'],
            'lastseen ASC, id ASC', '*', 0, 150);
        foreach ($open as $candidate) {
            if (is_siteadmin((int)$candidate->userid)) {
                // V4.1 classifies open site-admin findings as excluded.
                continue;
            }
            // Rotate the bounded refresh queue, so older unresolved candidates
            // cannot starve later ones indefinitely.
            $this->db->update_record('local_subs_xp_audit', (object)[
                'id' => $candidate->id, 'lastseen' => $now,
            ]);
            $ctx = \context_module::instance((int)$candidate->cmid, IGNORE_MISSING);
            if (!$ctx) {
                continue;
            }
            $personal = $this->db->get_records_sql(
                "SELECT id, points, ruleid, subtype FROM {block_xp_logs}
                  WHERE userid = :userid AND envid = :envid AND points > 0
                    AND ruleid > 0 AND reason = :reason ORDER BY id DESC",
                ['userid' => (int)$candidate->userid, 'envid' => (int)$ctx->id,
                    'reason' => 'activity_completion'], 0, 30
            );
            if ($candidate->kind === 'personal') {
                if ($personal) {
                    $this->resolve((string)$candidate->fingerprint, $now, $stats);
                }
                continue;
            }
            if ($candidate->kind !== 'promotion') {
                continue;
            }
            // Include the unambiguous nominal rule identity when personal XP
            // was absent, as on Nathalie's November replay. Such a credit may
            // legitimately be entered by a subsequent manual repair.
            if (!$personal) {
                $personal = [(object)['ruleid' => (int)$candidate->ruleid, 'subtype' => null]];
            }
            foreach ($personal as $log) {
                $sourcekey = CommercePedagogicalActivityXpGate::source_key(
                    (int)$candidate->cmid, (int)$log->ruleid,
                    'activity_completion', $log->subtype !== null ? (string)$log->subtype : null
                );
                if ($this->db->record_exists('local_subs_commerce_ped_xp', [
                    'promotionid' => (int)$candidate->promotionid,
                    'userid' => (int)$candidate->userid,
                    'sourcecomponent' => 'block_xp', 'sourcetype' => 'eligible_activity',
                    'sourcekey' => $sourcekey,
                ])) {
                    $this->resolve((string)$candidate->fingerprint, $now, $stats);
                    break;
                }
            }
        }
    }

    /** @return object[] */
    private function effective_rules(int $courseid): array {
        $coursecontext = \context_course::instance($courseid);
        $systemcontextid = (int)\context_system::instance()->id;
        $rules = $this->db->get_records_sql(
            "SELECT id, contextid, childcontextid, points, type, filter, filtercmid,
                    filterint1, filterchar1
               FROM {block_xp_rule}
              WHERE ((contextid = :coursectx AND childcontextid = 0)
                  OR (contextid = :systemctx AND childcontextid IN (0, :syscoursectx)))
                AND type = :ruletype AND points > 0",
            ['coursectx' => (int)$coursecontext->id, 'systemctx' => $systemcontextid,
                'syscoursectx' => (int)$coursecontext->id, 'ruletype' => 'cm_completion']
        );
        $local = array_filter($rules, static fn($r) => (int)$r->contextid === (int)$coursecontext->id);
        $scoped = array_filter($rules, static fn($r) => (int)$r->childcontextid === (int)$coursecontext->id);
        return $local ?: ($scoped ?: $rules);
    }

    public static function fingerprint(string $kind, int $userid, int $cmid, int $ruleid,
            int $promotionid): string {
        return hash('sha256', implode(':', [$kind, $userid, $cmid, $ruleid, $promotionid]));
    }

    /** @param array<string,int> $stats */
    private function observe(string $fingerprint, string $kind, int $userid, int $courseid,
            int $cmid, int $ruleid, int $promotionid, int $points, int $when,
            string $reason, int $now, array &$stats): void {
        $stats['candidates']++;
        $old = $this->db->get_record('local_subs_xp_audit', ['fingerprint' => $fingerprint]);
        if ($old) {
            $this->db->update_record('local_subs_xp_audit', (object)[
                'id' => $old->id,
                'status' => $old->status === 'resolved' ? 'review' : $old->status,
                'lastseen' => $now, 'evidenceat' => $when, 'timemodified' => $now,
            ]);
            $stats['seen']++;
        } else {
            $this->db->insert_record('local_subs_xp_audit', (object)[
                'fingerprint' => $fingerprint, 'kind' => $kind, 'userid' => $userid,
                'courseid' => $courseid, 'cmid' => $cmid, 'ruleid' => $ruleid,
                'promotionid' => $promotionid, 'points' => $points,
                'reason' => $reason, 'status' => 'review', 'evidenceat' => $when,
                'firstseen' => $now, 'lastseen' => $now,
                'resolvedat' => null, 'timemodified' => $now,
            ]);
            $stats['new']++;
        }
    }

    /** @param array<string,int> $stats */
    private function resolve(string $fingerprint, int $now, array &$stats): void {
        $old = $this->db->get_record('local_subs_xp_audit', ['fingerprint' => $fingerprint]);
        if ($old && $old->status === 'review') {
            $this->db->update_record('local_subs_xp_audit', (object)[
                'id' => $old->id, 'status' => 'resolved', 'resolvedat' => $now,
                'lastseen' => $now, 'timemodified' => $now,
            ]);
            $stats['resolved']++;
        }
    }
}
