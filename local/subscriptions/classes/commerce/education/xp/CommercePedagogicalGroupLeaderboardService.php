<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\xp;

defined('MOODLE_INTERNAL') || die();

/**
 * Promotion-isolated group ranking derived from the current group membership.
 *
 * Contributions do not store a group id. Moving a student therefore moves all
 * of their promotion points to their current group without rewriting history.
 */
final class CommercePedagogicalGroupLeaderboardService {
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
     * @return CommercePedagogicalGroupLeaderboardEntry[]
     */
    public function get_ranking(int $promotionid): array {
        $groups = $this->db->get_records(
            'local_subs_commerce_ped_group',
            ['promotionid' => $promotionid, 'active' => 1],
            'position ASC, id ASC',
            'id,displayname,position'
        );
        if (!$groups) {
            return [];
        }

        $groupids = array_map('intval', array_keys($groups));
        [$insql, $inparams] = $this->db->get_in_or_equal($groupids, SQL_PARAMS_NAMED, 'pedxpgroup');
        $memberships = $this->db->get_records_sql(
            "SELECT m.id, m.groupid, m.userid
               FROM {local_subs_commerce_ped_gmem} m
              WHERE m.active = 1
                AND m.groupid {$insql}",
            $inparams
        );

        $activeusers = [];
        $activejoins = $this->db->get_records(
            'local_subs_commerce_ped_join',
            ['promotionid' => $promotionid, 'state' => 'active'],
            '',
            'id,userid'
        );
        foreach ($activejoins as $join) {
            $activeusers[(int)$join->userid] = true;
        }

        $userpoints = $this->repository->points_by_user($promotionid);
        $pointsbygroup = array_fill_keys($groupids, 0);
        $membersbygroup = array_fill_keys($groupids, 0);

        foreach ($memberships as $membership) {
            $userid = (int)$membership->userid;
            $groupid = (int)$membership->groupid;
            if (!isset($activeusers[$userid]) || !array_key_exists($groupid, $pointsbygroup)) {
                continue;
            }
            $membersbygroup[$groupid]++;
            $pointsbygroup[$groupid] += $userpoints[$userid] ?? 0;
        }

        $rows = [];
        foreach ($groups as $group) {
            $groupid = (int)$group->id;
            $rows[] = [
                'groupid' => $groupid,
                'displayname' => (string)$group->displayname,
                'position' => (int)$group->position,
                'points' => (int)$pointsbygroup[$groupid],
                'membercount' => (int)$membersbygroup[$groupid],
            ];
        }

        usort($rows, static function(array $a, array $b): int {
            if ($a['points'] !== $b['points']) {
                return $b['points'] <=> $a['points'];
            }
            if ($a['position'] !== $b['position']) {
                return $a['position'] <=> $b['position'];
            }
            return $a['groupid'] <=> $b['groupid'];
        });

        $result = [];
        $lastrank = 0;
        $lastpoints = null;
        foreach ($rows as $index => $row) {
            if ($lastpoints === null || $row['points'] !== $lastpoints) {
                $lastrank = $index + 1;
                $lastpoints = $row['points'];
            }
            $result[] = new CommercePedagogicalGroupLeaderboardEntry(
                $row['groupid'],
                $row['displayname'],
                $row['position'],
                $row['points'],
                $row['membercount'],
                $lastrank
            );
        }

        return $result;
    }
}
