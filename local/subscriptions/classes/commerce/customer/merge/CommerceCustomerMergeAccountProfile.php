<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\customer\merge;

defined('MOODLE_INTERNAL') || die();

/**
 * Read-only profile used to compare merge candidates.
 */
final class CommerceCustomerMergeAccountProfile {
    public function __construct(
        public readonly \stdClass $user,
        public readonly int $enrolledcourses,
        public readonly int $completedcourses,
        public readonly int $completedactivities,
        public readonly int $gradecount,
        public readonly float $averagegradepercent,
        public readonly int $purchases,
        public readonly int $grants,
        public readonly int $digitalaccesses,
        public readonly int $guestsessions,
        public readonly int $levelupxp = 0,
        public readonly int $levelupquests = 0
    ) {
    }

    public function userid(): int {
        return (int)$this->user->id;
    }

    public function pedagogical_score(): int {
        return
            ($this->completedcourses * 500)
            + ($this->completedactivities * 10)
            + ($this->enrolledcourses * 50)
            + $this->gradecount
            + (int)round($this->averagegradepercent)
            + (int)floor($this->levelupxp / 10)
            + ($this->levelupquests * 25);
    }

    public function commerce_score(): int {
        return
            ($this->purchases * 20)
            + ($this->grants * 10)
            + ($this->digitalaccesses * 10)
            + ($this->guestsessions * 5);
    }

    public function has_pedagogical_history(): bool {
        return
            $this->enrolledcourses > 0
            || $this->completedcourses > 0
            || $this->completedactivities > 0
            || $this->gradecount > 0
            || $this->levelupxp > 0
            || $this->levelupquests > 0;
    }
}
