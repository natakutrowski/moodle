<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\xp;

defined('MOODLE_INTERNAL') || die();

/** One row in a promotion team leaderboard. */
final class CommercePedagogicalGroupLeaderboardEntry {
    public function __construct(
        private readonly int $groupid,
        private readonly string $displayname,
        private readonly int $position,
        private readonly int $points,
        private readonly int $membercount,
        private readonly int $rank
    ) {}

    public function get_group_id(): int { return $this->groupid; }
    public function get_display_name(): string { return $this->displayname; }
    public function get_position(): int { return $this->position; }
    public function get_points(): int { return $this->points; }
    public function get_member_count(): int { return $this->membercount; }
    public function get_rank(): int { return $this->rank; }
}
