<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\xp;

defined('MOODLE_INTERNAL') || die();

/**
 * Read-only history of promotion XP. Never calls the XP awarding bridge.
 * The learner entry point is restricted to active membership by its own controller.
 * The CRM entry points must require VIEW_PROMOTIONS in the calling controller.
 */
final class CommercePedagogicalXpHistoryService {
    public const PAGE_SIZE = 25;

    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommercePedagogicalXpRepository $repository
    ) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        $db = $db ?? $DB;
        return new self($db, CommercePedagogicalXpRepository::create($db));
    }

    /**
     * @return array<string,mixed>|null Returns null unless this user is an active participant.
     */
    public function get_for_user(int $promotionid, int $userid, int $page = 0): ?array {
        return $this->get_member_history($promotionid, $userid, $page, true);
    }

    /**
     * CRM-only view, including former members. Callers MUST check VIEW_PROMOTIONS.
     * @return array<string,mixed>|null
     */
    public function get_for_crm_user(int $promotionid, int $userid, int $page = 0): ?array {
        require_capability(\local_subscriptions\admin\Capabilities::VIEW_PROMOTIONS,
            \context_system::instance());
        return $this->get_member_history($promotionid, $userid, $page, false);
    }

    private function get_member_history(int $promotionid, int $userid, int $page, bool $activeonly): ?array {
        if ($promotionid <= 0 || $userid <= 1) {
            return null;
        }

        $params = ['promotionid' => $promotionid, 'userid' => $userid];
        $activeclause = '';
        if ($activeonly) {
            $activeclause = ' AND j.state = :state';
            $params['state'] = 'active';
        }
        $relations = $this->db->get_records_sql(
            "SELECT p.id, p.name, p.courseid
               FROM {local_subs_commerce_ped_join} j
               JOIN {local_subs_commerce_ped_promo} p ON p.id = j.promotionid
              WHERE j.promotionid = :promotionid AND j.userid = :userid{$activeclause}
           ORDER BY j.id DESC",
            $params, 0, 1
        );
        if (!$relations && !$activeonly && $this->db->record_exists(
                CommercePedagogicalXpRepository::TABLE,
                ['promotionid' => $promotionid, 'userid' => $userid])) {
            // Keep the CRM audit visible even when a former member's join was removed.
            $record = $this->db->get_record('local_subs_commerce_ped_promo', ['id' => $promotionid],
                'id,name,courseid', IGNORE_MISSING);
            $relations = $record ? [$record] : [];
        }
        if (!$relations) {
            return null;
        }
        $promotion = reset($relations);
        $count = $this->db->count_records(CommercePedagogicalXpRepository::TABLE, [
            'promotionid' => $promotionid, 'userid' => $userid,
        ]);
        $maxpage = $count > 0 ? intdiv($count - 1, self::PAGE_SIZE) : 0;
        $page = max(0, min($page, $maxpage));
        $ledger = $this->db->get_records(CommercePedagogicalXpRepository::TABLE, [
            'promotionid' => $promotionid, 'userid' => $userid,
        ], 'timeearned DESC, id DESC', '*', $page * self::PAGE_SIZE, self::PAGE_SIZE);

        $adjustmentids = [];
        $activitykeys = [];
        foreach ($ledger as $record) {
            if ($record->sourcetype === 'manual_adjustment' &&
                    preg_match('/^adjustment:([1-9][0-9]*)$/D', (string)$record->sourcekey, $match)) {
                $adjustmentids[] = (int)$match[1];
            }
            if ($record->sourcecomponent === 'block_xp' && $record->sourcetype === 'eligible_activity') {
                $activitykeys[(string)$record->sourcekey] = true;
            }
        }

        $adjustments = [];
        if ($adjustmentids) {
            foreach ($this->db->get_records_list('local_subs_commerce_ped_xpadj', 'id',
                    array_values(array_unique($adjustmentids))) as $row) {
                if ((int)$row->promotionid === $promotionid && (int)$row->userid === $userid &&
                        (string)$row->targettype === 'user') {
                    $adjustments[(int)$row->id] = $row;
                }
            }
        }

        // Resolve activity names only on a verified, exact source-key match;
        // an activity completion flag alone cannot prove a promotion XP award.
        $activities = $this->resolve_awarded_activities((int)$promotion->courseid, $userid, $activitykeys);
        $rows = [];
        foreach ($ledger as $record) {
            $ismannual = (string)$record->sourcetype === 'manual_adjustment';
            $adjustment = null;
            if ($ismannual && preg_match('/^adjustment:([1-9][0-9]*)$/D', (string)$record->sourcekey, $match)) {
                $adjustment = $adjustments[(int)$match[1]] ?? null;
            }
            $cancelledat = $adjustment && $adjustment->cancelledat !== null
                ? (int)$adjustment->cancelledat : 0;
            $activity = $activities[(string)$record->sourcekey] ?? null;
            $entry = [
                'date' => userdate((int)$record->timeearned, get_string('strftimedatetime', 'langconfig')),
                'points' => (int)$record->points,
                'isactive' => $cancelledat === 0,
                'isbonus' => $ismannual,
                'iscancelled' => $cancelledat !== 0,
                'cancelledlabel' => $cancelledat ? get_string('commerce_promo_xp_history_cancelled',
                    'local_subscriptions', userdate($cancelledat, get_string('strftimedatetime', 'langconfig'))) : '',
                'source' => $activity !== null
                    ? $activity['name']
                    : get_string($ismannual ? 'commerce_promo_xp_history_manual'
                        : ($record->sourcetype === 'eligible_activity'
                            ? 'commerce_promo_xp_history_activity' : 'commerce_promo_xp_history_other'),
                        'local_subscriptions'),
                'hasactivityurl' => $activity !== null && $activity['url'] !== '',
                'activityurl' => $activity['url'] ?? '',
            ];
            if (!$activeonly) {
                // Private CRM note: deliberately absent from the learner's returned data.
                $entry['crmreason'] = $adjustment ? (string)$adjustment->reason : '';
            }
            $rows[] = $entry;
        }

        return [
            'promotionid' => $promotionid,
            'promotionname' => format_string((string)$promotion->name, true,
                ['context' => \context_course::instance((int)$promotion->courseid)]),
            'totalpoints' => $this->repository->user_points($promotionid, $userid),
            'rewardcount' => (int)$count,
            'hasrewards' => $rows !== [],
            'rows' => $rows,
            'page' => $page,
        ];
    }

    /**
     * CRM-only, paginated promotion-wide ledger. This aggregates individual XP only;
     * group-level manual bonuses are a separate ledger visible on the Groups page.
     * Callers MUST check VIEW_PROMOTIONS before invoking this method.
     *
     * @return array<string,mixed>|null
     */
    public function get_for_crm_promotion(int $promotionid, int $page = 0): ?array {
        require_capability(\local_subscriptions\admin\Capabilities::VIEW_PROMOTIONS,
            \context_system::instance());
        if ($promotionid <= 0) {
            return null;
        }
        $promotion = $this->db->get_record('local_subs_commerce_ped_promo',
            ['id' => $promotionid], 'id,name', IGNORE_MISSING);
        if (!$promotion) {
            return null;
        }
        $count = $this->db->count_records(CommercePedagogicalXpRepository::TABLE,
            ['promotionid' => $promotionid]);
        $maxpage = $count > 0 ? intdiv($count - 1, self::PAGE_SIZE) : 0;
        $page = max(0, min($page, $maxpage));
        $ledger = $this->db->get_records_sql(
            "SELECT x.*, u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic,
                    u.middlename, u.alternatename
               FROM {local_subs_commerce_ped_xp} x
               JOIN {user} u ON u.id = x.userid
              WHERE x.promotionid = :promotionid
           ORDER BY x.timeearned DESC, x.id DESC",
            ['promotionid' => $promotionid], $page * self::PAGE_SIZE, self::PAGE_SIZE
        );
        $ids = [];
        foreach ($ledger as $record) {
            if ($record->sourcetype === 'manual_adjustment' &&
                    preg_match('/^adjustment:([1-9][0-9]*)$/D', (string)$record->sourcekey, $match)) {
                $ids[] = (int)$match[1];
            }
        }
        $adjustments = [];
        if ($ids) {
            foreach ($this->db->get_records_list('local_subs_commerce_ped_xpadj', 'id',
                    array_values(array_unique($ids))) as $adjustment) {
                if ((int)$adjustment->promotionid === $promotionid &&
                        (string)$adjustment->targettype === 'user') {
                    $adjustments[(int)$adjustment->id] = $adjustment;
                }
            }
        }
        $rows = [];
        foreach ($ledger as $record) {
            $manual = $record->sourcetype === 'manual_adjustment';
            $adjustment = null;
            if ($manual && preg_match('/^adjustment:([1-9][0-9]*)$/D',
                    (string)$record->sourcekey, $match)) {
                $candidate = $adjustments[(int)$match[1]] ?? null;
                if ($candidate && (int)$candidate->userid === (int)$record->userid) {
                    $adjustment = $candidate;
                }
            }
            $cancelledat = $adjustment && $adjustment->cancelledat !== null
                ? (int)$adjustment->cancelledat : 0;
            $rows[] = [
                'userid' => (int)$record->userid,
                'username' => fullname($record),
                'date' => userdate((int)$record->timeearned,
                    get_string('strftimedatetime', 'langconfig')),
                'points' => (int)$record->points,
                'iscancelled' => $cancelledat > 0,
                'isactive' => $cancelledat === 0,
                'cancelledlabel' => $cancelledat
                    ? get_string('commerce_promo_xp_history_cancelled', 'local_subscriptions',
                        userdate($cancelledat, get_string('strftimedatetime', 'langconfig'))) : '',
                // Exact activity links require a per-user lookup (available in detailed view).
                'source' => get_string($manual ? 'commerce_promo_xp_history_manual'
                    : ($record->sourcetype === 'eligible_activity'
                        ? 'commerce_promo_xp_history_activity' : 'commerce_promo_xp_history_other'),
                    'local_subscriptions'),
                'crmreason' => $adjustment ? (string)$adjustment->reason : '',
            ];
        }
        return [
            'promotionid' => $promotionid,
            'promotionname' => format_string((string)$promotion->name, true,
                ['context' => \context_system::instance()]),
            'totalpoints' => array_sum($this->repository->points_by_user($promotionid)),
            'rewardcount' => $count,
            'hasrewards' => $rows !== [],
            'rows' => $rows,
            'page' => $page,
        ];
    }

    /**
     * @param array<string,bool> $wantedkeys
     * @return array<string,array{name:string,url:string}>
     */
    private function resolve_awarded_activities(int $courseid, int $userid, array $wantedkeys): array {
        if (!$wantedkeys) {
            return [];
        }
        $results = [];
        $recordset = $this->db->get_recordset_sql(
            "SELECT l.id, l.ruleid, l.reason, l.subtype, cm.id AS cmid
               FROM {block_xp_logs} l
               JOIN {context} ctx ON ctx.id = l.envid AND ctx.contextlevel = :modulecontext
               JOIN {course_modules} cm ON cm.id = ctx.instanceid AND cm.course = :courseid
              WHERE l.userid = :userid AND l.points > 0 AND l.ruleid > 0
           ORDER BY l.id DESC",
            ['modulecontext' => CONTEXT_MODULE, 'courseid' => $courseid, 'userid' => $userid]
        );
        try {
            foreach ($recordset as $log) {
                $key = CommercePedagogicalActivityXpGate::source_key(
                    (int)$log->cmid, (int)$log->ruleid,
                    (string)$log->reason, $log->subtype === null ? null : (string)$log->subtype
                );
                if (isset($wantedkeys[$key]) && !isset($results[$key])) {
                    $results[$key] = ['cmid' => (int)$log->cmid];
                    if (count($results) === count($wantedkeys)) {
                        break;
                    }
                }
            }
        } finally {
            $recordset->close();
        }
        if (!$results) {
            return [];
        }

        try {
            $modinfo = get_fast_modinfo($courseid, $userid);
        } catch (\Throwable) {
            return [];
        }
        $resolved = [];
        foreach ($results as $sourcekey => $result) {
            try {
                $cm = $modinfo->get_cm($result['cmid']);
                if (!$cm->uservisible) {
                    continue;
                }
                $resolved[$sourcekey] = [
                    'name' => format_string((string)$cm->name, true,
                        ['context' => \context_module::instance((int)$cm->id)]),
                    'url' => $cm->url ? $cm->url->out(false) : '',
                ];
            } catch (\Throwable) {
                // Deleted/hidden activities must not invent a title or URL.
            }
        }
        return $resolved;
    }
}
