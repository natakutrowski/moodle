<?php

namespace local_subscriptions\crm\intelligence\priority;

defined('MOODLE_INTERNAL') || die();

final class DailyPriority {

    public function __construct(
        public readonly int $userid,
        public readonly string $key,
        public readonly int $score,
        public readonly string $action = 'open_user_profile'
    ) {
    }

    public function to_object(): \stdClass {
        return (object)[
            'userid' => $this->userid,
            'key' => $this->key,
            'score' => $this->score,
            'action' => $this->action,
        ];
    }
}