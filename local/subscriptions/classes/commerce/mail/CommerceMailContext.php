<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable business context passed to a transactional mail template.
 */
final class CommerceMailContext {

    public function __construct(
        private readonly array $values = []
    ) {
        self::assert_serialisable($values);
    }

    public function has(string $key): bool {
        return array_key_exists($key, $this->values);
    }

    public function get(string $key, mixed $default = null): mixed {
        return $this->values[$key] ?? $default;
    }

    public function require(string $key): mixed {
        if (!$this->has($key)) {
            throw new \coding_exception(
                'Missing required Commerce transactional mail context value: ' . $key
            );
        }

        return $this->values[$key];
    }

    public function all(): array {
        return $this->values;
    }

    public function with(string $key, mixed $value): self {
        $values = $this->values;
        $values[$key] = $value;

        return new self($values);
    }

    private static function assert_serialisable(mixed $value, string $path = 'context'): void {
        if (
            $value === null
            || is_scalar($value)
        ) {
            return;
        }

        if (!is_array($value)) {
            throw new \coding_exception(
                'Commerce transactional mail context values must be JSON-serialisable at ' . $path . '.'
            );
        }

        foreach ($value as $key => $item) {
            self::assert_serialisable(
                $item,
                $path . '.' . (string)$key
            );
        }
    }
}
