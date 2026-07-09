<?php

namespace local_subscriptions\crm\intelligence\alerts;

defined('MOODLE_INTERNAL') || die();

final class CrmAlert {

    public function __construct(
        public readonly string $key,
        public readonly string $severity = 'info',
        public readonly int $priority = 50,
        public readonly ?int $userid = null
    ) {
    }

    public function to_object(): \stdClass {
        return (object)[
            'key' => $this->key,
            'severity' => $this->severity,
            'priority' => $this->priority,
            'userid' => $this->userid,
        ];
    }
}