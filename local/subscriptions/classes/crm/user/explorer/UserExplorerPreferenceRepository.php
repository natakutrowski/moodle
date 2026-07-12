<?php

namespace local_subscriptions\crm\user\explorer;

defined('MOODLE_INTERNAL') || die();

final class UserExplorerPreferenceRepository {

    private const COLUMNS_PREFERENCE =
        'local_subscriptions_user_explorer_columns';

    private const VIEWS_PREFERENCE =
        'local_subscriptions_user_explorer_views';

    public function get_columns(int $userid): array {
        $raw = get_user_preferences(
            self::COLUMNS_PREFERENCE,
            '',
            $userid
        );

        if (!is_string($raw) || $raw === '') {
            return UserExplorerColumn::defaults();
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded)
            ? UserExplorerColumn::normalize($decoded)
            : UserExplorerColumn::defaults();
    }

    public function save_columns(
        int $userid,
        array $columns
    ): void {
        set_user_preference(
            self::COLUMNS_PREFERENCE,
            json_encode(
                UserExplorerColumn::normalize($columns),
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            ),
            $userid
        );
    }

    public function reset_columns(int $userid): void {
        unset_user_preference(
            self::COLUMNS_PREFERENCE,
            $userid
        );
    }

    public function get_saved_views(int $userid): array {
        $raw = get_user_preferences(
            self::VIEWS_PREFERENCE,
            '[]',
            $userid
        );

        if (!is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function save_views(
        int $userid,
        array $views
    ): void {
        set_user_preference(
            self::VIEWS_PREFERENCE,
            json_encode(
                array_values($views),
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            ),
            $userid
        );
    }
}