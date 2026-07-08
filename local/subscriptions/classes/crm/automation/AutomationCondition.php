<?php

namespace local_subscriptions\crm\automation;

defined('MOODLE_INTERNAL') || die();

final class AutomationCondition {

    public function __construct(
        public readonly string $key,
        public readonly array $payload = [],
        public readonly bool $negated = false
    ) {
    }

    public static function make(string $key, array $payload = [], bool $negated = false): self {
        return new self($key, $payload, $negated);
    }

    public function to_array(): array {
        return [
            'key' => $this->key,
            'payload' => $this->payload,
            'negated' => $this->negated,
        ];
    }
}