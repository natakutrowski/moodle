<?php

namespace local_subscriptions\crm\work\intelligence\dto;

defined('MOODLE_INTERNAL') || die();

/**
 * Suggested Work Management team.
 */
final class WorkTeamSuggestion {

    public function __construct(
        public readonly int $teamid,
        public readonly string $teamname,
        public readonly int $score,
        public readonly int $activeworkload,
        public readonly array $reasons = []
    ) {
        if ($this->teamid <= 0) {
            throw new \InvalidArgumentException(
                'Suggested Work Team ID must be greater than zero.'
            );
        }

        if ($this->score < 0 || $this->score > 100) {
            throw new \InvalidArgumentException(
                'Work Team suggestion score must be between 0 and 100.'
            );
        }

        if ($this->activeworkload < 0) {
            throw new \InvalidArgumentException(
                'Work Team active workload cannot be negative.'
            );
        }
    }

    public function to_object(): \stdClass {
        return (object)[
            'teamid' => $this->teamid,
            'teamname' => $this->teamname,
            'score' => $this->score,
            'activeworkload' =>
                $this->activeworkload,
            'reasons' => $this->reasons,
        ];
    }
}