<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\showroom\cms;

defined('MOODLE_INTERNAL') || die();

/**
 * Centralises persisted Showroom workflow statuses and their UI presentation.
 */
final class CommerceShowroomStatus {
    public const DRAFT = 'draft';
    public const REVIEW = 'review';
    public const PUBLISHED = 'published';
    public const ARCHIVED = 'archived';

    /** @return array<string,string> */
    public static function options(): array {
        return [
            self::DRAFT => self::label(self::DRAFT),
            self::REVIEW => self::label(self::REVIEW),
            self::PUBLISHED => self::label(self::PUBLISHED),
            self::ARCHIVED => self::label(self::ARCHIVED),
        ];
    }

    public static function label(string $status): string {
        $key = match ($status) {
            self::DRAFT => 'commerce_showroom_status_draft',
            self::REVIEW => 'commerce_showroom_status_review',
            self::PUBLISHED => 'commerce_showroom_status_published',
            self::ARCHIVED => 'commerce_showroom_status_archived',
            default => '',
        };

        return $key !== ''
            ? get_string($key, 'local_subscriptions')
            : s($status);
    }

    public static function badge_class(string $status): string {
        return match ($status) {
            self::PUBLISHED => 'success',
            self::REVIEW => 'warning text-dark',
            self::ARCHIVED => 'dark',
            default => 'secondary',
        };
    }
}
