<?php

declare(strict_types=1);

namespace local_subscriptions\trial;

defined('MOODLE_INTERNAL') || die();

/**
 * Authoritative calendar for the progressive shutdown of the Legacy Trial.
 *
 * 7.98D1 only exposes the policy and its diagnostics. Enforcement is wired in
 * the following phases so deploying D1 cannot unexpectedly close registrations.
 *
 * Configuration values are stored as local Moodle-site datetimes using the
 * explicit format YYYY-MM-DD HH:MM. Empty values preserve the historical open
 * behaviour. Unix timestamps are accepted as a compatibility input for tools.
 */
final class TrialLegacyTransitionPolicy {
    public const CONFIG_REGISTRATION_CLOSE_AT = 'trial_registration_close_at';
    public const CONFIG_LEGACY_EXPIRE_AT = 'trial_legacy_expire_at';

    public const PHASE_REGISTRATION_OPEN = 'registration_open';
    public const PHASE_CONVERSION_ONLY = 'conversion_only';
    public const PHASE_LEGACY_CLOSED = 'legacy_closed';

    /** @var array<string, scalar|null>|null */
    private ?array $configuration;
    private \DateTimeZone $timezone;

    /**
     * @param array<string, scalar|null>|null $configuration Optional deterministic config for tests/tools.
     */
    public function __construct(
        ?array $configuration = null,
        ?\DateTimeZone $timezone = null
    ) {
        $this->configuration = $configuration;
        $this->timezone = $timezone ?? \core_date::get_server_timezone_object();
    }

    public static function create(): self {
        return new self();
    }

    public function registration_close_at(): ?int {
        return $this->configured_timestamp(self::CONFIG_REGISTRATION_CLOSE_AT);
    }

    public function legacy_expire_at(): ?int {
        return $this->configured_timestamp(self::CONFIG_LEGACY_EXPIRE_AT);
    }

    public function registrations_are_open(?int $at = null): bool {
        $at ??= time();
        $legacyexpireat = $this->legacy_expire_at();
        if ($legacyexpireat !== null && $at >= $legacyexpireat) {
            return false;
        }

        $closeat = $this->registration_close_at();
        return $closeat === null || $at < $closeat;
    }

    /**
     * Global Legacy conversion window only.
     *
     * User-level conversion eligibility is resolved separately by
     * TrialLegacyConversionEligibilityService; this policy remains the single
     * authority for the global Date A / Date B calendar.
     */
    public function conversion_window_is_open(?int $at = null): bool {
        $at ??= time();
        $legacyexpireat = $this->legacy_expire_at();
        return $legacyexpireat === null || $at < $legacyexpireat;
    }

    public function legacy_window_is_closed(?int $at = null): bool {
        return !$this->conversion_window_is_open($at);
    }

    public function phase(?int $at = null): string {
        $at ??= time();

        if ($this->legacy_window_is_closed($at)) {
            return self::PHASE_LEGACY_CLOSED;
        }
        if ($this->registrations_are_open($at)) {
            return self::PHASE_REGISTRATION_OPEN;
        }

        return self::PHASE_CONVERSION_ONLY;
    }

    /**
     * Returns a serialisable policy snapshot suitable for CLI/admin diagnostics.
     *
     * @return array<string, mixed>
     */
    public function snapshot(?int $at = null): array {
        $at ??= time();
        $registrationcloseat = $this->registration_close_at();
        $legacyexpireat = $this->legacy_expire_at();

        return [
            'at' => $at,
            'timezone' => $this->timezone->getName(),
            'phase' => $this->phase($at),
            'registrations_open' => $this->registrations_are_open($at),
            'conversion_window_open' => $this->conversion_window_is_open($at),
            'legacy_window_closed' => $this->legacy_window_is_closed($at),
            'registration_close_at' => $registrationcloseat,
            'legacy_expire_at' => $legacyexpireat,
            'registration_close_raw' => $this->raw(self::CONFIG_REGISTRATION_CLOSE_AT),
            'legacy_expire_raw' => $this->raw(self::CONFIG_LEGACY_EXPIRE_AT),
        ];
    }

    /**
     * Checks the calendar without changing runtime behaviour.
     *
     * @return array<int, array{severity:string,code:string,message:string}>
     */
    public function configuration_issues(): array {
        $issues = [];
        $registrationraw = trim($this->raw(self::CONFIG_REGISTRATION_CLOSE_AT));
        $legacyraw = trim($this->raw(self::CONFIG_LEGACY_EXPIRE_AT));
        $registrationcloseat = $this->registration_close_at();
        $legacyexpireat = $this->legacy_expire_at();

        if ($registrationraw !== '' && $registrationcloseat === null) {
            $issues[] = $this->issue(
                'error',
                'invalid_registration_close_at',
                'Trial registration close date is invalid; expected YYYY-MM-DD HH:MM.'
            );
        }
        if ($legacyraw !== '' && $legacyexpireat === null) {
            $issues[] = $this->issue(
                'error',
                'invalid_legacy_expire_at',
                'Legacy Trial expiry date is invalid; expected YYYY-MM-DD HH:MM.'
            );
        }

        if ($registrationraw === '' && $legacyraw !== '') {
            $issues[] = $this->issue(
                'warning',
                'registration_close_at_missing',
                'Legacy expiry is configured but the registration close date is empty.'
            );
        }
        if ($registrationraw !== '' && $legacyraw === '') {
            $issues[] = $this->issue(
                'warning',
                'legacy_expire_at_missing',
                'Trial registrations have a close date but the Legacy expiry date is empty.'
            );
        }

        if (
            $registrationcloseat !== null &&
            $legacyexpireat !== null &&
            $legacyexpireat <= $registrationcloseat
        ) {
            $issues[] = $this->issue(
                'error',
                'legacy_expire_not_after_registration_close',
                'Legacy Trial expiry must be strictly later than the registration close date.'
            );
        }

        return $issues;
    }

    /**
     * Parses a transition datetime using the Moodle site timezone.
     *
     * Public for deterministic CLI tooling and certification tests.
     */
    public function parse_datetime(string $value): ?int {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (ctype_digit($value)) {
            $timestamp = (int)$value;
            return $timestamp > 0 ? $timestamp : null;
        }

        foreach (['!Y-m-d H:i', '!Y-m-d\\TH:i', '!Y-m-d H:i:s', '!Y-m-d\\TH:i:s'] as $format) {
            $datetime = \DateTimeImmutable::createFromFormat(
                $format,
                $value,
                $this->timezone
            );
            $errors = \DateTimeImmutable::getLastErrors();
            $valid = $errors === false || (
                (int)$errors['warning_count'] === 0 &&
                (int)$errors['error_count'] === 0
            );
            if ($datetime !== false && $valid) {
                return $datetime->getTimestamp();
            }
        }

        return null;
    }

    private function configured_timestamp(string $key): ?int {
        return $this->parse_datetime($this->raw($key));
    }

    private function raw(string $key): string {
        if ($this->configuration !== null) {
            return (string)($this->configuration[$key] ?? '');
        }

        $value = get_config('local_subscriptions', $key);
        return $value === false ? '' : (string)$value;
    }

    /** @return array{severity:string,code:string,message:string} */
    private function issue(string $severity, string $code, string $message): array {
        return [
            'severity' => $severity,
            'code' => $code,
            'message' => $message,
        ];
    }
}
