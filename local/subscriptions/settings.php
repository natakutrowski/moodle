<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_subscriptions_manage',
        get_string('active_subscriptions', 'local_subscriptions'),
        new moodle_url('/local/subscriptions/manage.php')
    ));

	$ADMIN->add('localplugins', new admin_externalpage(
		'local_subscriptions_manualenrol',
		get_string('manual_enrol', 'local_subscriptions'),
		new moodle_url('/local/subscriptions/manual_enrol.php')
	));
	
	$ADMIN->add('localplugins', new admin_externalpage(
		'local_subscriptions_importcsv',
		get_string('import_csv', 'local_subscriptions'),
		new moodle_url('/local/subscriptions/import_csv.php'),
		'moodle/site:config'
	));

}
