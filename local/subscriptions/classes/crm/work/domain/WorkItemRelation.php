<?php

namespace local_subscriptions\crm\work\domain;

defined('MOODLE_INTERNAL') || die();

final class WorkItemRelation {

    public const RELATED = 'related';
    public const CREATED_FROM = 'created_from';
    public const AFFECTS = 'affects';
    public const BLOCKS = 'blocks';
    public const DUPLICATES = 'duplicates';

    public static function all(): array {
        return [
            self::RELATED,
            self::CREATED_FROM,
            self::AFFECTS,
            self::BLOCKS,
            self::DUPLICATES,
        ];
    }

    public static function is_valid(string $relation): bool {
        return in_array($relation, self::all(), true);
    }
}