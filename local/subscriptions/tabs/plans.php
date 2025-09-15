<?php

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/formslib.php');

require_once($CFG->dirroot . '/local/subscriptions/forms/plan_form.php');
require_once($CFG->dirroot . '/local/subscriptions/lib/plans_lib.php');
require_once($CFG->dirroot . '/local/subscriptions/renderer/plans_renderer.php');
require_once($CFG->dirroot . '/local/subscriptions/classes/subscription_config.php');

use local_subscriptions\subscription_config;

global $DB, $OUTPUT, $PAGE;

$currentlang = current_language();
$id     = optional_param('id', 0, PARAM_INT);
$edit   = optional_param('edit', 0, PARAM_INT);
$add    = optional_param('add', 0, PARAM_BOOL);
$toggleid = optional_param('toggle', 0, PARAM_INT);


$plan = null;
$accessscopeid = null;
$duration_key = null;

if ($edit) {
    list($plan, $accessscopeid, $duration_key) = local_subscriptions_get_plan_for_edit($edit);
}

if ($toggleid && confirm_sesskey()) {
    $plantoggle = $DB->get_record('subscription_plan', ['id' => $toggleid], '*', MUST_EXIST);
    $DB->set_field('subscription_plan', 'is_active', $plantoggle->is_active ? 0 : 1, ['id' => $plantoggle->id]);

    redirect(new moodle_url(subscription_config::manage_page(), ['tab' => 'plans']),
        get_string('planstatusupdated', 'local_subscriptions'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$mform = new plan_form(new moodle_url(subscription_config::manage_page(), ['tab' => 'plans']), [
    'accessscopeid' => $accessscopeid, 'duration_key' => $duration_key
]);

if (!$edit && !$add) {
    if (($_SERVER['REQUEST_METHOD'] === 'POST') && (!isset($data) || empty($data) || is_null($data))) {
        if (!empty($_POST['name'])) {
            echo $OUTPUT->notification(get_string('error_plan_name_exists', 'local_subscriptions'), \core\output\notification::NOTIFY_ERROR);
        } else {
            echo $OUTPUT->notification(get_string('plancreateerror', 'local_subscriptions'), \core\output\notification::NOTIFY_ERROR);
        }
    }		
}

// 🔘 Bouton ajouter
if (!$edit && !$add) {
    echo local_subscriptions_plans_renderer::render_add_button(
        new moodle_url(subscription_config::manage_page(),['tab' => 'plans', 'add' => 1])
    );

    echo $OUTPUT->heading(get_string('planlist', 'local_subscriptions'));

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

    $plans = local_subscriptions_get_all_plans_with_translations($currentlang, $orderdir);
    echo local_subscriptions_plans_renderer::render_plans_table($plans, $currentlang, $order, $dir);
}

// 📋 Formulaire
if ($edit || $add) {
    echo html_writer::start_div('scope-form-container', ['style' => 'margin-top: 2em;']);
    echo $OUTPUT->heading($edit ? get_string('editplan', 'local_subscriptions') : get_string('addplan', 'local_subscriptions'), 3);
    	
	$mform->set_data((object)[
        'id' => $plan->id ?? null,
        'name' => $plan->name ?? '',
        'accessscopeid' => $plan->accessscopeid ?? '',
        'duration_key' => $plan->duration_key ?? '',
        'highlight_type' => $plan->highlight_type ?? '',
        'is_recurring' => isset($plan->is_recurring) ? (int)$plan->is_recurring : 0
    ]);
    $mform->display();
    echo html_writer::end_div();
}

$PAGE->requires->js_call_amd('local_subscriptions/toggleplan', 'init');
$PAGE->requires->js_call_amd('local_subscriptions/deleteplan', 'init');