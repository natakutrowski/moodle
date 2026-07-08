<?php

namespace local_subscriptions\crm\automation;

defined('MOODLE_INTERNAL') || die();

final class AutomationAction {

    public function __construct(
        public readonly string $key,
        public readonly array $payload = [],
        public readonly bool $stoponfailure = true
    ) {
    }

    public static function make(string $key, array $payload = [], bool $stoponfailure = true): self {
        return new self($key, $payload, $stoponfailure);
    }

    public function to_array(): array {
        return [
            'key' => $this->key,
            'payload' => $this->payload,
            'stoponfailure' => $this->stoponfailure,
        ];
    }
}