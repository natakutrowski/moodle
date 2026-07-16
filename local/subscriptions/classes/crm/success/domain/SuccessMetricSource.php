<?php

namespace local_subscriptions\crm\success\domain;

defined('MOODLE_INTERNAL') || die();

/**
 * Identifies the origin of a normalized Customer Success metric.
 *
 * Sources describe where a metric was collected. They do not describe
 * how the metric affects a score.
 */
final class SuccessMetricSource {

    public const MOODLE_USER = 'moodle_user';
    public const MOODLE_LOGS = 'moodle_logs';
    public const COURSE_COMPLETION = 'course_completion';
    public const GRADES = 'grades';
    public const LEVELUP_XP = 'levelup_xp';
    public const POODLL = 'poodll';
    public const SUBSCRIPTIONS = 'subscriptions';
    public const DIGITAL_PURCHASES = 'digital_purchases';
    public const INBOX = 'inbox';
    public const TICKETS = 'tickets';
    public const CRM = 'crm';
    public const WORK_ITEMS = 'work_items';

    /**
     * Returns all supported source identifiers.
     *
     * @return string[]
     */
    public static function all(): array {
        return [
            self::MOODLE_USER,
            self::MOODLE_LOGS,
            self::COURSE_COMPLETION,
            self::GRADES,
            self::LEVELUP_XP,
            self::POODLL,
            self::SUBSCRIPTIONS,
            self::DIGITAL_PURCHASES,
            self::INBOX,
            self::TICKETS,
            self::CRM,
            self::WORK_ITEMS,
        ];
    }

    public static function is_valid(string $source): bool {
        return in_array($source, self::all(), true);
    }
}