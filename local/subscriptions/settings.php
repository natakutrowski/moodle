<?php
defined('MOODLE_INTERNAL') || die();

use local_subscriptions\subscription_config;

if ($hassiteconfig) {

	// Crée une catégorie principale "Subscriptions"
	$ADMIN->add('localplugins', new admin_category(
		'subscriptions_category', 
		'🧾 ' . get_string('pluginname', 'local_subscriptions')));

	// Ajoute les pages dans la catégorie

    $ADMIN->add('subscriptions_category', new admin_externalpage(
        'local_subscriptions_manage_subscription',
        get_string('manage_subscriptions', 'local_subscriptions'),
        new moodle_url(subscription_config::manage_page()),
		'moodle/site:config'
    ));

	$ADMIN->add('subscriptions_category', new admin_externalpage(
		'local_subscriptions_add_subscription',
		get_string('add_subscription', 'local_subscriptions'),
		new moodle_url(subscription_config::add_manual_subscription_page()),
		'moodle/site:config'
	));
	
	$ADMIN->add('subscriptions_category', new admin_externalpage(
		'local_subscriptions_import_csv',
		get_string('import_subscriptions_csv', 'local_subscriptions'),
		new moodle_url(subscription_config::import_csv_page()),
		'moodle/site:config'
	));

    $ADMIN->add('localplugins', new admin_externalpage(
        'local_subscriptions_export',
        'Export des souscriptions',
        new moodle_url('/local/subscriptions/admin/subscriptions_export.php'),
        'moodle/site:config'
    ));

    $ADMIN->add('subscriptions_category', new admin_externalpage(
        'local_subscriptions_digital_purchases',
        get_string('digital_purchases_title', 'local_subscriptions'),
        new moodle_url('/local/subscriptions/admin/digital_purchases.php'),
        'moodle/site:config'
    ));
        
    // Le nœud "Local plugins" existe déjà. On ajoute une page réglages pour ton plugin.
    $settings = new admin_settingpage('local_subscriptions_settings', get_string('pluginname', 'local_subscriptions'));

    if ($ADMIN->fulltree) {

        $settings->add(new admin_setting_configselect(
            'local_subscriptions/availability_mode',
            get_string('availability_mode', 'local_subscriptions'),
            get_string('availability_mode_desc', 'local_subscriptions'),
            'enabled',
            [
                'enabled'   => get_string('availability_enabled', 'local_subscriptions'),
                'adminonly' => get_string('availability_adminonly', 'local_subscriptions'),
                'disabled'  => get_string('availability_disabled', 'local_subscriptions'),
            ]
        ));    
        
        // Liste des langues installées.
        $langs = get_string_manager()->get_list_of_translations(); // ['en'=>'English (en)', 'fr'=>'Français (fr)', ...]
        $options = ['' => get_string('settings:sitedefault', 'local_subscriptions')] + $langs;

        // 1) Langue par défaut pour les nouveaux comptes.
        $settings->add(new admin_setting_configselect(
            'local_subscriptions/defaultuserlang',
            get_string('settings:defaultuserlang', 'local_subscriptions'),
            get_string('settings:defaultuserlang_desc', 'local_subscriptions'),
            '', // vide => hérite de la langue du site
            $options
        ));

        // 2) Langue utilisée pour les e-mails du plugin.
        $settings->add(new admin_setting_configselect(
            'local_subscriptions/defaultemaillang',
            get_string('settings:defaultemaillang', 'local_subscriptions'),
            get_string('settings:defaultemaillang_desc', 'local_subscriptions'),
            '', // vide => langue de l’utilisateur ou langue du site
            $options
        ));

        $settings->add(new admin_setting_configcheckbox(
            'local_subscriptions/email_copy_verbose',
            get_string('email_copy_verbose', 'local_subscriptions'),
            get_string('email_copy_verbose_desc', 'local_subscriptions'),
            1
        ));

        $settings->add(new admin_setting_configcheckbox(
            'local_subscriptions/display_currency_symbols',
            get_string('set_display_currency_symbols', 'local_subscriptions'),
            get_string('set_display_currency_symbols_desc', 'local_subscriptions'),
            1
        ));

        // === Providers (global) ====================================================
        $settings->add(new admin_setting_heading(
            'local_subscriptions_providers_hdr',
            get_string('providers_header', 'local_subscriptions'),
            ''
        ));

        // Provider par défaut (quand rien ne force Stripe/Alfa)
        $settings->add(new admin_setting_configselect(
            'local_subscriptions/provider_default',
            get_string('provider_default', 'local_subscriptions'),
            get_string('provider_default_desc', 'local_subscriptions'),
            'stripe',
            [
                'stripe' => get_string('provider_stripe', 'local_subscriptions'),
                'alfa'   => get_string('provider_alfa',   'local_subscriptions'),
            ]
        ));

        // === Stripe ================================================================
        $settings->add(new admin_setting_heading(
            'local_subscriptions_stripe_hdr',
            get_string('provider_stripe', 'local_subscriptions'),
            ''
        ));

        // Environnement Stripe: test/live
        $settings->add(new admin_setting_configselect(
            'local_subscriptions/stripe_env',
            get_string('env_mode', 'local_subscriptions'),
            get_string('env_mode_desc', 'local_subscriptions'),
            'test',
            ['test' => get_string('env_test', 'local_subscriptions'),
            'live' => get_string('env_live', 'local_subscriptions')]
        ));

        // TEST keys
        $settings->add(new admin_setting_configpasswordunmask(
            'local_subscriptions/stripe_test_secret',
            get_string('stripe_secret_test', 'local_subscriptions'),
            '', ''
        ));
        $settings->add(new admin_setting_configtext(
            'local_subscriptions/stripe_test_publishable',
            get_string('stripe_publishable_test', 'local_subscriptions'),
            '', '', PARAM_RAW_TRIMMED
        ));
        $settings->add(new admin_setting_configpasswordunmask(
            'local_subscriptions/stripe_test_webhook_secret',
            get_string('stripe_webhook_secret_test', 'local_subscriptions'),
            '', ''
        ));

        $settings->add(new admin_setting_configtext(
            'local_subscriptions/stripe_test_portal_configuration_id',
            get_string('stripe_portal_configuration_id_test', 'local_subscriptions'),
            get_string('stripe_portal_configuration_id_desc', 'local_subscriptions'),
            '', PARAM_RAW_TRIMMED
        ));

        // LIVE keys
        $settings->add(new admin_setting_configpasswordunmask(
            'local_subscriptions/stripe_live_secret',
            get_string('stripe_secret_live', 'local_subscriptions'),
            '', ''
        ));
        $settings->add(new admin_setting_configtext(
            'local_subscriptions/stripe_live_publishable',
            get_string('stripe_publishable_live', 'local_subscriptions'),
            '', '', PARAM_RAW_TRIMMED
        ));
        $settings->add(new admin_setting_configpasswordunmask(
            'local_subscriptions/stripe_live_webhook_secret',
            get_string('stripe_webhook_secret_live', 'local_subscriptions'),
            '', ''
        ));

        $settings->add(new admin_setting_configtext(
            'local_subscriptions/stripe_live_portal_configuration_id',
            get_string('stripe_portal_configuration_id_live', 'local_subscriptions'),
            get_string('stripe_portal_configuration_id_desc', 'local_subscriptions'),
            '', PARAM_RAW_TRIMMED
        ));

        // === Alfa Bank =============================================================
        $settings->add(new admin_setting_heading(
            'local_subscriptions_alfa_hdr',
            get_string('alfa_settings_header', 'local_subscriptions'),
            ''
        ));

        // Environnement Alfa: test/live
        $settings->add(new admin_setting_configselect(
            'local_subscriptions/alfa_env',
            get_string('env_mode', 'local_subscriptions'),
            get_string('env_mode_desc', 'local_subscriptions'),
            'test',
            ['test' => get_string('env_test', 'local_subscriptions'),
            'live' => get_string('env_live', 'local_subscriptions')]
        ));

        // TEST creds
        $settings->add(new admin_setting_configtext(
            'local_subscriptions/alfa_test_api_base',
            get_string('alfa_api_base_test', 'local_subscriptions'),
            'https://alfa.rbsuat.com', 'https://alfa.rbsuat.com', PARAM_URL
        ));
        $settings->add(new admin_setting_configtext(
            'local_subscriptions/alfa_test_username',
            get_string('alfa_username_test', 'local_subscriptions'),
            '', '', PARAM_RAW_TRIMMED
        ));
        $settings->add(new admin_setting_configpasswordunmask(
            'local_subscriptions/alfa_test_password',
            get_string('alfa_password_test', 'local_subscriptions'),
            '', ''
        ));
        $settings->add(new admin_setting_configpasswordunmask(
            'local_subscriptions/alfa_test_token',
            get_string('alfa_token_test', 'local_subscriptions'),
            '', ''
        ));
        $settings->add(new admin_setting_configpasswordunmask(
            'local_subscriptions/alfa_test_webhook_secret',
            get_string('alfa_webhook_secret_test', 'local_subscriptions'),
            '', ''
        ));

        // LIVE creds
        $settings->add(new admin_setting_configtext(
            'local_subscriptions/alfa_live_api_base',
            get_string('alfa_api_base_live', 'local_subscriptions'),
            '', '', PARAM_URL
        ));
        $settings->add(new admin_setting_configtext(
            'local_subscriptions/alfa_live_username',
            get_string('alfa_username_live', 'local_subscriptions'),
            '', '', PARAM_RAW_TRIMMED
        ));
        $settings->add(new admin_setting_configpasswordunmask(
            'local_subscriptions/alfa_live_password',
            get_string('alfa_password_live', 'local_subscriptions'),
            '', ''
        ));
        $settings->add(new admin_setting_configpasswordunmask(
            'local_subscriptions/alfa_live_token',
            get_string('alfa_token_live', 'local_subscriptions'),
            '', ''
        ));
        $settings->add(new admin_setting_configpasswordunmask(
            'local_subscriptions/alfa_live_webhook_secret',
            get_string('alfa_webhook_secret_live', 'local_subscriptions'),
            '', ''
        ));

        // === Misc. =============================================================
        $settings->add(new admin_setting_heading(
            'local_subs_email_heading',
        get_string('emails_links_heading', 'local_subscriptions'),
        get_string('emails_links_heading_desc', 'local_subscriptions')
        ));

        $settings->add(new admin_setting_configtext(
            'local_subscriptions/email_link_secret',
        get_string('email_link_secret_label', 'local_subscriptions'),
        get_string('email_link_secret_desc', 'local_subscriptions'),
            '', PARAM_RAW_TRIMMED
        ));

        $settings->add(new admin_setting_configtext(
            'local_subscriptions/support_email',
            get_string('settings_support_email', 'local_subscriptions'),
            get_string('settings_support_email_desc', 'local_subscriptions'),
            'support@campusfr.fr',
            PARAM_EMAIL
        ));

        $settings->add(new admin_setting_configtext(
            'local_subscriptions/brand_logo_url',
        get_string('brand_logo_url_label', 'local_subscriptions'),
        get_string('brand_logo_url_desc', 'local_subscriptions'),
            '', PARAM_URL
        ));

        $settings->add(new admin_setting_heading(
            'local_subscriptions/followups_heading',
        get_string('followups_heading', 'local_subscriptions'),
        get_string('followups_heading_desc', 'local_subscriptions')
        ));

        $settings->add(new admin_setting_configtext(
            'local_subscriptions/expire_pending_after_minutes',
        get_string('expire_pending_after_minutes_label', 'local_subscriptions'),
        get_string('expire_pending_after_minutes_desc', 'local_subscriptions'),
            60, PARAM_INT
        ));

        $settings->add(new admin_setting_configtext(
            'local_subscriptions/reminder1_after_minutes',
        get_string('reminder1_after_minutes_label', 'local_subscriptions'),
        get_string('reminder1_after_minutes_desc', 'local_subscriptions'),
            1440, PARAM_INT // 24 h
        ));

        $settings->add(new admin_setting_configtext(
            'local_subscriptions/reminder2_after_minutes',
        get_string('reminder2_after_minutes_label', 'local_subscriptions'),
        get_string('reminder2_after_minutes_desc', 'local_subscriptions'),
            4320, PARAM_INT // 72 h
        ));

        $settings->add(new admin_setting_configtext(
            'local_subscriptions/featured_planid',
        get_string('featured_planid_label', 'local_subscriptions'),
        get_string('featured_planid_desc', 'local_subscriptions'),
            '', PARAM_INT
        ));

        $settings->add(new admin_setting_configcheckbox(
            'local_subscriptions/email_show_pr_ref',
            get_string('email_show_pr_ref', 'local_subscriptions'),
            get_string('email_show_pr_ref_desc', 'local_subscriptions'),
            0
        ));

        // Pages politiques/CGU/CGV (RU vs ROW)
        $settings->add(new admin_setting_configtext(
            'local_subscriptions/policy_url_ru',
            get_string('policy_url_ru', 'local_subscriptions'),
            '', '', PARAM_URL
        ));
        $settings->add(new admin_setting_configtext(
            'local_subscriptions/policy_url_row',
            get_string('policy_url_row', 'local_subscriptions'),
            '', '', PARAM_URL
        ));
        $settings->add(new admin_setting_configtext(
            'local_subscriptions/terms_url_ru',
            get_string('terms_url_ru', 'local_subscriptions'),
            '', '', PARAM_URL
        ));
        $settings->add(new admin_setting_configtext(
            'local_subscriptions/terms_url_row',
            get_string('terms_url_row', 'local_subscriptions'),
            '', '', PARAM_URL
        ));
        $settings->add(new admin_setting_configtext(
            'local_subscriptions/offer_url_ru',
            get_string('offer_url_ru', 'local_subscriptions'),
            '', '', PARAM_URL
        ));
        $settings->add(new admin_setting_configtext(
            'local_subscriptions/offer_url_row',
            get_string('offer_url_row', 'local_subscriptions'),
            '', '', PARAM_URL
        ));

        $settings->add(new admin_setting_configtext(
            'local_subscriptions/email_copy_to',
            get_string('email_copy_to', 'local_subscriptions'),
            get_string('email_copy_to_desc', 'local_subscriptions'),
            'admin@campusfr.fr', PARAM_RAW_TRIMMED
        ));

        // --- Section Essai 7 jours ---
        $settings->add(new admin_setting_heading('ls_trial_heading',
            get_string('settings_trial_section', 'local_subscriptions'), ''));

        $settings->add(new admin_setting_configtext(
            'local_subscriptions/trial_plan_id',
            get_string('settings_trial_planid', 'local_subscriptions'),
            get_string('settings_trial_planid_desc', 'local_subscriptions'),
            0, PARAM_INT));

        $settings->add(new admin_setting_configtext(
            'local_subscriptions/trial_duration_days',
            get_string('settings_trial_duration_days', 'local_subscriptions'),
            get_string('settings_trial_duration_days_desc', 'local_subscriptions'),
            7, PARAM_INT));

        $settings->add(new admin_setting_configtext(
            'local_subscriptions/trial_discount_percent',
            get_string('settings_trial_discount_percent', 'local_subscriptions'),
            get_string('settings_trial_discount_percent_desc', 'local_subscriptions'),
            15, PARAM_INT));

        $settings->add(new admin_setting_configtext(
            'local_subscriptions/trial_discount_hours',
            get_string('settings_trial_discount_hours', 'local_subscriptions'),
            get_string('settings_trial_discount_hours_desc', 'local_subscriptions'),
            72, PARAM_INT));

        $settings->add(new admin_setting_heading('ls_paylock_heading',
            get_string('settings_paylock_section', 'local_subscriptions'), ''));

        $settings->add(new admin_setting_configcheckbox(
            'local_subscriptions/payments_lock_strict',
            get_string('settings_paylock_strict', 'local_subscriptions'),
            get_string('settings_paylock_strict_desc', 'local_subscriptions'),
            0
        ));

        $settings->add(new admin_setting_configtext(
            'local_subscriptions/payments_mismatch_tolerance_cents',
            get_string('settings_paylock_tolerance', 'local_subscriptions'),
            get_string('settings_paylock_tolerance_desc', 'local_subscriptions'),
            2, PARAM_INT
        ));

    }

    $ADMIN->add('localplugins', $settings);

}
