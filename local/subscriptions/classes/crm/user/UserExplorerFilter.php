<?php

namespace local_subscriptions\crm\user;

defined('MOODLE_INTERNAL') || die();

final class UserExplorerFilter {

    public const NONE = '';
    public const HOT_LEAD = 'hot_lead';
    public const AT_RISK = 'at_risk';
    public const VIP = 'vip';
    public const COLD_USER = 'cold_user';
    public const TRIAL_TO_PURCHASE = 'trial_to_purchase';
    public const UPGRADE_SUBSCRIPTION = 'upgrade_subscription';

    public static function allowed(): array {
        return [
            self::NONE,
            self::HOT_LEAD,
            self::AT_RISK,
            self::VIP,
            self::COLD_USER,
            self::TRIAL_TO_PURCHASE,
            self::UPGRADE_SUBSCRIPTION,
        ];
    }

    public static function normalize(string $filter): string {
        return in_array($filter, self::allowed(), true) ? $filter : self::NONE;
    }

    public static function label(string $filter): string {
        $filter = self::normalize($filter);

        if ($filter === self::NONE) {
            return get_string('crm_user_filter_all', 'local_subscriptions');
        }

        return get_string('crm_user_filter_' . $filter, 'local_subscriptions');
    }

    public static function is_segment(string $filter): bool {
        return in_array($filter, [
            self::HOT_LEAD,
            self::AT_RISK,
            self::VIP,
            self::COLD_USER,
        ], true);
    }

    public static function is_opportunity(string $filter): bool {
        return in_array($filter, [
            self::TRIAL_TO_PURCHASE,
            self::UPGRADE_SUBSCRIPTION,
        ], true);
    }
}