<?php

namespace local_subscriptions\crm\success\plans\domain;

defined('MOODLE_INTERNAL') || die();

/**
 * Source responsible for preparing a Customer Success plan.
 */
final class CustomerSuccessPlanSource {

    /**
     * Plan manually created by an administrator.
     */
    public const MANUAL = 'manual';

    /**
     * Plan proposed from one or several CRM recommendations.
     */
    public const RECOMMENDATION_ENGINE =
        'recommendation_engine';

    /**
     * Plan proposed from a correlation result.
     */
    public const CORRELATION_ENGINE =
        'correlation_engine';

    /**
     * Plan prepared from the CRM Assistant workspace.
     */
    public const CRM_ASSISTANT =
        'crm_assistant';

    /**
     * Plan prepared from the User 360° page.
     */
    public const USER_360 =
        'user_360';

    /**
     * @return string[]
     */
    public static function all(): array {
        return [
            self::MANUAL,
            self::RECOMMENDATION_ENGINE,
            self::CORRELATION_ENGINE,
            self::CRM_ASSISTANT,
            self::USER_360,
        ];
    }

    public static function is_valid(
        string $source
    ): bool {
        return in_array(
            $source,
            self::all(),
            true
        );
    }
}