<?php

namespace local_subscriptions\crm\intelligence\segmentation;

defined('MOODLE_INTERNAL') || die();

final class Segment {
    public function __construct(
        public readonly string $key
    ) {
    }

    public function to_object(): \stdClass {
        return (object)['key' => $this->key];
    }
}