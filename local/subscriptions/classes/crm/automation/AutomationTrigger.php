<?php

namespace local_subscriptions\crm\automation;

defined('MOODLE_INTERNAL') || die();

final class AutomationTrigger {

    public function __construct(
        public readonly string $key,
        public readonly array $payload = []
    ) {
    }

    public static function make(string $key, array $payload = []): self {
        return new self($key, $payload);
    }

    public function to_array(): array {
        return [
            'key' => $this->key,
            'payload' => $this->payload,
        ];
    }
}