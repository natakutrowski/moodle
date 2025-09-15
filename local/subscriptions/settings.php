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
            'Brand logo URL',
            'Absolute URL to a small logo (PNG/SVG, height ~32px) used in emails.',
            '', PARAM_URL
        ));

        $settings->add(new admin_setting_heading(
            'local_subs_email_heading',
            'Emails & liens',
            'Réglages pour les emails de suivi et les liens de reprise.'
        ));

        $settings->add(new admin_setting_configtext(
            'local_subscriptions/email_link_secret',
            'Secret pour les liens de reprise',
            'Chaîne utilisée pour signer les liens de relance (fallback : $CFG->passwordsaltmain).',
            '', PARAM_RAW_TRIMMED
        ));

        $settings->add(new admin_setting_configtext(
            'local_subscriptions/brand_logo_url',
            'Brand logo URL (emails)',
            'URL absolue d’un petit logo (PNG/SVG ~32px) affiché dans les e-mails.',
            '', PARAM_URL
        ));

        $settings->add(new admin_setting_heading(
            'local_subscriptions/followups_heading',
            'Relances & expiration',
            'Délais (en minutes) pour expirer et relancer.'
        ));

        $settings->add(new admin_setting_configtext(
            'local_subscriptions/expire_pending_after_minutes',
            'Expiration des paiements en attente',
            'Passer de pending → expired après N minutes sans paiement.',
            60, PARAM_INT
        ));

        $settings->add(new admin_setting_configtext(
            'local_subscriptions/reminder1_after_minutes',
            'Relance n°1',
            'Envoyer une première relance si status ∈ (pending, expired, failed) et ancienneté ≥ N minutes.',
            1440, PARAM_INT // 24 h
        ));

        $settings->add(new admin_setting_configtext(
            'local_subscriptions/reminder2_after_minutes',
            'Relance n°2',
            'Envoyer une seconde relance si toujours non payé et ancienneté ≥ N minutes (depuis la création).',
            4320, PARAM_INT // 72 h
        ));

        $settings->add(new admin_setting_configtext(
            'local_subscriptions/featured_planid',
            'Plan mis en avant',
            'ID du plan à mettre en avant sur la page des offres.',
            '', PARAM_INT
        ));

        $settings->add(new admin_setting_configtext(
            'local_subscriptions/stripe_portal_configuration_id',
            get_string('stripe_portal_configuration_id', 'local_subscriptions'),
            get_string('stripe_portal_configuration_id_desc', 'local_subscriptions'),
            '', PARAM_RAW_TRIMMED
        ));

    }

    $ADMIN->add('localplugins', $settings);

}
