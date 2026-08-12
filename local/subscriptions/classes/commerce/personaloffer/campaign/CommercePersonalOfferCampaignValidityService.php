<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\personaloffer\campaign;

defined('MOODLE_INTERNAL') || die();

/** Resolves Campaign validity policy into immutable absolute Personal Offer timestamps. */
final class CommercePersonalOfferCampaignValidityService {
    public const MODE_LEGACY = 'legacy';
    public const MODE_FIXED = 'fixed_datetime';
    public const MODE_DURATION = 'duration';
    public const DEFAULT_TIMEZONE = 'Europe/Paris';

    /** @return array{validfrom:?int,expiresat:?int} */
    public function resolve(\stdClass $campaign, ?int $issuedat = null): array {
        $issuedat ??= time();
        $mode = (string)($campaign->validitymode ?? self::MODE_LEGACY);
        if ($mode === self::MODE_DURATION) {
            $duration = (int)($campaign->validityduration ?? 0);
            if ($duration <= 0) { throw new \coding_exception('Personal Offer campaign duration must be positive.'); }
            return ['validfrom' => $issuedat, 'expiresat' => $issuedat + $duration];
        }
        $validfrom = empty($campaign->validfrom) ? null : (int)$campaign->validfrom;
        $expiresat = empty($campaign->expiresat) ? null : (int)$campaign->expiresat;
        if ($validfrom !== null && $expiresat !== null && $expiresat <= $validfrom) {
            throw new \coding_exception('Personal Offer campaign expiration must be after its start.');
        }
        return ['validfrom' => $validfrom, 'expiresat' => $expiresat];
    }

    public static function normalise_mode(string $mode): string {
        $mode = trim($mode);
        if (!in_array($mode, [self::MODE_LEGACY, self::MODE_FIXED, self::MODE_DURATION], true)) {
            throw new \coding_exception('Invalid Personal Offer campaign validity mode.');
        }
        return $mode;
    }

    public static function normalise_timezone(string $timezone): string {
        $timezone = trim($timezone) ?: self::DEFAULT_TIMEZONE;
        try { new \DateTimeZone($timezone); } catch (\Throwable) {
            throw new \coding_exception('Invalid Personal Offer campaign timezone.');
        }
        return $timezone;
    }

    public static function duration_seconds(int $value, string $unit): int {
        if ($value <= 0) { throw new \coding_exception('Personal Offer campaign duration must be positive.'); }
        $multiplier = match ($unit) {
            'hours' => HOURSECS,
            'days' => DAYSECS,
            default => throw new \coding_exception('Invalid Personal Offer campaign duration unit.'),
        };
        $seconds = $value * $multiplier;
        if ($seconds > 366 * DAYSECS) { throw new \coding_exception('Personal Offer campaign duration is too long.'); }
        return $seconds;
    }
}
