<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\xp;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\education\calendar\CommercePedagogicalSectionHierarchy;

/**
 * Read-only, fail-closed learner badges. This class NEVER awards, resets, or replays points.
 * Unknown amounts are omitted rather than pretending that a rule will pay a known amount.
 */
final class CommercePedagogicalCompactPointsBadgeService {
    private const MODULES = ['h5pactivity', 'minilesson', 'readaloud', 'solo'];
    private const PROMOTION_STATUSES = ['scheduled', 'open', 'full', 'started'];

    public function __construct(private readonly \moodle_database $db) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        return new self($db ?? $DB);
    }

    /**
     * Resolve a nominal completion award only when an unambiguous, directly supported
     * Level Up rule matches the activity. No guessed global point values.
     *
     * @param array<int,object> $rules Recordset values from block_xp_rule.
     */
    public static function nominal_points(array $rules, int $cmid, ?string $cmname = null): ?int {
        $matches = [];
        foreach ($rules as $rule) {
            if ((string)$rule->type !== 'cm_completion' || (int)$rule->points <= 0) {
                continue;
            }
            $filter = (string)$rule->filter;
            if ($filter === 'cm' && (int)$rule->filtercmid === $cmid) {
                $matches[9000][] = (int)$rule->points;
            } else if ($filter === 'cmname' && $cmname !== null &&
                    isset($rule->filterchar1, $rule->filterint1) &&
                    in_array((int)$rule->filterint1, [0, 1], true)) {
                // Level Up XP: 0 = name equals, 1 = name contains.
                // Fail closed if the filter configuration is missing or empty.
                $needle = trim((string)$rule->filterchar1);
                if ($needle !== '') {
                    $actual = \core_text::strtolower($cmname);
                    $expected = \core_text::strtolower($needle);
                    $applies = (int)$rule->filterint1 === 0
                        ? $actual === $expected : str_contains($actual, $expected);
                    if ($applies) {
                        $matches[2000][] = (int)$rule->points;
                    }
                }
            } else if ($filter === 'anycm') {
                $matches[9][] = (int)$rule->points;
            }
        }
        if (!$matches) {
            return null;
        }
        $points = $matches[max(array_keys($matches))];
        // Matching priority and world context can make multiple rules ambiguous.
        return count($points) === 1 ? $points[0] : null;
    }

    /**
     * @return array<int,array{cmid:int,badges:array<int,array<string,mixed>>}>
     */
    public function for_user_course(int $userid, int $courseid, ?int $now = null): array {
        if ($userid <= 1 || $courseid <= SITEID) {
            return [];
        }
        $now ??= time();
        try {
            $coursecontext = \context_course::instance($courseid);
            $modinfo = get_fast_modinfo($courseid, $userid);
        } catch (\Throwable) {
            return [];
        }
        $modules = $this->db->get_records('course_modules', ['course' => $courseid], '', 'id,section');
        $cms = [];
        $cmnames = [];
        foreach ($modinfo->cms as $cm) {
            if (in_array($cm->modname, self::MODULES, true) && (bool)$cm->visible &&
                    isset($modules[(int)$cm->id])) {
                $cms[(int)$cm->id] = (int)$modules[(int)$cm->id]->section;
                $cmnames[(int)$cm->id] = (string)$cm->name;
            }
        }
        if (!$cms) {
            return [];
        }

        // Only a uniquely determinable completion rule may supply an unearned amount.
        // On complex/overlapping Level Up rules, the service uses a real logged amount
        // or omits a badge instead of displaying an inaccurate number.
        $systemcontextid = (int)\context_system::instance()->id;
        $rules = $this->db->get_records_sql(
            "SELECT id, contextid, childcontextid, points, type, filter, filtercmid,
                   filterint1, filterchar1
               FROM {block_xp_rule}
              WHERE ((contextid = :coursectx AND childcontextid = 0)
                  OR (contextid = :systemctx AND childcontextid IN (0, :systemcoursectx)))
                AND type = :ruletype AND points > 0",
            ['coursectx' => (int)$coursecontext->id, 'systemctx' => $systemcontextid,
                'systemcoursectx' => (int)$coursecontext->id, 'ruletype' => 'cm_completion']
        );
        // Respect the same context precedence as Level Up (course-specific
        // before system-wide), while remaining conservative on ambiguous rules.
        $courseworld = array_filter($rules,
            static fn($r) => (int)$r->contextid === (int)$coursecontext->id);
        $coursescoped = array_filter($rules,
            static fn($r) => (int)$r->childcontextid === (int)$coursecontext->id);
        $effectiverules = $courseworld ?: ($coursescoped ?: $rules);
        $amounts = [];
        foreach ($cms as $cmid => $_) {
            $amounts[$cmid] = self::nominal_points($effectiverules, $cmid, $cmnames[$cmid]);
        }

        $logs = $this->db->get_recordset_sql(
            "SELECT l.id, l.points, l.ruleid, l.reason, l.subtype, ctx.instanceid AS cmid
               FROM {block_xp_logs} l
               JOIN {context} ctx ON ctx.id = l.envid AND ctx.contextlevel = :modulelevel
               JOIN {course_modules} cm ON cm.id = ctx.instanceid AND cm.course = :courseid
              WHERE l.userid = :userid AND l.points > 0 AND l.ruleid > 0
                AND l.reason = :reason
           ORDER BY l.id DESC",
            ['modulelevel' => CONTEXT_MODULE, 'courseid' => $courseid,
                'userid' => $userid, 'reason' => 'activity_completion']
        );
        $personalearned = [];
        $sources = [];
        $observedamounts = [];
        try {
            foreach ($logs as $log) {
                $cmid = (int)$log->cmid;
                if (!isset($cms[$cmid])) {
                    continue;
                }
                // A positive personal award is proof, independently of Moodle completion.
                $personalearned[$cmid] = true;
                $observedamounts[$cmid] ??= (int)$log->points;
                $key = CommercePedagogicalActivityXpGate::source_key(
                    $cmid, (int)$log->ruleid, (string)$log->reason,
                    $log->subtype === null ? null : (string)$log->subtype
                );
                $sources[$key] = $cmid;
            }
        } finally {
            $logs->close();
        }

        // A user must have BOTH a current ped_access AND an active join.
        // Never show a promotion badge to a non-participant or former member.
        [$statussql, $statusparams] = $this->db->get_in_or_equal(
            self::PROMOTION_STATUSES, SQL_PARAMS_NAMED, 'cfstatus'
        );
        $relations = $this->db->get_records_sql(
            "SELECT pa.id, p.id AS promotionid, p.xpstartsat, p.endsat
               FROM {local_subs_commerce_ped_access} pa
               JOIN {local_subs_commerce_ped_join} j ON j.userid = pa.userid
                 AND j.courseid = pa.courseid AND j.promotionid = pa.promotionid
                 AND j.state = :joinstate
               JOIN {local_subs_commerce_ped_promo} p ON p.id = pa.promotionid
              WHERE pa.userid = :userid AND pa.courseid = :courseid
                AND p.xpstartsat IS NOT NULL AND p.status {$statussql}
                AND (p.endsat IS NULL OR p.endsat >= :now)
           ORDER BY p.id DESC",
            ['joinstate' => 'active', 'userid' => $userid, 'courseid' => $courseid, 'now' => $now]
                + $statusparams, 0, 1
        );
        $promotion = $relations ? reset($relations) : null;
        $promocalendar = [];
        $promoearned = [];
        if ($promotion) {
            $promotionid = (int)$promotion->promotionid;
            $releases = $this->db->get_records('local_subs_commerce_ped_cal', [
                'promotionid' => $promotionid, 'itemtype' => 'course_section',
            ], '', 'id,itemid,unlocksat');
            $released = [];
            foreach ($releases as $release) {
                $released[(int)$release->itemid] = (int)$release->unlocksat;
            }
            $parents = CommercePedagogicalSectionHierarchy::create($this->db)
                ->subsection_parents($courseid);
            foreach ($cms as $cmid => $sectionid) {
                $visited = [];
                while ($sectionid > 0 && !isset($visited[$sectionid])) {
                    $visited[$sectionid] = true;
                    if (array_key_exists($sectionid, $released)) {
                        $promocalendar[$cmid] = max((int)$promotion->xpstartsat,
                            $released[$sectionid]);
                        break;
                    }
                    $sectionid = $parents[$sectionid] ?? 0;
                }
            }
            if ($sources) {
                [$keysql, $keyparams] = $this->db->get_in_or_equal(
                    array_keys($sources), SQL_PARAMS_NAMED, 'cfsourcekey'
                );
                $ledger = $this->db->get_records_sql(
                    "SELECT id, sourcekey, points FROM {local_subs_commerce_ped_xp}
                      WHERE promotionid = :promotionid AND userid = :userid
                        AND sourcecomponent = :component AND sourcetype = :sourcetype
                        AND sourcekey {$keysql}",
                    ['promotionid' => $promotionid, 'userid' => $userid,
                        'component' => 'block_xp', 'sourcetype' => 'eligible_activity'] + $keyparams
                );
                foreach ($ledger as $row) {
                    $cmid = $sources[(string)$row->sourcekey] ?? 0;
                    if ($cmid > 0 && (int)$row->points > 0) {
                        $promoearned[$cmid] = ($promoearned[$cmid] ?? 0) + (int)$row->points;
                    }
                }
            }
        }

        $result = [];
        foreach ($cms as $cmid => $_) {
            $personalpoints = isset($personalearned[$cmid])
                ? $observedamounts[$cmid] : ($amounts[$cmid] ?? null);
            $badges = [];
            if ($personalpoints !== null && $personalpoints > 0) {
                $earned = isset($personalearned[$cmid]);
                $badges[] = self::badge('personal', (int)$personalpoints,
                    $earned ? 'earned' : 'available', $now);
            }
            if ($promotion && isset($promocalendar[$cmid])) {
                $earnedpoints = $promoearned[$cmid] ?? null;
                $promopoints = $earnedpoints ?? $personalpoints;
                if ($promopoints !== null && $promopoints > 0) {
                    // Earned takes precedence over a subsequent calendar change.
                    $status = $earnedpoints !== null ? 'earned'
                        : ($now < $promocalendar[$cmid] ? 'locked' : 'available');
                    $badges[] = self::badge('promotion', (int)$promopoints, $status,
                        $promocalendar[$cmid]);
                }
            }
            if ($badges) {
                $result[] = ['cmid' => (int)$cmid, 'badges' => $badges];
            }
        }
        return $result;
    }

    /**
     * Prepare a compact badge without inferring that 'available' guarantees a reward.
     * @return array<string,mixed>
     */
    public static function badge(string $kind, int $points, string $status, int $unlockat): array {
        if (!in_array($kind, ['personal', 'promotion'], true) ||
                !in_array($status, ['earned', 'available', 'locked'], true) || $points <= 0) {
            throw new \coding_exception('Invalid read-only CampusFR points badge.');
        }
        $label = $kind === 'promotion' ? 'commerce_points_badge_promo' : 'commerce_points_badge_personal';
        $help = 'commerce_points_badge_' . $kind . '_' . $status . '_help';
        $args = (object)['points' => $points, 'date' => $unlockat > 0
            ? userdate($unlockat, get_string('strftimedatetime', 'langconfig')) : ''];
        return [
            'kind' => $kind,
            'points' => $points,
            'state' => $status,
            'title' => get_string($help, 'local_subscriptions', $args),
            'iconalt' => get_string($label, 'local_subscriptions'),
            'iconurl' => $kind === 'promotion'
                ? (new \moodle_url('/local/subscriptions/pix/pain_au_chocolat.png'))->out(false) : '',
        ];
    }
}
