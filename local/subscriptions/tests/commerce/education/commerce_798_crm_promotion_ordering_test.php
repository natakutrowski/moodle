<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\education\xp\CommercePedagogicalCrmRanking;

/** Ranking and sorting must not require a DB write or alter stored group positions. */
final class commerce_798_crm_promotion_ordering_test extends \advanced_testcase {
    public function test_member_ordering_and_competition_ties(): void {
        $participants = [
            (object)['userid' => 3, 'groupname' => 'B', 'timecreated' => 3],
            (object)['userid' => 2, 'groupname' => 'A', 'timecreated' => 2],
            (object)['userid' => 1, 'groupname' => 'B', 'timecreated' => 1],
            (object)['userid' => 4, 'groupname' => null, 'timecreated' => 4],
        ];
        $points = [1 => 5, 2 => 12, 3 => 12, 4 => 0];
        $ranks = CommercePedagogicalCrmRanking::user_ranks($participants, $points);
        self::assertSame([2 => 1, 3 => 1, 1 => 3, 4 => 0], $ranks);
        $xp = CommercePedagogicalCrmRanking::participants($participants, $points, [], 'xp');
        self::assertSame([2, 3, 1, 4], array_map(static fn($u) => $u->userid, $xp));
        $joined = CommercePedagogicalCrmRanking::participants($participants, $points, [], 'joined');
        self::assertSame([4, 3, 2, 1], array_map(static fn($u) => $u->userid, $joined));
        $bygroup = CommercePedagogicalCrmRanking::participants($participants, $points, [], 'group');
        self::assertSame([2, 3, 1, 4], array_map(static fn($u) => $u->userid, $bygroup));
    }

    public function test_group_sort_preserves_saved_positions(): void {
        $make = static function(int $id, int $position) {
            return new class($id, $position) {
                public function __construct(private int $id, private int $position) {}
                public function get_id(): int { return $this->id; }
                public function get_position(): int { return $this->position; }
            };
        };
        $groups = [$make(8, 0), $make(9, 1), $make(10, 2)];
        $entry = static function(int $score) {
            return new class($score) {
                public function __construct(private int $score) {}
                public function get_points(): int { return $this->score; }
            };
        };
        $ordered = CommercePedagogicalCrmRanking::groups($groups, [8 => $entry(0), 9 => $entry(15), 10 => $entry(15)]);
        self::assertSame([9, 10, 8], array_map(static fn($g) => $g->get_id(), $ordered));
        self::assertSame([0, 1, 2], array_map(static fn($g) => $g->get_position(), $groups));
    }
}
