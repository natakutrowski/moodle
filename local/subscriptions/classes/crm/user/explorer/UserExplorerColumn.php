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
    public const INBOX = 'inbox';
    public const CUSTOMER_SUCCESS_PLANS = 'customer_success_plans';
    public const COUNTRY = 'country';
    public const REGISTERED = 'registered';
    public const LAST_ACCESS = 'last_access';

    public static function allowed(
        bool $includeinbox = true
    ): array {
        $columns = [
            self::USER,
            self::TAGS,
            self::SCORE,
            self::RISK,
            self::INTELLIGENCE,
            self::SUBSCRIPTIONS,
            self::PURCHASES,
            self::INBOX,
            self::CUSTOMER_SUCCESS_PLANS,
            self::COUNTRY,
            self::REGISTERED,
            self::LAST_ACCESS,
        ];

        if (!$includeinbox) {
            $columns = array_values(
                array_filter(
                    $columns,
                    static fn(string $column): bool =>
                        $column !== self::INBOX
                )
            );
        }

        return $columns;
    }

    public static function defaults(
        bool $includeinbox = true
    ): array {
        $columns = [
            self::USER,
            self::TAGS,
            self::SCORE,
            self::RISK,
            self::INTELLIGENCE,
            self::SUBSCRIPTIONS,
            self::PURCHASES,
            self::INBOX,
            self::CUSTOMER_SUCCESS_PLANS,
            self::LAST_ACCESS,
        ];

        if (!$includeinbox) {
            $columns = array_values(
                array_filter(
                    $columns,
                    static fn(string $column): bool =>
                        $column !== self::INBOX
                )
            );
        }

        return $columns;
    }

    public static function required(): array {
        return [
            self::USER,
        ];
    }

    public static function normalize(
        array $columns,
        bool $includeinbox = true
    ): array {
        $allowed = array_flip(
            self::allowed($includeinbox)
        );

        $normalized = [];

        foreach ($columns as $column) {
            if (
                is_string($column) &&
                isset($allowed[$column]) &&
                !in_array(
                    $column,
                    $normalized,
                    true
                )
            ) {
                $normalized[] = $column;
            }
        }

        foreach (
            self::required()
            as $requiredcolumn
        ) {
            if (
                !in_array(
                    $requiredcolumn,
                    $normalized,
                    true
                )
            ) {
                array_unshift(
                    $normalized,
                    $requiredcolumn
                );
            }
        }

        return $normalized ?:
            self::defaults($includeinbox);
    }

    public static function label(
        string $column
    ): string {
        if (
            !in_array(
                $column,
                self::allowed(),
                true
            )
        ) {
            throw new \coding_exception(
                'Unknown User Explorer column: ' .
                $column
            );
        }

        return get_string(
            'crm_user_column_' . $column,
            'local_subscriptions'
        );
    }
}