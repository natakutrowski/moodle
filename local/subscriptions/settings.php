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
	
    // Le nœud "Local plugins" existe déjà. On ajoute une page réglages pour ton plugin.
    $settings = new admin_settingpage('local_subscriptions_settings', get_string('pluginname', 'local_subscriptions'));

    if ($ADMIN->fulltree) {
        // Section Stripe.
        $settings->add(new admin_setting_heading(
            'local_subscriptions/stripeheading',
            get_string('stripe_heading', 'local_subscriptions'),
            get_string('stripe_heading_desc', 'local_subscriptions')
        ));

        // Publishable key (utile côté front si un jour tu utilises Elements).
        $settings->add(new admin_setting_configtext(
            'local_subscriptions/stripe_publishable',
            get_string('stripe_publishable', 'local_subscriptions'),
            get_string('stripe_publishable_desc', 'local_subscriptions'),
            '', PARAM_RAW_TRIMMED
        ));

        // Secret key (test/live) — champ masqué.
        $settings->add(new admin_setting_configpasswordunmask(
            'local_subscriptions/stripe_secret_key',
            get_string('stripe_secret', 'local_subscriptions'),
            get_string('stripe_secret_desc', 'local_subscriptions'),
            ''
        ));

        // Webhook secret — champ masqué (optionnel au début).
        $settings->add(new admin_setting_configpasswordunmask(
            'local_subscriptions/stripe_webhook_secret',
            get_string('stripe_webhook_secret', 'local_subscriptions'),
            get_string('stripe_webhook_secret_desc', 'local_subscriptions'),
            ''
        ));

        $settings->add(new admin_setting_configtext(
            'local_subscriptions/brand_logo_url',
        get_string('brand_logo_url_label', 'local_subscriptions'),
        get_string('brand_logo_url_desc', 'local_subscriptions'),
            '', PARAM_URL
        ));

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

        $settings->add(new admin_setting_configtext(
            'local_subscriptions/stripe_portal_configuration_id',
            get_string('stripe_portal_configuration_id', 'local_subscriptions'),
            get_string('stripe_portal_configuration_id_desc', 'local_subscriptions'),
            '', PARAM_RAW_TRIMMED
        ));

        $settings->add(new admin_setting_configcheckbox(
            'local_subscriptions/email_show_pr_ref',
            get_string('email_show_pr_ref', 'local_subscriptions'),
            get_string('email_show_pr_ref_desc', 'local_subscriptions'),
            0
        ));

        // === Alfa Bank ===
        $settings->add(new admin_setting_heading(
            'local_subscriptions_alfa_hdr',
            get_string('alfa_settings_header', 'local_subscriptions'),
            ''
        ));

        $settings->add(new admin_setting_configtext(
            'local_subscriptions/alfa_api_base',
            get_string('alfa_api_base', 'local_subscriptions'),
            get_string('alfa_api_base_desc', 'local_subscriptions'),
            'https://alfa.rbsuat.com',
            PARAM_URL
        ));

        $settings->add(new admin_setting_configselect(
            'local_subscriptions/alfa_mode',
            get_string('alfa_mode', 'local_subscriptions'),
            get_string('alfa_mode_desc', 'local_subscriptions'),
            'test',
            ['test' => 'Test', 'live' => 'Live']
        ));

        $settings->add(new admin_setting_configtext(
            'local_subscriptions/alfa_username',
            get_string('alfa_username', 'local_subscriptions'),
            '',
            '',
            PARAM_RAW_TRIMMED
        ));

        $settings->add(new admin_setting_configpasswordunmask(
            'local_subscriptions/alfa_password',
            get_string('alfa_password', 'local_subscriptions'),
            '',
            ''
        ));

        $settings->add(new admin_setting_configpasswordunmask(
            'local_subscriptions/alfa_token',
            get_string('alfa_token', 'local_subscriptions'),
            get_string('alfa_token_desc', 'local_subscriptions'),
            ''
        ));

        $settings->add(new admin_setting_configpasswordunmask(
            'local_subscriptions/alfa_webhook_secret',
            get_string('alfa_webhook_secret', 'local_subscriptions'),
            '',
            ''
        ));



    }

    $ADMIN->add('localplugins', $settings);

}
