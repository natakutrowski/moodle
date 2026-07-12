<?php

namespace local_subscriptions\crm\user\explorer;

defined('MOODLE_INTERNAL') || die();

final class UserExplorerColumn {

    public const USER = 'user';
    public const TAGS = 'tags';
    public const SCORE = 'score';
    public const RISK = 'risk';
    public const INTELLIGENCE = 'intelligence';
    public const SUBSCRIPTIONS = 'subscriptions';
    public const PURCHASES = 'purchases';
    public const COUNTRY = 'country';
    public const REGISTERED = 'registered';
    public const LAST_ACCESS = 'last_access';

    public static function allowed(): array {
        return [
            self::USER,
            self::TAGS,
            self::SCORE,
            self::RISK,
            self::INTELLIGENCE,
            self::SUBSCRIPTIONS,
            self::PURCHASES,
            self::COUNTRY,
            self::REGISTERED,
            self::LAST_ACCESS,
        ];
    }

    public static function defaults(): array {
        return [
            self::USER,
            self::TAGS,
            self::SCORE,
            self::RISK,
            self::INTELLIGENCE,
            self::SUBSCRIPTIONS,
            self::PURCHASES,
            self::LAST_ACCESS,
        ];
    }

    public static function required(): array {
        return [
            self::USER,
        ];
    }

    public static function normalize(array $columns): array {
        $allowed = array_flip(self::allowed());

        $normalized = [];

        foreach ($columns as $column) {
            if (
                is_string($column) &&
                isset($allowed[$column]) &&
                !in_array($column, $normalized, true)
            ) {
                $normalized[] = $column;
            }
        }

        foreach (self::required() as $requiredcolumn) {
            if (!in_array($requiredcolumn, $normalized, true)) {
                array_unshift($normalized, $requiredcolumn);
            }
        }

        return $normalized ?: self::defaults();
    }

    public static function label(string $column): string {
        if (!in_array($column, self::allowed(), true)) {
            throw new \coding_exception(
                'Unknown User Explorer column: ' . $column
            );
        }

        return get_string(
            'crm_user_column_' . $column,
            'local_subscriptions'
        );
    }
}