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
        new moodle_url(subscription_config::manage_subscription_page()),
		'moodle/site:config'
    ));

	$ADMIN->add('subscriptions_category', new admin_externalpage(
		'local_subscriptions_add_subscription',
		get_string('add_subscription', 'local_subscriptions'),
		new moodle_url(subscription_config::add_subscription_page()),
		'moodle/site:config'
	));
	
	$ADMIN->add('subscriptions_category', new admin_externalpage(
		'local_subscriptions_import_csv',
		get_string('import_subscriptions_csv', 'local_subscriptions'),
		new moodle_url(subscription_config::import_csv_page()),
		'moodle/site:config'
	));

}
