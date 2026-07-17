<?php

namespace local_subscriptions\crm\intelligence\recommendations;

defined('MOODLE_INTERNAL') || die();

/**
 * Data sources that can contribute to a CRM recommendation.
 *
 * A recommendation may use several sources. These values identify the
 * subsystem responsible for each piece of supporting evidence.
 */
final class RecommendationSource {

    public const CUSTOMER_SUCCESS = 'customer_success';
    public const CRM_INTELLIGENCE = 'crm_intelligence';
    public const INBOX = 'inbox';
    public const INBOX_AI = 'inbox_ai';
    public const WORK_MANAGEMENT = 'work_management';
    public const PAYMENTS = 'payments';
    public const SUBSCRIPTIONS = 'subscriptions';
    public const DIGITAL_PURCHASES = 'digital_purchases';
    public const MOODLE_ACTIVITY = 'moodle_activity';
    public const MOODLE_LEARNING = 'moodle_learning';
    public const AUTOMATION = 'automation';
    public const MANUAL = 'manual';

    /**
     * Recommendation produced by cross-domain signal correlation.
     */
    public const CORRELATION_ENGINE = 'correlation_engine';

    /**
     * Return all supported recommendation sources.
     *
     * @return string[]
     */
    public static function all(): array {
        return [
            self::CUSTOMER_SUCCESS,
            self::CRM_INTELLIGENCE,
            self::INBOX,
            self::INBOX_AI,
            self::WORK_MANAGEMENT,
            self::PAYMENTS,
            self::SUBSCRIPTIONS,
            self::DIGITAL_PURCHASES,
            self::MOODLE_ACTIVITY,
            self::MOODLE_LEARNING,
            self::AUTOMATION,
            self::MANUAL,
            self::CORRELATION_ENGINE,
        ];
    }

    /**
     * Check whether a recommendation source is supported.
     */
    public static function is_valid(string $source): bool {
        return in_array($source, self::all(), true);
    }
}