<?php
declare(strict_types=1);

namespace local_subscriptions\commerce\education\promotion;

defined('MOODLE_INTERNAL') || die();

/**
 * Lifecycle of a pedagogical promotion/campaign.
 *
 * Not to be confused with CommercePromotion, which is the commercial
 * discounts/pricing engine.
 */
final class CommercePedagogicalPromotionStatus {
    public const DRAFT = 'draft';
    public const SCHEDULED = 'scheduled';
    public const OPEN = 'open';
    public const FULL = 'full';
    public const STARTED = 'started';
    public const FINISHED = 'finished';
    public const ARCHIVED = 'archived';

    public static function all(): array {
        return [
            self::DRAFT,
            self::SCHEDULED,
            self::OPEN,
            self::FULL,
            self::STARTED,
            self::FINISHED,
            self::ARCHIVED,
        ];
    }

    public static function normalise(string $status): string {
        $status = strtolower(trim($status));
        if (!in_array($status, self::all(), true)) {
            throw new \coding_exception('Unsupported pedagogical promotion status: ' . $status);
        }
        return $status;
    }
}
