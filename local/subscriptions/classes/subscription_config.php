<?php
namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

class subscription_config {

	// -- Arrays of constants --
    public const PLAN_DURATION_KEYS = [
        '1month',
        '3months',
        '6months',
        '1year',
        '3years',
        'lifetime',
    ];

    public const AVAILABLE_CURRENCIES = [
        'EUR' => '€',
        'USD' => '$',
        'RUB' => '₽',
        'GBP' => '£',
        'CHF' => 'CHF',
    ];

    public const AVAILABLE_LANGUAGES = [
        'fr' => 'Français',
        'en' => 'English',
        'es' => 'Español',
        'de' => 'Deutsch',
        'it' => 'Italiano',
        'ru' => 'Русский',
    ];

    public static function get_plans(): array {
        $plans = [];
        foreach (self::PLAN_DURATION_KEYS as $key) {
            $plans[$key] = get_string('plan_' . $key, 'local_subscriptions');
        }
        return $plans;
    }

    public static function get_currency_options(): array {
        $currencies = [];
        foreach (self::AVAILABLE_CURRENCIES as $code => $symbol) {
            $currencies[$code] = "{$code} ({$symbol})";
        }
        return $currencies;
    }

    public static function get_currency_symbol(string $currency): ?string {
        $map = [
            'USD' => '$',
            'EUR' => '€',
            'RUB' => '₽',
            'GBP' => '£',
        ];
        return $map[$currency] ?? null;
    }


    public static function get_translation_languages(): array {
        return self::AVAILABLE_LANGUAGES;
    }
    
    // -- Plugin path --
    public static function plugin_path(): string {
        return '/local/subscriptions/';
    }
    
    // -- Plugin pages --
    public static function add_manual_subscription_page(): string {
        return self::plugin_path() . 'add_manual_subscription.php';
    }
    public static function import_csv_page(): string {
        return self::plugin_path() . 'import_csv.php';
    }
    public static function process_csv_page(): string {
        return self::plugin_path() . 'process_csv.php';
    }
    public static function manage_page(): string {
        return self::plugin_path() . 'manage.php';
    }
    public static function scopes_translations_page(): string {
        return self::plugin_path() . 'scopes_translations.php';
    }
    public static function plans_translations_page(): string {
        return self::plugin_path() . 'plans_translations.php';
    }
    public static function plans_prices_page(): string {
        return self::plugin_path() . 'plans_prices.php';
    }
    
    // -- Buttons --
    // Add subscription
    public static function button_add_subscription(): string {
		$button = \html_writer::link(
			new \moodle_url(self::add_manual_subscription_page()),
			\html_writer::tag('i', '', ['class' => 'fa fa-plus-circle', 'style' => 'margin-right: 5px;']) .
			get_string('add_subscription', 'local_subscriptions'),
			['class' => 'btn btn-secondary me-2']
		);
		return $button;
	}
	
	// Manage subscription
	public static function button_manage_subscription(): string {
		$button = \html_writer::link(
			new \moodle_url(self::manage_page(), ['tab' => 'user_subscriptions']),
			'📋 ' . get_string('manage_subscriptions', 'local_subscriptions'),
			['class' => 'btn btn-secondary me-2']
		);
		return $button;
	}
	
	// Import CSV
	public static function button_import_csv(): string {
		$button = \html_writer::link(
			new \moodle_url(self::import_csv_page()),
			'📂 ' . get_string('btn_import_csv', 'local_subscriptions'),
			['class' => 'btn btn-secondary me-2']
		);	
		return $button;
	}    

    public static function get_plan_info(string $planid): array {
        $plans = self::get_plans_full(); // nom + description + scope
        return $plans[$planid] ?? [];
    }

    public static function get_plans_full(): array {
        global $DB;

        $records = $DB->get_records('subscription_plan');
        $result = [];

        foreach ($records as $plan) {
            $name = self::get_plan_translation($plan->id, 'name');
            $description = self::get_plan_translation($plan->id, 'description');

            $result[$plan->id] = [
                'id' => $plan->id,
                'name' => $name,
                'description' => $description,
                'accessscopeid' => $plan->accessscopeid,
                'duration_key' => $plan->duration_key,
                'is_active' => $plan->is_active,
                'creation_date' => $plan->creation_date,
                'last_update' => $plan->last_update,
            ];

        }

        return $result;
    }

    public static function get_plan_translation(int $planid, string $field, ?string $lang = null): string {
        global $DB;

        if (!$lang) {
            $lang = current_language();
        }

        $record = $DB->get_record('subscription_plan_translation', [
            'planid' => $planid,
            'lang' => $lang,
        ]);

        if ($record && isset($record->{$field})) {
            return $record->{$field};
        }

        // Fallback : retourne le champ en anglais
        if ($lang !== 'en') {
            $record = $DB->get_record('subscription_plan_translation', [
                'planid' => $planid,
                'lang' => 'en',
            ]);

            if ($record && isset($record->{$field})) {
                return $record->{$field};
            }
        }

        return '';
    }

    public static function guard_public_access(): void {
        $mode = get_config('local_subscriptions', 'availability_mode') ?: 'enabled';
        if ($mode === 'enabled') { return; }

        $sysctx = \context_system::instance();

        if ($mode === 'adminonly') {
            if (!isloggedin()) {
                // Laisse Moodle rediriger vers login
                require_login();
            }
            if (!has_capability('moodle/site:config', $sysctx)) {
                redirect(new \moodle_url('/'), get_string('subs_unavailable_adminonly', 'local_subscriptions'), 5,
                    \core\output\notification::NOTIFY_WARNING);
            }
            return;
        }

        // disabled => tout le monde dehors sauf admin
        if (is_siteadmin()) { return; }
        redirect(new \moodle_url('/'), get_string('subs_unavailable', 'local_subscriptions'), 5,
            \core\output\notification::NOTIFY_INFO);
    }
}
