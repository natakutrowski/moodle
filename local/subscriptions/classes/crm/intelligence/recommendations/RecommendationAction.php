<?php

namespace local_subscriptions\crm\intelligence\recommendations;

defined('MOODLE_INTERNAL') || die();

final class RecommendationAction {

    public function __construct(
        public readonly string $key,
        public readonly string $action,
        public readonly array $params = []
    ) {
    }

    public function to_object(): \stdClass {
        return (object)[
            'key' => $this->key,
            'action' => $this->action,
            'params' => $this->params,
        ];
    }
}