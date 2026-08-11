<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\statistics;

defined('MOODLE_INTERNAL') || die();

/**
 * Request-local cache for Commerce statistics.
 *
 * This deliberately avoids a persistent Moodle cache definition: statistics stay fresh between
 * requests while duplicate aggregates and chart queries inside one dashboard request are removed.
 */
final class CommerceStatisticsRequestCache {
    /** @var array<string,mixed> */
    private array $values = [];

    public function remember(string $key, callable $loader): mixed {
        if (!array_key_exists($key, $this->values)) {
            $this->values[$key] = $loader();
        }

        return $this->values[$key];
    }

    public function clear(): void {
        $this->values = [];
    }

    public function count(): int {
        return count($this->values);
    }
}
