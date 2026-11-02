<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\xp;

defined('MOODLE_INTERNAL') || die();

/** Pure CRM ordering and competition ranks, shared by groups and participants. */
final class CommercePedagogicalCrmRanking {
    /** Sort group domain objects by promotion score, without changing the saved group positions. */
    public static function groups(array $groups, array $entries): array {
        usort($groups, static function($a, $b) use ($entries): int {
            $aid = (int)$a->get_id();
            $bid = (int)$b->get_id();
            $ascore = isset($entries[$aid]) ? (int)$entries[$aid]->get_points() : 0;
            $bscore = isset($entries[$bid]) ? (int)$entries[$bid]->get_points() : 0;
            return ($bscore <=> $ascore)
                ?: ($a->get_position() <=> $b->get_position())
                ?: ($aid <=> $bid);
        });
        return $groups;
    }

    /** Score ties share a rank. Zero-point participants have no podium rank. */
    public static function user_ranks(array $participants, array $points): array {
        $ordered = self::participants($participants, $points, [], 'xp');
        $ranks = [];
        $previous = null;
        $rank = 0;
        foreach ($ordered as $index => $participant) {
            $uid = (int)$participant->userid;
            $score = (int)($points[$uid] ?? 0);
            if ($score <= 0) {
                $ranks[$uid] = 0;
                continue;
            }
            if ($previous === null || $score !== $previous) {
                $rank = $index + 1;
                $previous = $score;
            }
            $ranks[$uid] = $rank;
        }
        return $ranks;
    }

    /** @param array<int,\stdClass> $participants */
    public static function participants(array $participants, array $points, array $joins, string $sort): array {
        if (!in_array($sort, ['xp', 'joined', 'group'], true)) {
            $sort = 'xp';
        }
        usort($participants, static function($a, $b) use ($points, $joins, $sort): int {
            $aid = (int)$a->userid;
            $bid = (int)$b->userid;
            $apoints = (int)($points[$aid] ?? 0);
            $bpoints = (int)($points[$bid] ?? 0);
            $asince = (int)($joins[$aid]->timecreated ?? $a->timecreated ?? 0);
            $bsince = (int)($joins[$bid]->timecreated ?? $b->timecreated ?? 0);
            $agroup = trim((string)($a->groupname ?? ''));
            $bgroup = trim((string)($b->groupname ?? ''));
            if ($sort === 'joined') {
                $primary = $bsince <=> $asince; // Newest enrolment first.
            } else if ($sort === 'group') {
                $primary = ($agroup === '' ? 1 : 0) <=> ($bgroup === '' ? 1 : 0);
                $primary = $primary ?: strnatcasecmp($agroup, $bgroup);
            } else {
                $primary = $bpoints <=> $apoints;
            }
            return $primary ?: ($bpoints <=> $apoints) ?: ($aid <=> $bid);
        });
        return $participants;
    }
}
