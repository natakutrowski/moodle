<?php
require('../../config.php');
require_once($CFG->dirroot . '/local/subscriptions/forms/plan_price_form.php');
require_once($CFG->dirroot . '/local/subscriptions/lib/plans_lib.php');
require_once($CFG->dirroot . '/local/subscriptions/renderer/plans_renderer.php');

use local_subscriptions\subscription_config;

global $DB;

$planid = optional_param('planid', 0, PARAM_INT);
if (!$planid) {
    redirect(new moodle_url(subscription_config::manage_page(), ['tab' => 'plans']));
}
$add = optional_param('add', 0, PARAM_BOOL);
$edit   = optional_param('edit', 0, PARAM_INT);
$delete = optional_param('del', 0, PARAM_INT);

// Vérifie que le plan existe
$plan = $DB->get_record('subscription_plan', ['id' => $planid], '*', MUST_EXIST);

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url(subscription_config::plans_prices_page(),['planid' => $planid]));
$PAGE->set_title(get_string('planprices', 'local_subscriptions'));
$PAGE->set_heading(get_string('planprices', 'local_subscriptions'));
$PAGE->requires->js_call_amd('local_subscriptions/deleteprice', 'init');


echo $OUTPUT->header();

if ($delete && confirm_sesskey()) {
    local_subscriptions_delete_price($delete);
    redirect(new moodle_url(subscription_config::plans_prices_page(), ['planid' => $planid])
, get_string('pricedeleted', 'local_subscriptions'), null, \core\output\notification::NOTIFY_SUCCESS);
}

// 🧾 Récupère les prix
$prices = local_subscriptions_get_plan_prices($planid);
echo $OUTPUT->heading(get_string('planpricesfor', 'local_subscriptions', '"'.$plan->name.'"'), 3);

echo local_subscriptions_plans_renderer::render_prices_table($prices);

// On prépare les données personnalisées pour le formulaire :
$formdata = ['planid' => $planid];

// Si on est en mode édition, on ajoute la devise en cours d'édition
if ($edit) {
    $price = local_subscriptions_get_price($edit);
    $formdata['editingcurrency'] = $price->currency;
}

$mform = new plan_price_form(null, $formdata);

if ($mform->is_cancelled()) {
    redirect(new moodle_url(subscription_config::plans_prices_page(), ['planid' => $planid]));
} elseif ($data = $mform->get_data()) {

    if (!isset($data->currency)) {
        $data->currency = $DB->get_field('subscription_plan_price', 'currency', ['id' => $data->id]);
    }

    $record = (object)[
        'plan_id' => $planid,
        'currency' => strtoupper(trim($data->currency)),
        'price' => $data->price
    ];

    if (!empty($data->id)) {
        $record->id = $data->id;
        $DB->update_record('subscription_plan_price', $record);
        redirect(new moodle_url(subscription_config::plans_prices_page(), ['planid' => $planid])
        , get_string('priceupdated', 'local_subscriptions'), null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        $DB->insert_record('subscription_plan_price', $record);
        redirect(new moodle_url(subscription_config::plans_prices_page(), ['planid' => $planid])
        , get_string('priceadded', 'local_subscriptions'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

// Boutons retour
$returnurl = new moodle_url(subscription_config::manage_page(), ['tab' => 'plans']);
$buttons = html_writer::link($returnurl, '← ' . get_string('backtoplanlist', 'local_subscriptions'), ['class' => 'btn btn-link']);
echo html_writer::div($buttons, 'd-flex justify-content-start align-items-center', ['style' => 'margin-top: 30px; gap: 10px;']);

// 📋 Formulaire
if ($edit) {
    $price = local_subscriptions_get_price($edit);
    $mform->set_data($price);
    var_dump($price);
    echo $OUTPUT->heading(get_string('editprice', 'local_subscriptions'), 4);
    $mform->display();
} elseif ($add) {
    $mform->set_data((object)['planid' => $planid]);
    echo $OUTPUT->heading(get_string('addprice', 'local_subscriptions'), 4);
    $mform->display();
} else {
    // Bouton Ajouter
    echo html_writer::link(new moodle_url(subscription_config::plans_prices_page(), ['planid' => $planid, 'add' => 1]), '➕ ' . get_string('addprice', 'local_subscriptions'), [
        'class' => 'btn btn-primary',
        'style' => 'margin-bottom: 1em; display: inline-block;'
    ]);
}

echo $OUTPUT->footer();
