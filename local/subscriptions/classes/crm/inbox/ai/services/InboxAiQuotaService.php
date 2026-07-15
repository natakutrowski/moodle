<?php

namespace local_subscriptions\crm\inbox\ai\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\ai\repositories\InboxAiUsageRepository;

final class InboxAiQuotaService {

    private const DEFAULT_GLOBAL_DAILY = 500;
    private const DEFAULT_USER_DAILY = 100;

    public function __construct(
        private readonly InboxAiUsageRepository $usage
    ) {
    }

    public function can_consume(
        ?int $actorid,
        int $units = 1
    ): bool {
        $units = max(1, $units);

        $since = usergetmidnight(
            time()
        );

        $globalusage =
            $this->usage->count_since(
                $since,
                null,
                true
            );

        if (
            $globalusage + $units >
            $this->global_limit()
        ) {
            return false;
        }

        if (
            $actorid !== null &&
            $actorid > 0
        ) {
            $userusage =
                $this->usage->count_since(
                    $since,
                    $actorid,
                    true
                );

            if (
                $userusage + $units >
                $this->user_limit()
            ) {
                return false;
            }
        }

        return true;
    }

    public function assert_can_consume(
        ?int $actorid,
        int $units = 1
    ): void {
        if (
            $this->can_consume(
                $actorid,
                $units
            )
        ) {
            return;
        }

        throw new \moodle_exception(
            'crm_inbox_ai_quota_exceeded',
            'local_subscriptions'
        );
    }

    public function usage(
        ?int $actorid = null
    ): array {
        $since = usergetmidnight(
            time()
        );

        return [
            'global' =>
                $this->usage->count_since(
                    $since,
                    null,
                    true
                ),

            'globallimit' =>
                $this->global_limit(),

            'user' =>
                $actorid !== null
                    ? $this->usage->count_since(
                        $since,
                        $actorid,
                        true
                    )
                    : null,

            'userlimit' =>
                $this->user_limit(),
        ];
    }

    private function global_limit(): int {
        return max(
            1,
            (int)get_config(
                'local_subscriptions',
                'inbox_ai_global_daily_limit'
            ) ?: self::DEFAULT_GLOBAL_DAILY
        );
    }

    private function user_limit(): int {
        return max(
            1,
            (int)get_config(
                'local_subscriptions',
                'inbox_ai_user_daily_limit'
            ) ?: self::DEFAULT_USER_DAILY
        );
    }
}