<?php

namespace local_subscriptions\crm\help\guides;

defined('MOODLE_INTERNAL') || die();

final class HelpGuideProgressRepository {

    private const PREFERENCE_PREFIX =
        'local_subscriptions_crm_guide_';

    public function get_completed_step_ids(
        int $userid,
        string $guideid
    ): array {
        $value = get_user_preferences(
            $this->preference_key($guideid),
            '[]',
            $userid
        );

        if (!is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

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
        string $guideid,
        array $stepids
    ): void {
        set_user_preference(
            $this->preference_key($guideid),
            json_encode(
                array_values(array_unique($stepids)),
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            ),
            $userid
        );
    }

    public function reset(
        int $userid,
        string $guideid
    ): void {
        unset_user_preference(
            $this->preference_key($guideid),
            $userid
        );
    }

    private function preference_key(
        string $guideid
    ): string {
        if (
            preg_match(
                '/\A[a-z0-9][a-z0-9_-]*\z/',
                $guideid
            ) !== 1
        ) {
            throw new \coding_exception(
                'Invalid CRM help guide identifier.'
            );
        }

        return self::PREFERENCE_PREFIX . $guideid;
    }
}