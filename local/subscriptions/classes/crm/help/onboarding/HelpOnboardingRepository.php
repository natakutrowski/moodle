<?php

namespace local_subscriptions\crm\help\onboarding;

defined('MOODLE_INTERNAL') || die();

final class HelpOnboardingRepository {

    private const PREFERENCE_KEY =
        'local_subscriptions_crm_onboarding';

    public function get_completed_step_ids(
        int $userid
    ): array {
        $rawvalue = get_user_preferences(
            self::PREFERENCE_KEY,
            '[]',
            $userid
        );

        if (!is_string($rawvalue) || $rawvalue === '') {
            return [];
        }

        $decoded = json_decode(
            $rawvalue,
            true
        );

        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_unique(array_filter(
            $decoded,
            static fn($stepid): bool =>
                is_string($stepid) &&
                preg_match(
                    '/\A[a-z0-9][a-z0-9_-]*\z/',
                    $stepid
                ) === 1
        )));
    }

    public function save_completed_step_ids(
        int $userid,
        array $stepids
    ): void {
        $stepids = array_values(array_unique(array_filter(
            $stepids,
            static fn($stepid): bool =>
                is_string($stepid) &&
                preg_match(
                    '/\A[a-z0-9][a-z0-9_-]*\z/',
                    $stepid
                ) === 1
        )));

        set_user_preference(
            self::PREFERENCE_KEY,
            json_encode(
                $stepids,
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            ),
            $userid
        );
    }

    public function reset(int $userid): void {
        unset_user_preference(
            self::PREFERENCE_KEY,
            $userid
        );
    }
}