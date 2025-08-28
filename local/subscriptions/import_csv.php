<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib/lib_csv.php');
require_once(__DIR__ . '/renderer/user_subs_renderer.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

use local_subscriptions\subscription_manager;
use local_subscriptions\subscription_config;

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url(subscription_config::import_csv_page()));
$PAGE->set_title(get_string('import_subscriptions', 'local_subscriptions'));
$PAGE->set_heading(get_string('import_subscriptions_csv', 'local_subscriptions'));
$PAGE->requires->css('/local/subscriptions/styles.css');

echo $OUTPUT->header();

$renderer = new local_subscriptions_user_subs_renderer($PAGE, $OUTPUT);

// Gestion du formulaire de téléchargement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csvfile'])) {
    $tmp = $_FILES['csvfile']['tmp_name'];

	[$rows, $validrows, $headers] = parse_csv_file($tmp);

    $importid = time();
    $tempfile = make_request_directory() . "/csv_import_$importid.csv";
    //file_put_contents($tempfile, $content);



    // Sécurité + copie
    if (!is_uploaded_file($tmp)) {
        throw new moodle_exception('invalidcsvupload', 'local_subscriptions');
    }
    if (!@copy($tmp, $tempfile)) { // (ou move_uploaded_file($tmp, $tempfile))
        throw new moodle_exception('csvwritefail', 'local_subscriptions');
    }

    // Formulaire de confirmation
	echo $renderer->render_import_confirmation_form($validrows, $importid);

    // ➕ Message récapitulatif
    $total = count($rows);
    $valid = count($validrows);
    $ignored = $total - $valid;	
    
    if (!empty($rows)) {	
		echo $renderer->render_import_preview_table($rows, $headers);
    }

	echo html_writer::tag('p', get_string('import_preview', 'local_subscriptions'));
	echo $renderer->render_import_actions_and_summary($ignored);	
    echo html_writer::end_tag('form');
	echo $renderer->render_import_checkbox_script();
    echo $OUTPUT->footer();
    exit;
}

echo $renderer->render_csv_upload_form();
echo $OUTPUT->footer();