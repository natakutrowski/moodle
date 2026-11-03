<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\xp;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\education\group\CommercePedagogicalGroupRepository;
use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionStatus;

/**
 * Builds the learner-facing team challenges for current pedagogical promotions.
 */
final class CommercePedagogicalStudentLeaderboardService {
    private const VISIBLE_ROWS = 5;

    private const CURRENT_STATUSES = [
        CommercePedagogicalPromotionStatus::SCHEDULED,
        CommercePedagogicalPromotionStatus::OPEN,
        CommercePedagogicalPromotionStatus::FULL,
        CommercePedagogicalPromotionStatus::STARTED,
    ];

    public function __construct(
        private readonly \moodle_database $db,
        private readonly CommercePedagogicalGroupRepository $groups,
        private readonly CommercePedagogicalGroupLeaderboardService $leaderboard,
        private readonly CommercePedagogicalXpRepository $xp
    ) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        $db = $db ?? $DB;
        return new self(
            $db,
            CommercePedagogicalGroupRepository::create($db),
            CommercePedagogicalGroupLeaderboardService::create($db),
            CommercePedagogicalXpRepository::create($db)
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function get_for_user(int $userid, ?int $now = null): array {
        if ($userid <= 0) {
            return [];
        }
        $now ??= time();

        [$statussql, $statusparams] = $this->db->get_in_or_equal(
            self::CURRENT_STATUSES,
            SQL_PARAMS_NAMED,
            'pedteamstatus'
        );
        $params = [
            'userid' => $userid,
            'joinstate' => 'active',
            'now' => $now,
        ] + $statusparams;

        // ped_access is the learner's current relation for a course. Requiring
        // the matching active join keeps historical/revoked promotions out.
        $relations = $this->db->get_records_sql(
            "SELECT pa.id,
                    pa.courseid,
                    pa.promotionid,
                    pp.name AS promotionname,
                    pp.startsat,
                    pp.xpstartsat,
                    pp.leaderboardhidehours,
                    pp.timecreated
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
                AND (pp.endsat IS NULL OR pp.endsat >= :now)
           ORDER BY COALESCE(pp.startsat, pp.timecreated) DESC, pp.id DESC",
            $params
        );

        $result = [];
        $seen = [];
        foreach ($relations as $relation) {
            $promotionid = (int)$relation->promotionid;
            if ($promotionid <= 0 || isset($seen[$promotionid])) {
                continue;
            }
            $seen[$promotionid] = true;

            $group = $this->groups->group_for_user($promotionid, $userid);
            if ($group === null) {
                continue;
            }

            $revealat = ($relation->xpstartsat !== null && (int)$relation->leaderboardhidehours > 0)
                ? (int)$relation->xpstartsat + (int)$relation->leaderboardhidehours * HOURSECS
                : 0;
            if ($revealat > $now) {
                $result[] = [
                    'promotionid' => $promotionid,
                    'promotionname' => format_string((string)$relation->promotionname),
                    'groupid' => (int)$group->get_id(),
                    'groupname' => format_string($group->get_display_name()),
                    'isleaderboardhidden' => true,
                    // Personal XP are never a group ranking and remain visible during the reveal delay.
                    'userpoints' => $this->xp->user_points($promotionid, $userid),
                    'historyurl' => new \moodle_url('/local/subscriptions/my_promotion_xp.php',
                        ['promotionid' => $promotionid])->out(false),
                    'revealdate' => userdate($revealat, get_string('strftimedatetime', 'langconfig')),
                    'isxpnotstarted' => $relation->xpstartsat !== null && $now < (int)$relation->xpstartsat,
                    'isxpstarted' => $relation->xpstartsat === null || $now >= (int)$relation->xpstartsat,
                    'xpstartdate' => $relation->xpstartsat !== null
                        ? userdate((int)$relation->xpstartsat, get_string('strftimedatetime', 'langconfig'))
                        : '',
                    // No points, ranks or other groups are sent to the learner.
                ];
                continue;
            }

            $ranking = $this->leaderboard->get_ranking($promotionid);
            if (!$ranking) {
                continue;
            }

            $mygroupid = (int)$group->get_id();
            $mine = null;
            foreach ($ranking as $entry) {
                if ($entry->get_group_id() === $mygroupid) {
                    $mine = $entry;
                    break;
                }
            }
            if ($mine === null) {
                continue;
            }

            $rows = [];
            $mineincluded = false;
            foreach (array_slice($ranking, 0, self::VISIBLE_ROWS) as $entry) {
                $ismine = $entry->get_group_id() === $mygroupid;
                $mineincluded = $mineincluded || $ismine;
                $rows[] = $this->row($entry, $ismine, false);
            }
            if (!$mineincluded) {
                $rows[] = $this->row($mine, true, true);
            }

            $result[] = [
                'promotionid' => $promotionid,
                'promotionname' => format_string((string)$relation->promotionname),
                'groupid' => $mygroupid,
                'groupname' => format_string($group->get_display_name()),
                'grouppoints' => $mine->get_points(),
                'grouprank' => $mine->get_rank(),
                'mygold' => $mine->get_points() > 0 && $mine->get_rank() === 1,
                'mysilver' => $mine->get_points() > 0 && $mine->get_rank() === 2,
                'mybronze' => $mine->get_points() > 0 && $mine->get_rank() === 3,
                'groupmembers' => $mine->get_member_count(),
                'userpoints' => $this->xp->user_points($promotionid, $userid),
                'historyurl' => new \moodle_url('/local/subscriptions/my_promotion_xp.php',
                    ['promotionid' => $promotionid])->out(false),
                'leaderboard' => $rows,
            ];
        }

        return $result;
    }

    /** @return array<string,mixed> */
    private function row(
        CommercePedagogicalGroupLeaderboardEntry $entry,
        bool $ismine,
        bool $separated
    ): array {
        return [
            'groupid' => $entry->get_group_id(),
            'groupname' => format_string($entry->get_display_name()),
            'points' => $entry->get_points(),
            'members' => $entry->get_member_count(),
            'rank' => $entry->get_rank(),
            'isgold' => $entry->get_points() > 0 && $entry->get_rank() === 1,
            'issilver' => $entry->get_points() > 0 && $entry->get_rank() === 2,
            'isbronze' => $entry->get_points() > 0 && $entry->get_rank() === 3,
            'ismine' => $ismine,
            'separated' => $separated,
        ];
    }
}
