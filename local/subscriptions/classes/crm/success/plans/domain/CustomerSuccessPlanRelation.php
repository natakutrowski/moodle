<?php

namespace local_subscriptions\crm\success\plans\domain;

defined('MOODLE_INTERNAL') || die();

/**
 * Semantic relation between a plan step and another CRM object.
 */
final class CustomerSuccessPlanRelation {

    public const CREATED_FROM = 'created_from';
    public const RELATED = 'related';
    public const EXECUTED_BY = 'executed_by';
    public const BLOCKED_BY = 'blocked_by';
    public const RESOLVES = 'resolves';

    /**
     * @return string[]
     */
    public static function all(): array {
        return [
            self::CREATED_FROM,
            self::RELATED,
            self::EXECUTED_BY,
            self::BLOCKED_BY,
            self::RESOLVES,
        ];
    }

    public static function is_valid(
        string $relation
    ): bool {
        return in_array(
            $relation,
            self::all(),
            true
        );
    }
}