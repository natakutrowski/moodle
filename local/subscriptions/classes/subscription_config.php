<?php
namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

class subscription_config {

	// -- Arrays of constants --
    public const PLAN_DURATION_KEYS = [
        '1week',
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
    public static function backoffice_home(): \moodle_url {
        return new \moodle_url(self::admin_dashboard_page());
    }
    public static function admin_dashboard_page(): string {
        return self::plugin_path() . 'admin/dashboard.php';
    }

    public static function import_csv_page(): string {
        return self::plugin_path() . 'admin/imports/index.php';
    }
    public static function process_csv_page(): string {
        return self::plugin_path() . 'admin/imports/process.php';
    }
    public static function manage_page(): string {
        return self::plugin_path() . 'admin/manage.php';
    }
    public static function scopes_translations_page(): string {
        return self::plugin_path() . 'admin/scopes/translations.php';
    }
    public static function plans_translations_page(): string {
        return self::plugin_path() . 'admin/plans/translations.php';
    }
    public static function plans_prices_page(): string {
        return self::plugin_path() . 'admin/plans/prices.php';
    }

    public static function plan_entitlements_page(): string {
        return self::plugin_path() . 'admin/plans/entitlements.php';
    }

    public static function plan_upgrades_page(): string {
        return self::plugin_path() . 'admin/plans/upgrades.php';
    }

    public static function user_subscriptions_page(): string {
        return self::plugin_path() . 'admin/subscriptions/index.php';
    }

    public static function add_manual_subscription_page(): string {
        return self::plugin_path() . 'admin/subscriptions/add.php';
    }

    public static function user_subscription_edit_page(): string {
        return self::plugin_path() . 'admin/subscriptions/edit.php';
    }

    public static function user_subscription_delete_page(): string {
        return self::plugin_path() . 'admin/subscriptions/delete.php';
    }

    public static function subscriptions_export_page(): string {
        return self::plugin_path() . 'admin/subscriptions/export.php';
    }

    public static function admin_users_page(): string {
        return self::plugin_path() . 'admin/users/index.php';
    }

    public static function admin_user_view_page(): string {
        return self::plugin_path() . 'admin/users/view.php';
    }

    public static function digital_products_admin_page(): string {
        return self::plugin_path() . 'admin/digital/products.php';
    }

    public static function digital_product_edit_admin_page(): string {
        return self::plugin_path() . 'admin/digital/product_edit.php';
    }

    public static function digital_product_delete_admin_page(): string {
        return self::plugin_path() . 'admin/digital/product_delete.php';
    }

    public static function digital_product_duplicate_admin_page(): string {
        return self::plugin_path() . 'admin/digital/product_duplicate.php';
    }

    public static function digital_product_toggle_admin_page(): string {
        return self::plugin_path() . 'admin/digital/product_toggle.php';
    }

    public static function digital_product_file_preview_admin_page(): string {
        return self::plugin_path() . 'admin/digital/product_file_preview.php';
    }

    public static function digital_purchases_admin_page(): string {
        return self::plugin_path() . 'admin/digital/purchases.php';
    }

    public static function digital_sales_stats_admin_page(): string {
        return self::plugin_path() . 'admin/digital/stats.php';
    }

    public static function boutique_page(): string {
        return '/boutique';
    }
    
    // -- Buttons --
    public static function button_admin_dashboard(): string {
        return \html_writer::link(
            new \moodle_url(self::admin_dashboard_page()),
            '⚙️ ' . get_string('admin_dashboard', 'local_subscriptions'),
            ['class' => 'btn btn-secondary me-2']
        );
    }
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
        return \html_writer::link(
            new \moodle_url(self::user_subscriptions_page()),
            '📋 ' . get_string('manage_user_subscriptions', 'local_subscriptions'),
            ['class' => 'btn btn-secondary me-2']
        );
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
