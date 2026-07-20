<?php

namespace local_subscriptions\currency;

defined('MOODLE_INTERNAL') || die();

/**
 * Stores the preferred CRM Dashboard currency per user.
 */
final class CurrencyPreferenceRepository {

    private const PREFERENCE_NAME =
        'local_subscriptions_dashboard_currency';

    /**
     * Get a user's preferred currency.
     */
    public function get(int $userid): string {
        if ($userid <= 0) {
            return '';
        }

        $value = get_user_preferences(
            self::PREFERENCE_NAME,
            '',
            $userid
        );

        return Currency::sanitize((string)$value);
    }

    /**
     * Save a user's preferred currency.
     */
    public function set(int $userid, string $currency): void {
        $currency = Currency::sanitize($currency);

        if ($userid <= 0 || $currency === '') {
            return;
        }

        set_user_preference(
            self::PREFERENCE_NAME,
            $currency,
            $userid
        );
    }

    /**
     * Remove the preference.
     */
    public function reset(int $userid): void {
        if ($userid <= 0) {
            return;
        }

        unset_user_preference(
            self::PREFERENCE_NAME,
            $userid
        );
    }
}