<?php

namespace local_subscriptions\crm\intelligence\scoring;

defined('MOODLE_INTERNAL') || die();

final class LeadScore {

    public function __construct(
        public readonly int $commercial,
        public readonly int $engagement,
        public readonly int $risk,
        public readonly array $reasons = []
    ) {
    }

    public function global(): int {
        return max(0, min(100, (int)round(
            ($this->commercial * 0.45) +
            ($this->engagement * 0.35) -
            ($this->risk * 0.20)
        )));
    }

    public function to_object(): \stdClass {
        return (object)[
            'commercial' => $this->commercial,
            'engagement' => $this->engagement,
            'risk' => $this->risk,
            'global' => $this->global(),
            'level' => $this->level(),
            'reasons' => $this->reasons,
        ];
    }

    public function level(): string {
        return LeadScoreLevel::from_score($this->global());
    }

}