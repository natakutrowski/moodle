<?php

namespace local_subscriptions\commandcenter\intents;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commandcenter\CommandResult;

final class CommandIntentMatch {

    private CommandResult $result;
    private int $score;

    private function __construct(CommandResult $result, int $score) {
        $this->result = $result;
        $this->score = $score;
    }

    public static function create(CommandResult $result, int $score = 1000): self {
        return new self($result, $score);
    }

    public function result(): CommandResult {
        return $this->result;
    }

    public function score(): int {
        return $this->score;
    }
}