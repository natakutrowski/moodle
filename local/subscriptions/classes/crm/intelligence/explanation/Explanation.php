<?php

namespace local_subscriptions\crm\intelligence\explanation;

defined('MOODLE_INTERNAL') || die();

final class Explanation {

    public function __construct(
        public readonly string $key,
        public readonly string $category,
        public readonly int $impact
    ) {
    }

    public function to_object(): \stdClass {
        return (object)[
            'key' => $this->key,
            'category' => $this->category,
            'impact' => $this->impact,
        ];
    }
}