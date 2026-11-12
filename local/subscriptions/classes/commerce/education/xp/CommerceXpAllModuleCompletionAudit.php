<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\xp;

defined('MOODLE_INTERNAL') || die();

/**
 * V4 observational scanner: completion-based awards for every Moodle activity.
 *
 * It NEVER writes block_xp, block_xp_logs or the promotion XP ledger. It does
 * write candidate observations, run history and its own forward cursor.
 * An achievement with a currently matched rule does NOT establish historical
 * entitlement: all new findings are review-only, including promotions.
 *
 * Trigger types other than cm_completion must be inventoried separately;
 * currently none of the positive rules use them in CampusFR production.
 */
final class CommerceXpAllModuleCompletionAudit {
    // 2026-11-02 09:00 CET: cohort XP start. Forward scans use a separate cursor.
    public const START_AT = 1793606400;
    public const MAX_ROWS = 600;
    public const SETTLE_SECONDS = 900;
    public const SLICE_SECONDS = 6 * HOURSECS;

    public function __construct(private readonly \moodle_database $db) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        return new self($db ?? $DB);
    }

    /**
     * SQL paging invariant: ascending (completion.timemodified, completion.id)
     * and cursor persisted as that pair. No offset paging, no skipped rows on
     * bounded batches. No cursor advance on exceptions.
     *
     * @return array<string,int|string>
     */
    public function run(?int $now = null): array {
        $now ??= time();
        $stats = ['examined' => 0, 'candidates' => 0, 'new' => 0,
            'seen' => 0, 'resolved' => 0, 'skipped' => 0, 'unsupportedrules' => 0,
            'more' => 0, 'excludedadmins' => 0, 'status' => 'ok'];
        $lockfactory = \core\lock\lock_config::get_lock_factory('local_subscriptions_ped_xp');
        $lock = $lockfactory->get_lock('xp-v4-completion-audit', 1);
        if (!$lock) {
            $stats['status'] = 'locked';
            return $stats;
        }
        try {
            // Close prior V4 findings for site admins without deleting the audit trail.
            $stats['excludedadmins'] = $this->exclude_site_admin_cases($now);
            $ts = (int)get_config('local_subscriptions', 'xp_v4_completion_cursor');
            $ts = $ts > 0 ? max(self::START_AT, $ts) : self::START_AT;
            $lastid = max(0, (int)get_config('local_subscriptions', 'xp_v4_completion_lastid'));
            $until = min($now - self::SETTLE_SECONDS, $ts + self::SLICE_SECONDS);
            if ($until < $ts || ($until === $ts && $lastid === 0)) {
                $stats['status'] = 'caught_up';
                return $stats;
            }
            $stats['unsupportedrules'] = $this->db->count_records_select('block_xp_rule',
                'points > :zero AND type <> :supported',
                ['zero' => 0, 'supported' => 'cm_completion']);
            $params = ['fromtime' => $ts, 'totime' => $until, 'lastid' => $lastid];
            $sql = "SELECT cc.id, cc.userid, cc.coursemoduleid AS cmid,
                           cc.completionstate, cc.timemodified, cc.overrideby,
                           cm.course AS courseid, cm.instance,
                           m.name AS modname
                      FROM {course_modules_completion} cc
                      JOIN {course_modules} cm ON cm.id = cc.coursemoduleid
                          AND cm.completion > 0
                      JOIN {modules} m ON m.id = cm.module
                      JOIN {user} u ON u.id = cc.userid
                          AND u.deleted = 0 AND u.suspended = 0
                     WHERE cc.completionstate IN (1, 2)
                       AND cc.timemodified >= :fromtime
                       AND cc.timemodified <= :totime
                       AND (cc.timemodified > :cursorat
                         OR (cc.timemodified = :cursorequal
                             AND cc.id > :lastid))
                  ORDER BY cc.timemodified ASC, cc.id ASC";
            $params['cursorat'] = $ts;
            $params['cursorequal'] = $ts;
            $rows = array_values($this->db->get_records_sql($sql, $params, 0, self::MAX_ROWS + 1));
            $hasmore = count($rows) > self::MAX_ROWS;
            if ($hasmore) {
                $rows = array_slice($rows, 0, self::MAX_ROWS);
            }
            $rulecache = [];
            $namecache = [];
            $promocache = [];
            $gate = new CommercePedagogicalActivityXpGate($this->db);
            $runid = $this->db->insert_record('local_subs_xp_audit_run', (object)[
                'startedat' => $now, 'finishedat' => null,
                'fromtime' => $ts, 'totime' => $until, 'state' => 'running',
                'examined' => 0, 'candidates' => 0, 'newitems' => 0, 'resolved' => 0,
                'details' => 'source=v4_all_modules',
            ]);
            try {
                foreach ($rows as $cc) {
                    $stats['examined']++;
                    if ($cc->overrideby !== null && (int)$cc->overrideby > 0) {
                        $stats['skipped']++;
                        continue;
                    }
                    $cmid = (int)$cc->cmid;
                    $courseid = (int)$cc->courseid;
                    $userid = (int)$cc->userid;
                    // CampusFR XP policy: site administrators do not earn learner XP.
                    // Never infer an entitlement from their test completions.
                    if (is_siteadmin($userid)) {
                        $stats['skipped']++;
                        continue;
                    }
                    $modname = (string)$cc->modname;
                    if (!preg_match('/^[a-z][a-z0-9_]*$/D', $modname)) {
                        $stats['skipped']++;
                        continue;
                    }
                    if (!array_key_exists($cmid, $namecache)) {
                        try {
                            $name = $this->db->get_field($modname, 'name',
                                ['id' => (int)$cc->instance], IGNORE_MISSING);
                            $namecache[$cmid] = is_string($name) ? $name : '';
                        } catch (\Throwable) {
                            $namecache[$cmid] = '';
                        }
                    }
                    if ($namecache[$cmid] === '') {
                        $stats['skipped']++;
                        continue;
                    }
                    if (!isset($rulecache[$courseid])) {
                        $rulecache[$courseid] = $this->effective_rules($courseid);
                    }
                    $rule = CommerceXpAuditReconciler::matched_rule(
                        $rulecache[$courseid], $cmid, $namecache[$cmid]);
                    if (!$rule) {
                        $stats['skipped']++;
                        continue;
                    }
                    $context = \context_module::instance($cmid, IGNORE_MISSING);
                    if (!$context) {
                        $stats['skipped']++;
                        continue;
                    }
                    $personal = array_values($this->db->get_records_sql(
                        "SELECT id, ruleid, points, subtype, timerecorded
                           FROM {block_xp_logs}
                          WHERE userid = :userid AND envid = :envid
                            AND reason = :reason AND ruleid > 0 AND points > 0
                       ORDER BY id DESC",
                        ['userid' => $userid, 'envid' => (int)$context->id,
                            'reason' => 'activity_completion'], 0, 31));
                    $personalkey = CommerceXpAuditReconciler::fingerprint('personal',
                        $userid, $cmid, (int)$rule->id, 0);
                    if (!$personal) {
                        $this->observe($personalkey, 'personal', $cc, (int)$rule->id,
                            0, (int)$rule->points, 'v4_completion_review_only', $now, $stats);
                    } else {
                        $this->resolve($personalkey, $now, $stats);
                    }
                    // Do not infer promotion entitlement from completion alone.
                    // A promotion-only replay has separate evidence and is NOT
                    // covered by this scan. Require actual positive Level Up log.
                    if (!$personal || count($personal) > 30) {
                        continue;
                    }
                    $pkey = $userid . ':' . $courseid;
                    if (!isset($promocache[$pkey])) {
                        $promocache[$pkey] = array_values($this->db->get_records_sql(
                            "SELECT p.id,p.xpstartsat,p.endsat,j.timecreated AS joinedat
                               FROM {local_subs_commerce_ped_promo} p
                               JOIN {local_subs_commerce_ped_join} j
                                 ON j.promotionid=p.id AND j.courseid=p.courseid
                                AND j.userid=:userid AND j.state=:state
                               JOIN {local_subs_commerce_ped_access} a
                                 ON a.promotionid=p.id AND a.courseid=p.courseid
                                AND a.userid=j.userid
                              WHERE p.courseid=:courseid AND p.xpstartsat IS NOT NULL
                                AND p.status IN ('scheduled','open','full','started')",
                            ['userid' => $userid, 'state' => 'active',
                                'courseid' => $courseid]));
                    }
                    foreach ($promocache[$pkey] as $promo) {
                        $foundeligible = false;
                        $foundledger = false;
                        $matching = null;
                        foreach ($personal as $log) {
                            $earnedat = (int)$log->timerecorded;
                            if ($earnedat < max((int)$promo->xpstartsat, (int)$promo->joinedat) ||
                                    ($promo->endsat !== null && $earnedat > (int)$promo->endsat)) {
                                continue;
                            }
                            try {
                                if ($gate->eligible_cmid((int)$promo->id, $courseid,
                                        (int)$context->id, $earnedat) !== $cmid) {
                                    continue;
                                }
                            } catch (\Throwable) {
                                continue;
                            }
                            $foundeligible = true;
                            $sourcekey = CommercePedagogicalActivityXpGate::source_key(
                                $cmid, (int)$log->ruleid, 'activity_completion',
                                $log->subtype !== null ? (string)$log->subtype : null);
                            if ($this->db->record_exists('local_subs_commerce_ped_xp', [
                                    'promotionid' => (int)$promo->id,
                                    'userid' => $userid, 'sourcecomponent' => 'block_xp',
                                    'sourcetype' => 'eligible_activity', 'sourcekey' => $sourcekey])) {
                                $foundledger = true;
                                break;
                            }
                            $matching = $log;
                        }
                        $promokey = CommerceXpAuditReconciler::fingerprint('promotion',
                            $userid, $cmid, (int)$rule->id, (int)$promo->id);
                        if ($foundledger) {
                            $this->resolve($promokey, $now, $stats);
                        } elseif ($foundeligible && $matching !== null) {
                            $this->observe($promokey, 'promotion', $cc, (int)$rule->id,
                                (int)$promo->id, (int)$matching->points,
                                'v4_promotion_review_only', $now, $stats);
                        }
                    }
                }
                // Persist pair cursor only after successful processing of batch.
                // On partial batches, continue at the last exact (time,id).
                if ($hasmore && $rows) {
                    $lastrow = end($rows);
                    set_config('xp_v4_completion_cursor', (string)$lastrow->timemodified,
                        'local_subscriptions');
                    set_config('xp_v4_completion_lastid', (string)$lastrow->id,
                        'local_subscriptions');
                    $stats['more'] = 1;
                } else {
                    set_config('xp_v4_completion_cursor', (string)$until, 'local_subscriptions');
                    set_config('xp_v4_completion_lastid', '0', 'local_subscriptions');
                }
                $this->db->update_record('local_subs_xp_audit_run', (object)[
                    'id' => $runid, 'finishedat' => time(), 'state' => 'ok',
                    'examined' => $stats['examined'], 'candidates' => $stats['candidates'],
                    'newitems' => $stats['new'], 'resolved' => $stats['resolved'],
                    'details' => 'source=v4_all_modules; rows_more=' . (int)$hasmore .
                        '; skipped=' . $stats['skipped'] .
                        '; excluded_site_admin_findings=' . $stats['excludedadmins'] .
                        '; unsupported_positive_rules=' . $stats['unsupportedrules'],
                ]);
            } catch (\Throwable $e) {
                $this->db->update_record('local_subs_xp_audit_run', (object)[
                    'id' => $runid, 'finishedat' => time(), 'state' => 'error',
                    'details' => substr(get_class($e) . ': ' . $e->getMessage(), 0, 1000),
                ]);
                throw $e;
            }
            return $stats;
        } finally {
            $lock->release();
        }
    }

    /**
     * Mark open observations for current site administrators as excluded.
     * Preserve fingerprints, observed times and original IDs (no deletion).
     * This only changes audit metadata, never Level Up/promotion balances.
     *
     * Note: historical administrator status cannot be reconstructed here;
     * excluded signifies the current CampusFR learner-XP policy.
     */
    public function exclude_site_admin_cases(int $now): int {
        $count = 0;
        foreach (get_admins() as $admin) {
            $userid = (int)$admin->id;
            if ($userid <= 0 || !is_siteadmin($userid)) {
                continue;
            }
            $records = $this->db->get_records_select('local_subs_xp_audit',
                'userid = :userid AND status = :status AND reason IN (:v4personal,:v4promo,:v3personal,:v3promo)',
                ['userid' => $userid, 'status' => 'review',
                    'v4personal' => 'v4_completion_review_only',
                    'v4promo' => 'v4_promotion_review_only',
                    'v3personal' => 'no_personal_completion_log',
                    'v3promo' => 'no_promotion_source_ledger'],
                'id ASC', 'id,status,reason');
            foreach ($records as $record) {
                $this->db->update_record('local_subs_xp_audit', (object)[
                    'id' => (int)$record->id,
                    'status' => 'excluded',
                    'reason' => 'excluded_site_admin:' . (string)$record->reason,
                    'resolvedat' => $now,
                    'lastseen' => $now,
                    'timemodified' => $now,
                ]);
                $count++;
            }
        }
        return $count;
    }

    /** @return object[] */
    private function effective_rules(int $courseid): array {
        $ctx = \context_course::instance($courseid, IGNORE_MISSING);
        if (!$ctx) {
            return [];
        }
        $sys = (int)\context_system::instance()->id;
        $rules = $this->db->get_records_sql(
            "SELECT id,contextid,childcontextid,points,type,filter,filtercmid,
                    filterint1,filterchar1
               FROM {block_xp_rule}
              WHERE ((contextid=:ctx AND childcontextid=0)
                 OR (contextid=:sys AND childcontextid IN (0,:scoped)))
                AND type=:type AND points>0",
            ['ctx' => (int)$ctx->id, 'sys' => $sys,
                'scoped' => (int)$ctx->id, 'type' => 'cm_completion']);
        $local = array_filter($rules, static fn($r) => (int)$r->contextid === (int)$ctx->id);
        $scoped = array_filter($rules, static fn($r) => (int)$r->childcontextid === (int)$ctx->id);
        return $local ?: ($scoped ?: $rules);
    }

    /**
     * @param array<string,int|string> $stats
     */
    private function observe(string $key, string $kind, object $cc, int $ruleid,
            int $promotionid, int $points, string $reason, int $now,
            array &$stats): void {
        $stats['candidates']++;
        $record = $this->db->get_record('local_subs_xp_audit', ['fingerprint' => $key]);
        if ($record) {
            // Keep original reason and provenance from legacy H5P scanner.
            // Do not overwrite the original evidence time with subsequent replay.
            $this->db->update_record('local_subs_xp_audit', (object)[
                'id' => $record->id, 'lastseen' => $now, 'timemodified' => $now,
                'status' => $record->status === 'resolved' ? 'review' : $record->status,
            ]);
            $stats['seen']++;
            return;
        }
        $this->db->insert_record('local_subs_xp_audit', (object)[
            'fingerprint' => $key, 'kind' => $kind,
            'userid' => (int)$cc->userid, 'courseid' => (int)$cc->courseid,
            'cmid' => (int)$cc->cmid, 'ruleid' => $ruleid,
            'promotionid' => $promotionid, 'points' => $points,
            'reason' => $reason, 'status' => 'review',
            'evidenceat' => (int)$cc->timemodified,
            'firstseen' => $now, 'lastseen' => $now, 'resolvedat' => null,
            'timemodified' => $now,
        ]);
        $stats['new']++;
    }

    /** @param array<string,int|string> $stats */
    private function resolve(string $key, int $now, array &$stats): void {
        $record = $this->db->get_record('local_subs_xp_audit', ['fingerprint' => $key]);
        if ($record && $record->status === 'review') {
            $this->db->update_record('local_subs_xp_audit', (object)[
                'id' => $record->id, 'status' => 'resolved',
                'resolvedat' => $now, 'lastseen' => $now, 'timemodified' => $now,
            ]);
            $stats['resolved']++;
        }
    }
}
