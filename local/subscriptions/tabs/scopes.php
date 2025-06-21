<?php

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/formslib.php');

require_once($CFG->dirroot . '/local/subscriptions/forms/access_scope_form.php');
require_once($CFG->dirroot . '/local/subscriptions/lib/scopes_lib.php');
require_once($CFG->dirroot . '/local/subscriptions/renderer/scopes_renderer.php');
require_once($CFG->dirroot . '/local/subscriptions/classes/subscription_config.php');

use local_subscriptions\subscription_config;

global $DB, $OUTPUT, $PAGE;

$currentlang = current_language();
$id     = optional_param('id', 0, PARAM_INT);
$edit   = optional_param('edit', 0, PARAM_INT);
$add    = optional_param('add', 0, PARAM_BOOL);
$delete = optional_param('delete', 0, PARAM_INT);

$scope = null;
$course_ids = [];

if ($edit) {
    list($scope, $course_ids) = local_subscriptions_get_scope_for_edit($edit);
}

if ($delete) {
    local_subscriptions_delete_scope($delete);
}

$mform = new access_scope_form(null, ['course_ids' => implode(',', $course_ids)]);
if ($mform->is_cancelled()) {
    redirect(new moodle_url(subscription_config::manage_plans_page(), ['tab' => 'scopes']));
} elseif ($data = $mform->get_data()) {
    $record = (object)[
        'name'         => $data->name,
        'course_ids'   => is_array($data->course_ids) ? implode(',', $data->course_ids) : '',
        'last_update'  => time()
    ];

    if ($data->id) {
        $record->id = $data->id;
        $DB->update_record('subscription_access_scope', $record);
    } else {
        $record->creation_date = time();
        $newid = $DB->insert_record('subscription_access_scope', $record);

        if ($newid) {
            redirect(
                new moodle_url(subscription_config::scopes_translations_page(), [
                    'scopeid' => $newid,
                    'add' => $newid,
                    'sesskey' => sesskey()
                ]),
                get_string('scopecreated', 'local_subscriptions'),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
        } else {
            redirect(
                new moodle_url(subscription_config::manage_plans_page(), ['tab' => 'scopes']),
                get_string('scopecreateerror', 'local_subscriptions'),
                null,
                \core\output\notification::NOTIFY_ERROR
            );
        }
    }

    redirect(new moodle_url(subscription_config::manage_plans_page(), ['tab' => 'scopes']));

} else {
	if (!$edit && !$add) {
		if (($_SERVER['REQUEST_METHOD'] === 'POST') && (!isset($data) || empty($data) || is_null($data))) {
			if (!empty($_POST['name'])) {
				echo $OUTPUT->notification(get_string('error_scope_name_exists', 'local_subscriptions'), \core\output\notification::NOTIFY_ERROR);
			} else {
				echo $OUTPUT->notification(get_string('scopecreateerror', 'local_subscriptions'), \core\output\notification::NOTIFY_ERROR);
			}
		}		
	}
}

// 🔘 Bouton ajouter
if (!$edit && !$add) {
    echo local_subscriptions_scopes_renderer::render_add_button(
        new moodle_url(subscription_config::manage_plans_page(),['tab' => 'scopes', 'add' => 1])
    );

    echo $OUTPUT->heading(get_string('scopelist', 'local_subscriptions'));

	$order = optional_param('order', 'name', PARAM_ALPHA);
	$dir = optional_param('dir', 'asc', PARAM_ALPHA);
	
	$validorders = ['name'];
	$validdirs = ['asc', 'desc'];
	
	if (!in_array($order, $validorders)) {
		$order = 'name';
	}
	if (!in_array($dir, $validdirs)) {
		$dir = 'asc';
	}
	
	$dir = strtolower($dir);
	if ($dir !== 'asc' && $dir !== 'desc') {
		$dir = 'asc';
	}
	
	$orderdir = strtoupper($dir); // 'ASC' ou 'DESC' sécurisé

    $scopes = local_subscriptions_get_all_scopes_with_translations($currentlang, $orderdir);
    echo local_subscriptions_scopes_renderer::render_scopes_table($scopes, $currentlang, $order, $dir);
}

// 📋 Formulaire
if ($edit || $add) {
    echo html_writer::start_div('scope-form-container', ['style' => 'margin-top: 2em;']);
    echo $OUTPUT->heading($edit ? get_string('edit', 'local_subscriptions') : get_string('add', 'local_subscriptions'), 3);
    $mform->set_data((object)[
        'id' => $scope->id ?? null,
        'name' => $scope->name ?? '',
        'course_ids' => explode(',', $scope->course_ids ?? '')
    ]);
    $mform->display();
    echo html_writer::end_div();
}

$PAGE->requires->js_call_amd('local_subscriptions/deletescope', 'init');