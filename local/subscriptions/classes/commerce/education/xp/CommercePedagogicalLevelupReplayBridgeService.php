<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\xp;

defined('MOODLE_INTERNAL') || die();

use block_xp\local\ruletype\limit_spec;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;

/**
 * Awards promotion-only XP when Level Up suppresses a repeated personal reward.
 *
 * The personal Level Up ledger stays untouched. This bridge is only used when
 * Level Up itself confirms that the action matched a positive rule but refused
 * to award personal XP because that rule/reason limit had already been reached.
 *
 * A replay can contribute once per promotion only when the matching personal
 * reward predates the learner's active participation in that promotion. If the
 * same source was rewarded after joining, M5.2 already mirrored that personal
 * gain and no team-only replay is allowed.
 */
final class CommercePedagogicalLevelupReplayBridgeService {
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
     * Record a team-only replay for one current course promotion.
     */
    public function record_course_replay(
        int $userid,
        int $courseid,
        int $points,
        int $timeearned,
        int $xpcontextid,
        int $ruleid,
        string $reason,
        ?string $subtype,
        ?int $envid,
        ?int $parentid,
        ?int $objectid,
        int $scope
    ): ?CommercePedagogicalXpRecordResult {
        $this->validate($userid, $points, $timeearned, $xpcontextid, $ruleid, $reason, $scope);
        if ($courseid <= 0) {
            throw new \coding_exception('Level Up replay bridge requires a valid course id.');
        }

        $relation = $this->db->get_record_sql(
            "SELECT pj.id AS joinid,
                    pj.timecreated AS jointimecreated,
                    pj.promotionid,
                    pj.courseid
               FROM {local_subs_commerce_ped_access} pa
               JOIN {local_subs_commerce_ped_join} pj
                 ON pj.userid = pa.userid
                AND pj.courseid = pa.courseid
                AND pj.promotionid = pa.promotionid
                AND pj.state = :joinstate
               JOIN {local_subs_commerce_ped_promo} pp
                 ON pp.id = pj.promotionid
              WHERE pa.userid = :userid
                AND pa.courseid = :courseid
                AND pa.promotionid IS NOT NULL
                AND pp.status IN ('scheduled', 'open', 'full', 'started')
                AND (pp.endsat IS NULL OR pp.endsat >= :timeearned)",
            [
                'joinstate' => 'active',
                'userid' => $userid,
                'courseid' => $courseid,
                'timeearned' => $timeearned,
            ],
            IGNORE_MISSING
        );
        if (!$relation) {
            return null;
        }

        return $this->record_for_relation(
            (int)$relation->promotionid,
            (int)$relation->jointimecreated,
            $userid,
            $points,
            $timeearned,
            $xpcontextid,
            $ruleid,
            $reason,
            $subtype,
            $envid,
            $parentid,
            $objectid,
            $scope
        );
    }

    /**
     * Record a team-only replay in each current promotion of a site-wide XP user.
     *
     * @return array<int,CommercePedagogicalXpRecordResult> promotion id => result
     */
    public function record_global_replay(
        int $userid,
        int $points,
        int $timeearned,
        int $xpcontextid,
        int $ruleid,
        string $reason,
        ?string $subtype,
        ?int $envid,
        ?int $parentid,
        ?int $objectid,
        int $scope
    ): array {
        $this->validate($userid, $points, $timeearned, $xpcontextid, $ruleid, $reason, $scope);

        [$statussql, $statusparams] = $this->db->get_in_or_equal(
            self::SCORING_STATUSES,
            SQL_PARAMS_NAMED,
            'm54status'
        );
        $rows = $this->db->get_records_sql(
            "SELECT pj.id AS joinid,
                    pj.timecreated AS jointimecreated,
                    pj.promotionid,
                    pj.courseid
               FROM {local_subs_commerce_ped_access} pa
               JOIN {local_subs_commerce_ped_join} pj
                 ON pj.userid = pa.userid
                AND pj.courseid = pa.courseid
                AND pj.promotionid = pa.promotionid
                AND pj.state = :joinstate
               JOIN {local_subs_commerce_ped_promo} pp
                 ON pp.id = pj.promotionid
              WHERE pa.userid = :userid
                AND pa.promotionid IS NOT NULL
                AND pp.status {$statussql}
                AND (pp.endsat IS NULL OR pp.endsat >= :timeearned)
           ORDER BY pj.id ASC",
            [
                'joinstate' => 'active',
                'userid' => $userid,
                'timeearned' => $timeearned,
            ] + $statusparams
        );

        $results = [];
        $seen = [];
        foreach ($rows as $row) {
            $promotionid = (int)$row->promotionid;
            if ($promotionid <= 0 || isset($seen[$promotionid])) {
                continue;
            }
            $seen[$promotionid] = true;
            $result = $this->record_for_relation(
                $promotionid,
                (int)$row->jointimecreated,
                $userid,
                $points,
                $timeearned,
                $xpcontextid,
                $ruleid,
                $reason,
                $subtype,
                $envid,
                $parentid,
                $objectid,
                $scope
            );
            if ($result !== null) {
                $results[$promotionid] = $result;
            }
        }
        return $results;
    }

