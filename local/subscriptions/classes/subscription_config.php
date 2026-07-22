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

    /**
     * Returns the main Moodle administration page.
     */
    public static function moodle_admin_page(): string {
        return '/admin/search.php';
    }

    /**
     * Returns the CampusFR student courses page.
     */
    public static function campus_my_courses_page(): string {
        return '/local/campus/mycourses.php';
    }

    /**
     * Returns the current user's Moodle profile page.
     */
    public static function moodle_user_profile_page(): string {
        return '/user/profile.php';
    }

    /**
     * Returns the current user's grade overview page.
     */
    public static function moodle_grade_overview_page(): string {
        return '/grade/report/overview/index.php';
    }

    /**
     * Returns the Moodle calendar page.
     */
    public static function moodle_calendar_page(): string {
        return '/calendar/view.php';
    }

    /**
     * Returns the current user's preferences page.
     */
    public static function moodle_user_preferences_page(): string {
        return '/user/preferences.php';
    }

    /**
     * Returns the Moodle role switching page.
     */
    public static function moodle_switch_role_page(): string {
        return '/course/switchrole.php';
    }

    /**
     * Returns the Moodle logout page.
     */
    public static function moodle_logout_page(): string {
        return '/login/logout.php';
    }

    /**
     * Returns the favicon configured in the Edly theme.
     *
     * The embedded Edly layout does not always include the configured theme
     * favicon, so autonomous CRM pages reuse the same stored theme file
     * explicitly.
     *
     * @return \moodle_url|null
     */
    public static function crm_favicon_url(): ?\moodle_url {
        $favicon = get_config(
            'theme_edly',
            'favicon'
        );

        if (
            !is_string($favicon) ||
            trim($favicon) === ''
        ) {
            return null;
        }

        return \moodle_url::make_pluginfile_url(
            \context_system::instance()->id,
            'theme_edly',
            'favicon',
            0,
            '/',
            $favicon
        );
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

    public static function user_subscription_view_page(): string {
        return self::plugin_path() . 'admin/subscriptions/view.php';
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

    public static function admin_user_email_preview_page(): string {
        return self::plugin_path() . 'admin/users/email_preview.php';
    }

    public static function admin_user_toggle_suspension_page(): string {
        return self::plugin_path() . 'admin/users/toggle_suspension.php';
    }    

    public static function digital_products_admin_page(): string {
        return self::plugin_path() . 'admin/digital/products/index.php';
    }

    public static function digital_product_edit_admin_page(): string {
        return self::plugin_path() . 'admin/digital/products/edit.php';
    }

    public static function digital_product_delete_admin_page(): string {
        return self::plugin_path() . 'admin/digital/products/delete.php';
    }

    public static function digital_product_duplicate_admin_page(): string {
        return self::plugin_path() . 'admin/digital/products/duplicate.php';
    }

    public static function digital_product_toggle_admin_page(): string {
        return self::plugin_path() . 'admin/digital/products/toggle.php';
    }

    public static function digital_product_file_preview_admin_page(): string {
        return self::plugin_path() . 'admin/digital/products/preview.php';
    }

    public static function digital_purchases_admin_page(): string {
        return self::plugin_path() . 'admin/digital/purchases/index.php';
    }

    public static function digital_sales_stats_admin_page(): string {
        return self::plugin_path() . 'admin/digital/stats.php';
    }

    public static function digital_purchase_view_admin_page(): string {
        return self::plugin_path() . 'admin/digital/purchases/view.php';
    }    

    public static function digital_purchase_resend_email_admin_page(): string {
        return self::plugin_path() . 'admin/digital/purchases/resend_email.php';
    }

    public static function digital_purchase_regenerate_token_admin_page(): string {
        return self::plugin_path() . 'admin/digital/purchases/regenerate_token.php';
    }

    public static function digital_purchase_extend_token_admin_page(): string {
        return self::plugin_path() . 'admin/digital/purchases/extend_token.php';
    }

    public static function digital_purchase_cancel_admin_page(): string {
        return self::plugin_path() . 'admin/digital/purchases/cancel.php';
    }

    public static function digital_purchase_check_provider_admin_page(): string {
        return self::plugin_path() . 'admin/digital/purchases/check_provider.php';
    }

    public static function boutique_page(): string {
        return '/boutique';
    }
    
    public static function admin_user_email_page(): string {
        return self::plugin_path() . 'admin/users/email.php';
    }

    public static function admin_user_reset_password_page(): string {
        return self::plugin_path() . 'admin/users/reset_password.php';
    }

    public static function admin_user_add_note_page(): string {
        return self::plugin_path() . 'admin/users/add_note.php';
    }

    public static function admin_user_subscription_quick_action_page(): string {
        return self::plugin_path() . 'admin/users/subscription_quick_action.php';
    }

    public static function admin_user_toggle_tag_page(): string {
        return self::plugin_path() . 'admin/users/toggle_tag.php';
    }    
    public static function command_center_search_ajax(): string {
        return self::plugin_path() . 'ajax/command_center_search.php';
    }

    public static function command_center_execute_ajax(): string {
        return self::plugin_path() . 'ajax/command_center_execute.php';
    }

    public static function admin_user_timeline_ajax_page(): string {
        return self::plugin_path()
            . 'ajax/user_timeline.php';
    }    

    /**
     * Returns the Inbox thread preview AJAX endpoint.
     */
    public static function ajax_inbox_thread_preview_page(): string {
        return self::plugin_path()
            . 'ajax/inbox_thread_preview.php';
    }    

    public static function automation_rules_admin_page(): string {
        return self::plugin_path() . 'admin/automations/index.php';
    }

    public static function automation_history_admin_page(): string {
        return self::plugin_path() . 'admin/automations/history.php';
    }

    public static function automation_toggle_admin_page(): string {
        return self::plugin_path() . 'admin/automations/toggle.php';
    }    

    public static function admin_help_page(): string {
        return self::plugin_path() . 'admin/help/index.php';
    }

    public static function admin_help_article_page(): string {
        return self::plugin_path() . 'admin/help/article.php';
    }

    public static function admin_help_onboarding_action_page(): string {
        return self::plugin_path() .
            'admin/help/onboarding_action.php';
    }

    public static function admin_help_guide_page(): string {
        return self::plugin_path() .
            'admin/help/guide.php';
    }

    public static function admin_help_guide_action_page(): string {
        return self::plugin_path() .
            'admin/help/guide_action.php';
    }

    public static function admin_help_diagnostics_page(): string {
        return self::plugin_path() .
            'admin/help/diagnostics.php';
    }
    
    public static function admin_user_explorer_action_page(): string {
        return self::plugin_path() .
            'admin/users/explorer_action.php';
    }

    public static function admin_user_explorer_export_page(): string {
        return self::plugin_path() .
            'admin/users/export.php';
    }

    public static function admin_inbox_page(): string {
        return self::plugin_path() .
            'admin/inbox/index.php';
    }

    public static function admin_inbox_thread_page(): string {
        return self::plugin_path() .
            'admin/inbox/thread.php';
    }

    public static function admin_inbox_reply_page(): string {
        return self::plugin_path() .
            'admin/inbox/reply.php';
    }

    public static function admin_inbox_action_page(): string {
        return self::plugin_path() .
            'admin/inbox/action.php';
    }

    public static function admin_inbox_diagnostics_page(): string {
        return self::plugin_path() .
            'admin/inbox/diagnostics.php';
    }

    public static function admin_inbox_ai_action_page(): string {
        return self::plugin_path() .
            'admin/inbox/ai_action.php';
    }

    public static function admin_inbox_ai_diagnostics_page(): string {
        return self::plugin_path() .
            'admin/inbox/ai_diagnostics.php';
    }

    public static function plugin_dir(): string {
        global $CFG;

        return $CFG->dirroot . '/local/subscriptions/';
    }

    public static function help_content_dir(): string {
        return self::plugin_dir() . 'help/';
    }

    public static function admin_work_items_page(): string {
        return self::plugin_path() . 'admin/work/index.php';
    }

    public static function admin_work_item_view_page(): string {
        return self::plugin_path() . 'admin/work/view.php';
    }

    public static function admin_work_item_create_page(): string {
        return self::plugin_path() . 'admin/work/create.php';
    }

    public static function admin_work_item_action_page(): string {
        return self::plugin_path() . 'admin/work/action.php';
    }

    public static function admin_work_teams_page(): string {
        return self::plugin_path() . 'admin/work/teams.php';
    }

    public static function admin_work_team_action_page(): string {
        return self::plugin_path() . 'admin/work/team_action.php';
    }
    
    public static function admin_crm_assistant_page(): string {
        return self::plugin_path() .
            'admin/assistant/index.php';
    }

    public static function admin_crm_assistant_action_page(): string {
        return self::plugin_path() .
            'admin/assistant/action.php';
    }

    public static function admin_crm_assistant_work_item_page(): string {
        return self::plugin_path() .
            'admin/assistant/work_item.php';
    }

    public static function admin_customer_success_plan_page(): string {
        return self::plugin_path() .
            'admin/assistant/plan.php';
    }

    public static function admin_customer_success_plan_action_page(): string {
        return self::plugin_path() .
            'admin/assistant/plan_action.php';
    }

    public static function admin_customer_success_plan_confirm_page(): string {
        return self::plugin_path() .
            'admin/assistant/plan_action_confirm.php';
    }

    public static function admin_crm_tools_page(): string {
        return self::plugin_path() .
            'admin/tools/index.php';
    }

    public static function admin_crm_tool_action_page(): string {
        return self::plugin_path() .
            'admin/tools/action.php';
    }

    public static function admin_crm_tool_history_page(): string {
        return self::plugin_path() .
            'admin/tools/history.php';
    }

    /**
     * Returns the CRM brand logo image URL.
     *
     * @return \moodle_url
     */
    public static function crm_brand_logo_url(): \moodle_url {
        global $OUTPUT;

        return $OUTPUT->image_url(
            'branding/campusfr-crm',
            'local_subscriptions'
        );
    }

    public static function plugin_stylesheet_page(): string {
        return self::plugin_path() .
            'styles.css';
    }

    public static function crm_assistant_ai_endpoint(): string {
        return self::plugin_path() .
            'ajax/crm_assistant_ask.php';
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

    public static function inbox_attachment_url(
        int $fileitemid,
        string $filename
    ): \moodle_url {
        global $CFG;

        return \moodle_url::make_pluginfile_url(
            \context_system::instance()->id,
            'local_subscriptions',
            'inbox_attachment',
            $fileitemid,
            '/',
            $filename,
            true
        );
    }

}
