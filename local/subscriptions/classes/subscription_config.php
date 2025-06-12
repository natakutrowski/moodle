<?php
namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

class subscription_config {

	// -- Arrays of constants --
    public const PLAN_KEYS = [
        '1month',
        '3months',
        '6months',
        '1year',
        '3years',
        'lifetime',
    ];

    public const SCOPE_KEYS = [
        'full',
        'a0',
        'a1',
        'a2',
        'test',
    ];

    public const SUBSCRIPTION_MAPPING = [
        'full'      => [2, 5],
        'test'      => [3],
        'a0'        => [2],
        'a1'        => [4],
        'a2'        => [5],
    ];

    public static function get_plans(): array {
        $plans = [];
        foreach (self::PLAN_KEYS as $key) {
            $plans[$key] = get_string('plan_' . $key, 'local_subscriptions');
        }
        return $plans;
    }

    public static function get_scopes(): array {
        $scopes = [];
        foreach (self::SCOPE_KEYS as $key) {
            $scopes[$key] = get_string('access_' . $key, 'local_subscriptions');
        }
        return $scopes;
    }

    // -- Plugin path --
    public static function plugin_path(): string {
        return '/local/subscriptions/';
    }
    
    // -- Plugin pages --
    public static function add_subscription_page(): string {
        return self::plugin_path() . 'add_subscription.php';
    }

    public static function manage_subscription_page(): string {
        return self::plugin_path() . 'manage_subscription.php';
    }

    public static function import_csv_page(): string {
        return self::plugin_path() . 'import_csv.php';
    }

    public static function process_csv_page(): string {
        return self::plugin_path() . 'process_csv.php';
    }
    
    // -- Buttons --
    // Add subscription
	public static function button_add_subscription(): string {
		$button = \html_writer::link(
			new \moodle_url(self::add_subscription_page()),
			\html_writer::tag('i', '', ['class' => 'fa fa-plus-circle', 'style' => 'margin-right: 5px;']) .
			get_string('btn_add_subscription', 'local_subscriptions'),
			['class' => 'btn btn-secondary me-2']
		);
		return $button;
	}
	
	// Manage subscription
	public static function button_manage_subscription(): string {
		$button = \html_writer::link(
			new \moodle_url(self::manage_subscription_page()),
			'📋 ' . get_string('btn_manage_subscriptions', 'local_subscriptions'),
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
}
