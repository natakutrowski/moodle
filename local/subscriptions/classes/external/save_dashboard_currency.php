<?php

namespace local_subscriptions\external;

defined('MOODLE_INTERNAL') || die();

use context_system;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\currency\Currency;
use local_subscriptions\currency\CurrencyPreferenceRepository;

/**
 * Save the current user's preferred Dashboard currency.
 */
final class save_dashboard_currency extends external_api {

    public static function execute_parameters():
        external_function_parameters {
        return new external_function_parameters([
            'currency' => new external_value(
                PARAM_ALPHA,
                'Three-letter currency code'
            ),
        ]);
    }

    /**
     * Save the preference.
     *
     * @return array{success: bool, currency: string}
     */
    public static function execute(
        string $currency
    ): array {
        global $USER;

        $params = self::validate_parameters(
            self::execute_parameters(),
            [
                'currency' => $currency,
            ]
        );

        require_login();

        $context = context_system::instance();
        self::validate_context($context);

        require_capability(
            Capabilities::VIEW_DASHBOARD,
            $context
        );

        $currency = Currency::sanitize(
            $params['currency']
        );

        if ($currency === '') {
            throw new \invalid_parameter_exception(
                'Invalid currency code.'
            );
        }

        (new CurrencyPreferenceRepository())->set(
            (int)$USER->id,
            $currency
        );

        return [
            'success' => true,
            'currency' => $currency,
        ];
    }

    public static function execute_returns():
        external_single_structure {
        return new external_single_structure([
            'success' => new external_value(
                PARAM_BOOL,
                'Whether the preference was saved'
            ),
            'currency' => new external_value(
                PARAM_ALPHA,
                'Saved currency'
            ),
        ]);
    }
}