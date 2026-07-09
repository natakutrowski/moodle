<?php

namespace local_subscriptions\crm\intelligence\opportunities;

defined('MOODLE_INTERNAL') || die();

final class Opportunity {

    public function __construct(
        public readonly string $key,
        public readonly int $priority = 50
    ) {
    }

    public function to_object(): \stdClass {
        return (object)[
            'key' => $this->key,
            'priority' => $this->priority,
        ];
    }
}