<?php

namespace local_subscriptions\crm\inbox\ai\domain;

defined('MOODLE_INTERNAL') || die();

final class InboxAiCategory {

    public const PAYMENT = 'payment';
    public const ACCESS = 'access';
    public const SUBSCRIPTION = 'subscription';
    public const TECHNICAL = 'technical';
    public const COURSE_CONTENT = 'course_content';
    public const ACCOUNT = 'account';
    public const REFUND = 'refund';
    public const BILLING = 'billing';
    public const COMMERCIAL = 'commercial';
    public const FEEDBACK = 'feedback';
    public const SPAM = 'spam';
    public const OTHER = 'other';

    public static function values(): array {
        return [
            self::PAYMENT,
            self::ACCESS,
            self::SUBSCRIPTION,
            self::TECHNICAL,
            self::COURSE_CONTENT,
            self::ACCOUNT,
            self::REFUND,
            self::BILLING,
            self::COMMERCIAL,
            self::FEEDBACK,
            self::SPAM,
            self::OTHER,
        ];
    }

    public static function is_valid(
        string $category
    ): bool {
        return in_array(
            $category,
            self::values(),
            true
        );
    }
}