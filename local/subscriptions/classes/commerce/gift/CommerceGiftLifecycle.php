<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\gift;

defined('MOODLE_INTERNAL') || die();

/**
 * Proposed G2B state transitions. State only; no e-mail, payment or grant actions.
 * Separates 'claimed', 'grant pending' and 'active' to support safe retries.
 */
final class CommerceGiftLifecycle {
    public const ISSUED = 'issued';
    public const SCHEDULED = 'scheduled';
    public const NOTIFIED = 'notified';
    public const DELIVERY_FAILED = 'delivery_failed';
    public const CLAIMED = 'claimed';
    public const GRANT_PENDING = 'grant_pending';
    public const GRANT_FAILED = 'grant_failed';
    public const ACTIVE = 'active';
    public const CANCELLED = 'cancelled';
    public const EXPIRED = 'expired';

    private const TRANSITIONS = [
        self::ISSUED => [self::SCHEDULED, self::NOTIFIED, self::DELIVERY_FAILED, self::CLAIMED, self::CANCELLED, self::EXPIRED],
        self::SCHEDULED => [self::NOTIFIED, self::DELIVERY_FAILED, self::CLAIMED, self::CANCELLED, self::EXPIRED],
        self::NOTIFIED => [self::CLAIMED, self::CANCELLED, self::EXPIRED],
        self::DELIVERY_FAILED => [self::SCHEDULED, self::NOTIFIED, self::CLAIMED, self::CANCELLED, self::EXPIRED],
        self::CLAIMED => [self::GRANT_PENDING],
        self::GRANT_PENDING => [self::GRANT_FAILED, self::ACTIVE],
        self::GRANT_FAILED => [self::GRANT_PENDING],
        self::ACTIVE => [],
        self::CANCELLED => [],
        self::EXPIRED => [],
    ];

    public static function can_transition(string $from, string $to): bool {
        return isset(self::TRANSITIONS[$from])
            && ($from === $to || in_array($to, self::TRANSITIONS[$from], true));
    }

    public static function assert_transition(string $from, string $to): void {
        if (!self::can_transition($from, $to)) {
            throw new \DomainException('Invalid gift lifecycle transition.');
        }
    }

    public static function is_terminal(string $state): bool {
        return in_array($state, [self::ACTIVE, self::CANCELLED, self::EXPIRED], true);
    }
}
