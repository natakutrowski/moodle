<?php

declare(strict_types=1);

namespace local_subscriptions\trial;

defined('MOODLE_INTERNAL') || die();

/**
 * Authoritative Trial duration policy for 7.98D5.
 *
 * local_subscriptions/trial_duration_days is the only runtime source of truth.
 * The historical local_campus/trialdays setting is intentionally ignored.
 * A value of 0 preserves the historical "no practical expiry" behaviour by
 * producing a long-lived end date; negative/invalid values fall back to 7 days.
 */
final class TrialDurationPolicy {
    public const CONFIG_KEY = 'trial_duration_days';
    public const DEFAULT_DAYS = 7;
    private const LONG_LIVED_SECONDS = 3155760000; // 100 * 365.25 days.

    /** @var array<string, scalar|null>|null */
    private ?array $configuration;

    /**
     * @param array<string, scalar|null>|null $configuration Deterministic config for tests.
     */
    public function __construct(?array $configuration = null) {
        $this->configuration = $configuration;
    }

    public static function create(): self {
        return new self();
    }

    public function configured_days(): int {
        $raw = $this->raw(self::CONFIG_KEY);
        if ($raw === '') {
            return self::DEFAULT_DAYS;
        }

        $days = (int)$raw;
        return $days < 0 ? self::DEFAULT_DAYS : $days;
    }

    public function end_at(int $start): int {
        $days = $this->configured_days();
        if ($days === 0) {
            return $start + self::LONG_LIVED_SECONDS;
        }

        return $start + max(1, $days) * DAYSECS;
    }

    /** @return array{component:string,key:string,days:int} */
    public function snapshot(): array {
        return [
            'component' => 'local_subscriptions',
            'key' => self::CONFIG_KEY,
            'days' => $this->configured_days(),
        ];
    }

    private function raw(string $key): string {
        if ($this->configuration !== null) {
            return trim((string)($this->configuration[$key] ?? ''));
        }

        $value = get_config('local_subscriptions', $key);
        return $value === false ? '' : trim((string)$value);
    }
}
