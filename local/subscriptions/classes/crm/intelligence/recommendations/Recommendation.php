<?php

namespace local_subscriptions\crm\intelligence\recommendations;

defined('MOODLE_INTERNAL') || die();

final class Recommendation {

    public function __construct(
        public readonly string $key,
        public readonly string $type = 'info',
        public readonly int $priority = 50,
        public readonly ?RecommendationAction $action = null
    ) {
    }

    public function to_object(): \stdClass {
        return (object)[
            'key' => $this->key,
            'type' => $this->type,
            'priority' => $this->priority,
            'action' => $this->action?->to_object(),
        ];
    }
}