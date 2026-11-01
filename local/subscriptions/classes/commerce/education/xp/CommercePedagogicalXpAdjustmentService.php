<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\xp;

defined('MOODLE_INTERNAL') || die();

/**
 * Append-only, auditable manual XP awards. Does not mutate personal Level Up XP.
 * User bonuses are also recorded in the existing promotion XP ledger;
 * group bonuses only affect that group's promotion leaderboard.
 */
final class CommercePedagogicalXpAdjustmentService {
    public const TABLE = 'local_subs_commerce_ped_xpadj';
    public const USER = 'user';
    public const GROUP = 'group';
    public const MAX_USER_POINTS = 500;
    public const MAX_GROUP_POINTS = 5000;

    public function __construct(private readonly \moodle_database $db) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        return new self($db ?? $DB);
    }

    /**
     * Awards positive XP scoped to one promotion. POST/session/capability checks
     * are performed by the CRM entry point; this service validates the target.
     */
    public function award(
        int $promotionid,
        string $targettype,
        int $targetid,
        int $points,
        string $reason,
        int $actorid,
        string $requestkey,
        ?int $now = null
    ): \stdClass {
        $now ??= time();
        if ($promotionid <= 0 || $targetid <= 0 || $actorid <= 0) {
            throw new \coding_exception('Manual XP adjustment requires valid identifiers.');
        }
        if (!in_array($targettype, [self::USER, self::GROUP], true)) {
            throw new \coding_exception('Invalid manual XP target type.');
        }
        $limit = $targettype === self::USER ? self::MAX_USER_POINTS : self::MAX_GROUP_POINTS;
        if ($points < 1 || $points > $limit) {
            throw new \coding_exception('Manual XP adjustment exceeds the allowed range.');
        }
        $reason = trim($reason);
        if (\core_text::strlen($reason) < 8 || \core_text::strlen($reason) > 250) {
            throw new \coding_exception('Manual XP requires a reason between 8 and 250 characters.');
        }
        if (!preg_match('/\A[a-f0-9]{32}\z/D', $requestkey)) {
            throw new \coding_exception('Invalid manual XP request id.');
        }
        $promotion = $this->db->get_record('local_subs_commerce_ped_promo', ['id' => $promotionid],
            'id,courseid,status,endsat,xpstartsat', MUST_EXIST);
        if (!in_array((string)$promotion->status, ['scheduled', 'open', 'full', 'started'], true) ||
            ($promotion->endsat !== null && (int)$promotion->endsat < $now)) {
            throw new \coding_exception('Manual XP cannot be credited to a closed promotion.');
        }
        if ($promotion->xpstartsat !== null && $now < (int)$promotion->xpstartsat) {
            throw new \coding_exception('Manual XP cannot be credited before the promotion scoring start.');
        }
        if ($targettype === self::USER) {
            if (!$this->db->record_exists('local_subs_commerce_ped_join', [
                'promotionid' => $promotionid, 'userid' => $targetid, 'state' => 'active'])) {
                throw new \coding_exception('Manual XP user must be an active promotion participant.');
            }
        } else {
            if (!$this->db->record_exists('local_subs_commerce_ped_group', [
                'id' => $targetid, 'promotionid' => $promotionid, 'active' => 1])) {
                throw new \coding_exception('Manual XP group must be active in the selected promotion.');
            }
            // An empty group must not rank ahead of groups with learners by receiving manual XP.
            if (!$this->db->record_exists_sql(
                "SELECT 1 FROM {local_subs_commerce_ped_gmem} gm
                   JOIN {local_subs_commerce_ped_join} j ON j.userid = gm.userid
                    AND j.promotionid = :promotionid AND j.state = :joinstate
                  WHERE gm.groupid = :groupid AND gm.active = 1",
                ['promotionid' => $promotionid, 'joinstate' => 'active', 'groupid' => $targetid]
            )) {
                throw new \coding_exception('Manual XP requires a group with active promotion members.');
            }
        }
        // A request id is generated once per form rendering. Replaying the POST
        // must never award the same bonus twice.
        $previous = $this->db->get_record(self::TABLE,
            ['promotionid' => $promotionid, 'requestkey' => $requestkey], '*', IGNORE_MISSING);
        if ($previous) {
            if ((string)$previous->targettype !== $targettype ||
                (int)($targettype === self::USER ? $previous->userid : $previous->groupid) !== $targetid ||
                (int)$previous->points !== $points || (string)$previous->reason !== $reason ||
                (int)$previous->actorid !== $actorid) {
                throw new \coding_exception('A manual XP request id cannot be reused for another award.');
            }
            return $previous;
        }
        $transaction = $this->db->start_delegated_transaction();
        $record = (object)[
            'promotionid' => $promotionid,
            'targettype' => $targettype,
            'userid' => $targettype === self::USER ? $targetid : null,
            'groupid' => $targettype === self::GROUP ? $targetid : null,
            'points' => $points,
            'reason' => $reason,
            'actorid' => $actorid,
            'requestkey' => $requestkey,
            'timecreated' => $now,
        ];
        $record->id = (int)$this->db->insert_record(self::TABLE, $record);
        if ($targettype === self::USER) {
            CommercePedagogicalXpService::create($this->db)->record_once(
                $promotionid, $targetid, 'local_subscriptions', 'manual_adjustment',
                'adjustment:' . $record->id, $points, $now, $now
            );
        }
        $transaction->allow_commit();
        return $record;
    }

    /**
     * Cancels a manual bonus without deleting its audit row or personal XP.
     * For user bonuses the original positive ledger event is retained for audit;
     * leaderboard repository totals subtract cancelled bonuses.
     * A second cancellation is idempotent and keeps the original actor and date.
     */
    public function cancel(
        int $promotionid,
        string $targettype,
        int $targetid,
        int $adjustmentid,
        int $actorid,
        ?int $now = null
    ): \stdClass {
        if ($promotionid <= 0 || $targetid <= 0 || $adjustmentid <= 0 || $actorid <= 0 ||
            !in_array($targettype, [self::USER, self::GROUP], true)) {
            throw new \coding_exception('Invalid manual XP cancellation identifiers.');
        }
        $now ??= time();
        if ($now <= 0) {
            throw new \coding_exception('Invalid manual XP cancellation timestamp.');
        }
        $lock = \core\lock\lock_config::get_lock_factory('local_subscriptions_ped_xp')
            ->get_lock('manual-xp-cancel:' . $promotionid . ':' . $adjustmentid, 10);
        if (!$lock) {
            throw new \coding_exception('Could not lock manual XP cancellation.');
        }
        try {
            $record = $this->db->get_record(self::TABLE,
                ['id' => $adjustmentid, 'promotionid' => $promotionid], '*', MUST_EXIST);
            $actualtarget = $targettype === self::USER ? (int)$record->userid : (int)$record->groupid;
            if ((string)$record->targettype !== $targettype || $actualtarget !== $targetid) {
                throw new \coding_exception('Bonus does not belong to this promotion target.');
            }
            if ($record->cancelledat !== null) {
                return $record;
            }
            $transaction = $this->db->start_delegated_transaction();
            $record->cancelledat = $now;
            $record->cancelledby = $actorid;
            $this->db->update_record(self::TABLE, (object)[
                'id' => $record->id,
                'cancelledat' => $now,
                'cancelledby' => $actorid,
            ]);
            $transaction->allow_commit();
            return $record;
        } finally {
            $lock->release();
        }
    }

    /** @return \stdClass[] */
    public function recent_for_target(int $promotionid, string $targettype, int $targetid, int $limit = 5): array {
        if (!in_array($targettype, [self::USER, self::GROUP], true)) {
            throw new \coding_exception('Invalid adjustment target.');
        }
        $field = $targettype === self::USER ? 'userid' : 'groupid';
        return array_values($this->db->get_records(self::TABLE,
            ['promotionid' => $promotionid, 'targettype' => $targettype, $field => $targetid],
            'id DESC', '*', 0, max(1, min($limit, 20))));
    }

    /** @return array<int,int> Group id => manual group XP total. */
    public function group_bonus_totals(int $promotionid): array {
        $rows = $this->db->get_records_sql(
            "SELECT groupid, SUM(points) AS points
               FROM {local_subs_commerce_ped_xpadj}
              WHERE promotionid = :promotionid AND targettype = :targettype
                AND groupid IS NOT NULL AND cancelledat IS NULL
           GROUP BY groupid",
            ['promotionid' => $promotionid, 'targettype' => self::GROUP]
        );
        $result = [];
        foreach ($rows as $row) {
            $result[(int)$row->groupid] = (int)$row->points;
        }
        return $result;
    }
}
