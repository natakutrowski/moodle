<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\xp;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;

/**
 * Projects personal Level Up XP gains into current promotion team ledgers.
 *
 * Level Up XP remains authoritative for personal XP. CampusFR only mirrors
 * positive gains while a learner is an active participant of a current
 * pedagogical promotion.
 *
 * Level Up XP can run in course context or globally (system context). In the
 * global mode the source course is intentionally unavailable, therefore one
 * global personal XP gain contributes to each of the learner's current active
 * promotion relations. Finished/archived historical promotions are excluded.
 */
final class CommercePedagogicalLevelupXpBridgeService {
    /** Promotion lifecycle states in which team XP can still be earned. */
    private const SCORING_STATUSES = [
        CommercePedagogicalPromotionStatus::SCHEDULED,
        CommercePedagogicalPromotionStatus::OPEN,
        CommercePedagogicalPromotionStatus::FULL,
        CommercePedagogicalPromotionStatus::STARTED,
    ];

    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommercePedagogicalXpService $xpservice
    ) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        $db = $db ?? $DB;
        return new self($db, CommercePedagogicalXpService::create($db));
    }

    /**
     * Record one Level Up XP increase for the learner's current promotion of a course.
     */
    public function record_gain(
        int $userid,
        int $courseid,
        int $points,
        int $timeearned,
        string $gainid
    ): ?CommercePedagogicalXpRecordResult {
        $this->validate_gain($userid, $points, $timeearned, $gainid);
        if ($courseid <= 0) {
            throw new \coding_exception('Level Up promotion XP bridge requires a valid course id.');
        }

        $access = $this->db->get_record(
            'local_subs_commerce_ped_access',
            ['userid' => $userid, 'courseid' => $courseid],
            'id,promotionid',
            IGNORE_MISSING
        );
        if (!$access || empty($access->promotionid)) {
            return null;
        }

        $promotionid = (int)$access->promotionid;
        if (!$this->is_current_active_participation($promotionid, $courseid, $userid, $timeearned)) {
            return null;
        }

        return $this->xpservice->record_once(
            $promotionid,
            $userid,
            'block_xp',
            'points_increased',
            trim($gainid),
            $points,
            $timeearned
        );
    }

    /**
     * Record one site-wide Level Up XP increase in all current promotions of a learner.
     *
     * Level Up XP's global mode emits a system context and deliberately contains no
     * source course. CampusFR therefore projects the same real personal XP gain into
     * every current promotion relation of the learner. The same gain id is safe
     * because the ledger uniqueness is promotion + user + source.
     *
     * @return array<int,CommercePedagogicalXpRecordResult> promotion id => result
     */
    public function record_global_gain(
        int $userid,
        int $points,
        int $timeearned,
        string $gainid
    ): array {
        $this->validate_gain($userid, $points, $timeearned, $gainid);
        $gainid = trim($gainid);

        [$statussql, $statusparams] = $this->db->get_in_or_equal(
            self::SCORING_STATUSES,
            SQL_PARAMS_NAMED,
            'pedxpstatus'
        );

        $params = [
            'userid' => $userid,
            'joinstate' => 'active',
            'timeearned' => $timeearned,
        ] + $statusparams;

        // ped_access identifies the learner's current relation for each course.
        // Matching ped_join prevents an old/revoked relation from receiving points.
        $rows = $this->db->get_records_sql(
            "SELECT pa.id,
                    pa.courseid,
                    pa.promotionid
               FROM {local_subs_commerce_ped_access} pa
               JOIN {local_subs_commerce_ped_join} pj
                 ON pj.userid = pa.userid
                AND pj.courseid = pa.courseid
                AND pj.promotionid = pa.promotionid
                AND pj.state = :joinstate
               JOIN {local_subs_commerce_ped_promo} pp
                 ON pp.id = pa.promotionid
              WHERE pa.userid = :userid
                AND pa.promotionid IS NOT NULL
                AND pp.status {$statussql}
                AND (pp.endsat IS NULL OR pp.endsat >= :timeearned)
           ORDER BY pa.id ASC",
            $params
        );

        $results = [];
        $seen = [];
        foreach ($rows as $row) {
            $promotionid = (int)$row->promotionid;
            if ($promotionid <= 0 || isset($seen[$promotionid])) {
                continue;
            }
            $seen[$promotionid] = true;
            $results[$promotionid] = $this->xpservice->record_once(
                $promotionid,
                $userid,
                'block_xp',
                'points_increased',
                $gainid,
                $points,
                $timeearned
            );
        }

        return $results;
    }

    private function validate_gain(
        int $userid,
        int $points,
        int $timeearned,
        string $gainid
    ): void {
        if ($userid <= 0) {
            throw new \coding_exception('Level Up promotion XP bridge requires a valid user id.');
        }
        if ($points <= 0 || $timeearned <= 0 || trim($gainid) === '') {
            throw new \coding_exception('Level Up promotion XP bridge requires positive points, timestamp and gain id.');
        }
    }

    private function is_current_active_participation(
        int $promotionid,
        int $courseid,
        int $userid,
        int $timeearned
    ): bool {
        if ($promotionid <= 0 || $courseid <= 0) {
            return false;
        }

        [$statussql, $statusparams] = $this->db->get_in_or_equal(
            self::SCORING_STATUSES,
            SQL_PARAMS_NAMED,
            'pedxpstatus'
        );
        $params = [
            'promotionid' => $promotionid,
            'courseid' => $courseid,
            'userid' => $userid,
            'joinstate' => 'active',
            'timeearned' => $timeearned,
        ] + $statusparams;

        return $this->db->record_exists_sql(
            "SELECT 1
               FROM {local_subs_commerce_ped_promo} pp
               JOIN {local_subs_commerce_ped_join} pj
                 ON pj.promotionid = pp.id
                AND pj.courseid = pp.courseid
                AND pj.userid = :userid
                AND pj.state = :joinstate
              WHERE pp.id = :promotionid
                AND pp.courseid = :courseid
                AND pp.status {$statussql}
                AND (pp.endsat IS NULL OR pp.endsat >= :timeearned)",
            $params
        );
    }
}
