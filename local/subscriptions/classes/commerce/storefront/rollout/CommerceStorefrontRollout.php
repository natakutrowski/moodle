<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\rollout;

defined('MOODLE_INTERNAL') || die();

/** Controls the progressive switch from historical catalogue pages to Storefront. */
final class CommerceStorefrontRollout {
    public const CONFIG_ENABLED = 'storefront_enabled';

    private function __construct() {
    }

    public static function is_enabled(): bool {
        return (bool)get_config('local_subscriptions', self::CONFIG_ENABLED);
    }

    public static function should_redirect_subscribe(
        bool $enabled,
        bool $embedded,
        ?int $planid
    ): bool {
        return $enabled && !$embedded && ($planid === null || $planid <= 0);
    }
}
