<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\xp;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\education\calendar\CommercePedagogicalSectionHierarchy;

/**
 * Read-only per-module promotion XP badges for the current learner.
 *
 * A positive Level Up log proves the particular rule/reason/subtype and
 * lets us match the promotion ledger's hashed source identity exactly.
 * Moodle completion is deliberately NOT treated as proof of promotion XP.
 * No callback, bridge, scheduled task or awarding service is called here.
 */
final class CommercePedagogicalActivityXpDisplayService {
    private const REPLAY_MODULES = ['h5pactivity', 'minilesson', 'readaloud', 'solo'];
    private const LIVE_STATUSES = ['scheduled', 'open', 'full', 'started'];

    public function __construct(private readonly \moodle_database $db) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        return new self($db ?? $DB);
    }

    /**
     * @return array<int,array{cmid:int,state:string,label:string,title:string}>
     */
    public function for_user_course(int $userid, int $courseid, ?int $now = null): array {
        if ($userid <= 1 || $courseid <= SITEID) {
            return [];
        }
        $now ??= time();

        [$statussql, $statusparams] = $this->db->get_in_or_equal(
            self::LIVE_STATUSES, SQL_PARAMS_NAMED, 'xpdisplaystatus'
        );
        // Require the same ped_access + active join used by the promotion XP bridge.
        // Only promotions with the explicit scoring window provide safe activity provenance.
        $relations = $this->db->get_records_sql(
            "SELECT pa.id, pp.id AS promotionid, pp.xpstartsat
               FROM {local_subs_commerce_ped_access} pa
               JOIN {local_subs_commerce_ped_join} pj
                 ON pj.userid = pa.userid AND pj.courseid = pa.courseid
                AND pj.promotionid = pa.promotionid AND pj.state = :joinstate
               JOIN {local_subs_commerce_ped_promo} pp ON pp.id = pa.promotionid
              WHERE pa.userid = :userid AND pa.courseid = :courseid
                AND pp.status {$statussql}
                AND pp.xpstartsat IS NOT NULL AND pp.xpstartsat <= :now
                AND (pp.endsat IS NULL OR pp.endsat >= :endnow)
           ORDER BY pp.id DESC",
            ['joinstate' => 'active', 'userid' => $userid, 'courseid' => $courseid,
                'now' => $now, 'endnow' => $now] + $statusparams,
            0, 1
        );
        if (!$relations) {
            return [];
        }
        $promotion = reset($relations);
        $promotionid = (int)$promotion->promotionid;
        try {
            $modinfo = get_fast_modinfo($courseid, $userid);
        } catch (\Throwable) {
            return [];
        }

        // Evaluate unlock dates on the ancestor section, including Moodle 5 delegated subsections.
        $releases = $this->db->get_records('local_subs_commerce_ped_cal', [
            'promotionid' => $promotionid, 'itemtype' => 'course_section',
        ], '', 'id,itemid,unlocksat');
        $released = [];
        foreach ($releases as $release) {
            $released[(int)$release->itemid] = (int)$release->unlocksat <= $now;
        }
        $parents = CommercePedagogicalSectionHierarchy::create($this->db)->subsection_parents($courseid);
        $modules = $this->db->get_records('course_modules', ['course' => $courseid], '', 'id,section');
        $eligible = [];
        foreach ($modinfo->cms as $cm) {
            $cmid = (int)$cm->id;
            if (!in_array($cm->modname, self::REPLAY_MODULES, true) || !$cm->uservisible ||
                    !$cm->visible || !isset($modules[$cmid])) {
                continue;
            }
            $sectionid = (int)$modules[$cmid]->section;
            $visited = [];
            while ($sectionid > 0 && !isset($visited[$sectionid])) {
                $visited[$sectionid] = true;
                if (array_key_exists($sectionid, $released)) {
                    if ($released[$sectionid]) {
                        $eligible[$cmid] = true;
                    }
                    break;
                }
                $sectionid = $parents[$sectionid] ?? 0;
            }
        }
        if (!$eligible) {
            return [];
        }

        // Read only the current learner's logs (not the records of other students).
        $logs = $this->db->get_recordset_sql(
            "SELECT l.id, l.ruleid, l.reason, l.subtype, ctx.instanceid AS cmid
               FROM {block_xp_logs} l
               JOIN {context} ctx ON ctx.id = l.envid AND ctx.contextlevel = :contextlevel
               JOIN {course_modules} cm ON cm.id = ctx.instanceid AND cm.course = :courseid
              WHERE l.userid = :userid AND l.points > 0 AND l.ruleid > 0",
            ['contextlevel' => CONTEXT_MODULE, 'courseid' => $courseid, 'userid' => $userid]
        );
        $sourcekeys = [];
        $completions = [];
        try {
            foreach ($logs as $log) {
                $cmid = (int)$log->cmid;
                if (!isset($eligible[$cmid])) {
                    continue;
                }
                $key = CommercePedagogicalActivityXpGate::source_key(
                    $cmid, (int)$log->ruleid, (string)$log->reason,
                    $log->subtype === null ? null : (string)$log->subtype
                );
                $sourcekeys[$key] = $cmid;
                if ($log->reason === 'activity_completion') {
                    $completions[$cmid] = true;
                }
            }
        } finally {
            $logs->close();
        }
        $earned = [];
        if ($sourcekeys) {
            // The ledger is authoritative: a completed activity alone is not an XP grant.
            [$insql, $params] = $this->db->get_in_or_equal(
                array_keys($sourcekeys), SQL_PARAMS_NAMED, 'xpsourcekey'
            );
            $records = $this->db->get_records_sql(
                "SELECT id, sourcekey FROM {local_subs_commerce_ped_xp}
                  WHERE promotionid = :promotionid AND userid = :userid
                    AND sourcecomponent = :component AND sourcetype = :sourcetype
                    AND sourcekey {$insql}",
                ['promotionid' => $promotionid, 'userid' => $userid,
                    'component' => 'block_xp', 'sourcetype' => 'eligible_activity'] + $params
            );
            foreach ($records as $row) {
                $cmid = $sourcekeys[(string)$row->sourcekey] ?? null;
                if ($cmid !== null) {
                    $earned[$cmid] = true;
                }
            }
        }

        $result = [];
        $labels = [
            'earned' => 'commerce_promo_xp_badge_earned',
            'retry' => 'commerce_promo_xp_badge_retry',
            'potential' => 'commerce_promo_xp_badge_potential',
        ];
        $help = [
            'earned' => 'commerce_promo_xp_badge_earned_help',
            'retry' => 'commerce_promo_xp_badge_retry_help',
            'potential' => 'commerce_promo_xp_badge_potential_help',
        ];
        foreach ($eligible as $cmid => $_) {
            $state = self::classify(isset($earned[$cmid]), isset($completions[$cmid]));
            $result[] = [
                'cmid' => (int)$cmid,
                'state' => $state,
                'label' => get_string($labels[$state], 'local_subscriptions'),
                'title' => get_string($help[$state], 'local_subscriptions'),
            ];
        }
        return $result;
    }

    /** Prefer confirmed ledger gains; old completions only support a conditional retry hint. */
    public static function classify(bool $ledgeraward, bool $previouscompletion): string {
        if ($ledgeraward) {
            return 'earned';
        }
        return $previouscompletion ? 'retry' : 'potential';
    }
}