    private function record_for_relation(
        int $promotionid,
        int $jointimecreated,
        int $userid,
        int $points,
        int $timeearned,
        int $xpcontextid,
        int $ruleid,
        string $reason,
        ?string $subtype,
        ?int $envid,
        ?int $parentid,
        ?int $objectid,
        int $scope
    ): ?CommercePedagogicalXpRecordResult {
        if ($jointimecreated <= 0) {
            return null;
        }

        // M5.4 only covers a genuinely historical personal reward: Level Up must
        // already have a matching positive log from before this promotion join.
        if (!$this->has_personal_reward_before_join(
            $userid,
            $jointimecreated,
            $xpcontextid,
            $ruleid,
            $reason,
            $subtype,
            $envid,
            $parentid,
            $objectid,
            $scope
        )) {
            return null;
        }

        // If this exact Level Up source already produced personal XP after the
        // learner joined the promotion, M5.2 already mirrored it. A replay must
        // not add an extra team-only reward.
        if ($this->has_personal_reward_since_join(
            $userid,
            $jointimecreated,
            $xpcontextid,
            $ruleid,
            $reason,
            $subtype,
            $envid,
            $parentid,
            $objectid,
            $scope
        )) {
            return null;
        }

        $sourcekey = $this->source_key(
            $ruleid,
            $reason,
            $subtype,
            $envid,
            $parentid,
            $objectid,
            $scope
        );

        return $this->xpservice->record_once(
            $promotionid,
            $userid,
            'block_xp',
            'reason_limit_replay',
            $sourcekey,
            $points,
            $timeearned
        );
    }

    private function has_personal_reward_before_join(
        int $userid,
        int $jointimecreated,
        int $xpcontextid,
        int $ruleid,
        string $reason,
        ?string $subtype,
        ?int $envid,
        ?int $parentid,
        ?int $objectid,
        int $scope
    ): bool {
        return $this->has_matching_personal_reward(
            $userid,
            $xpcontextid,
            $ruleid,
            $reason,
            $subtype,
            $envid,
            $parentid,
            $objectid,
            $scope,
            'timerecorded < :jointimecreated',
            $jointimecreated
        );
    }

    private function has_personal_reward_since_join(
        int $userid,
        int $jointimecreated,
        int $xpcontextid,
        int $ruleid,
        string $reason,
        ?string $subtype,
        ?int $envid,
        ?int $parentid,
        ?int $objectid,
        int $scope
    ): bool {
        return $this->has_matching_personal_reward(
            $userid,
            $xpcontextid,
            $ruleid,
            $reason,
            $subtype,
            $envid,
            $parentid,
            $objectid,
            $scope,
            'timerecorded >= :jointimecreated',
            $jointimecreated
        );
    }

    private function has_matching_personal_reward(
        int $userid,
        int $xpcontextid,
        int $ruleid,
        string $reason,
        ?string $subtype,
        ?int $envid,
        ?int $parentid,
        ?int $objectid,
        int $scope,
        string $timecondition,
        int $jointimecreated
    ): bool {
        $conditions = [
            'contextid = :contextid',
            'userid = :userid',
            'ruleid = :ruleid',
            'reason = :reason',
            'points > 0',
            $timecondition,
        ];
        $params = [
            'contextid' => $xpcontextid,
            'userid' => $userid,
            'ruleid' => $ruleid,
            'reason' => $reason,
            'jointimecreated' => $jointimecreated,
        ];

        if ($subtype === null) {
            $conditions[] = 'subtype IS NULL';
        } else {
            $conditions[] = 'subtype = :subtype';
            $params['subtype'] = $subtype;
        }

        $this->append_tracking_condition($conditions, $params, 'envid', $envid, $scope, limit_spec::SCOPE_ENV);
        $this->append_tracking_condition($conditions, $params, 'parentid', $parentid, $scope, limit_spec::SCOPE_PARENT);
        $this->append_tracking_condition($conditions, $params, 'objectid', $objectid, $scope, limit_spec::SCOPE_OBJECT);

        return $this->db->record_exists_select(
            'block_xp_logs',
            implode(' AND ', $conditions),
            $params
        );
    }

    /** @param string[] $conditions @param array<string,mixed> $params */
    private function append_tracking_condition(
        array &$conditions,
        array &$params,
        string $field,
        ?int $value,
        int $scope,
        int $flag
    ): void {
        // This mirrors Level Up's own reason-limit matching: scoped tracking
        // fields with null values are intentionally omitted from the filter.
        if (($scope & $flag) === 0 || $value === null) {
            return;
        }
        $conditions[] = "{$field} = :{$field}";
        $params[$field] = $value;
    }

    private function source_key(
        int $ruleid,
        string $reason,
        ?string $subtype,
        ?int $envid,
        ?int $parentid,
        ?int $objectid,
        int $scope
    ): string {
        $identity = [
            'rule=' . $ruleid,
            'reason=' . $reason,
            'subtype=' . ($subtype ?? ''),
            'scope=' . $scope,
        ];
        if (($scope & limit_spec::SCOPE_ENV) !== 0 && $envid !== null) {
            $identity[] = 'env=' . $envid;
        }
        if (($scope & limit_spec::SCOPE_PARENT) !== 0 && $parentid !== null) {
            $identity[] = 'parent=' . $parentid;
        }
        if (($scope & limit_spec::SCOPE_OBJECT) !== 0 && $objectid !== null) {
            $identity[] = 'object=' . $objectid;
        }
        return 'limit-replay:' . hash('sha256', implode('|', $identity));
    }

    private function validate(
        int $userid,
        int $points,
        int $timeearned,
        int $xpcontextid,
        int $ruleid,
        string $reason,
        int $scope
    ): void {
        if ($userid <= 0 || $points <= 0 || $timeearned <= 0 || $xpcontextid <= 0 || $ruleid <= 0) {
            throw new \coding_exception('Invalid Level Up replay bridge identifiers.');
        }
        if (trim($reason) === '') {
            throw new \coding_exception('Level Up replay bridge requires a reason name.');
        }
        $validscope = limit_spec::SCOPE_ENV | limit_spec::SCOPE_PARENT | limit_spec::SCOPE_OBJECT;
        if ($scope < 0 || ($scope & ~$validscope) !== 0) {
            throw new \coding_exception('Invalid Level Up replay reason scope.');
        }
    }
}
