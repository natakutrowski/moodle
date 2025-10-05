<?php

require('../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/local/subscriptions/forms/plan_translation_form.php');
require_once($CFG->dirroot . '/local/subscriptions/lib/plans_lib.php');
require_once($CFG->dirroot . '/local/subscriptions/renderer/plans_renderer.php');

use local_subscriptions\subscription_config;

subscription_config::guard_public_access();

global $DB;

require_login();
require_capability('moodle/site:config', context_system::instance());

$planid = optional_param('planid', 0, PARAM_INT);
$editing = optional_param('edit', 0, PARAM_INT);
$adding = optional_param('add', 0, PARAM_INT);
$deleteid = optional_param('del', 0, PARAM_INT);

$plans = $DB->get_records('subscription_plan', null, 'name ASC');
$translations = local_subscriptions_get_plan_translations($planid);

// Suppression
if ($deleteid && confirm_sesskey()) {
    local_subscriptions_delete_plan_translation($deleteid);
    redirect(new moodle_url(subscription_config::plans_translations_page()));
}

// Traitement du formulaire (après affichage)
if (optional_param('submittranslation', false, PARAM_RAW)) {
    local_subscriptions_save_plan_translation();
}

$PAGE->set_url(new moodle_url(subscription_config::plans_translations_page()));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('translationspagetitle', 'local_subscriptions'));
$PAGE->set_heading(get_string('translationspagetitle', 'local_subscriptions'));
$PAGE->requires->js_call_amd('local_subscriptions/deleteplantranslation', 'init');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('translationspagetitle', 'local_subscriptions'));

// Table
echo local_subscriptions_plans_renderer::local_subscriptions_render_plans_translations_table($plans, $translations, $planid, $adding, $editing);

// Boutons retour + "Afficher tout"
$returnurl = new moodle_url(subscription_config::manage_page(), ['tab' => 'plans']);
$clearurl = new moodle_url(subscription_config::plans_translations_page());
$buttons = html_writer::link($returnurl, '← ' . get_string('backtoplanlist', 'local_subscriptions'), ['class' => 'btn btn-link']);
if ($planid) {
    $buttons .= html_writer::link($clearurl, get_string('showalltranslations', 'local_subscriptions'), [
        'class' => 'btn btn-secondary', 'style' => 'margin-left: 10px;'
    ]);
}
echo html_writer::div($buttons, 'd-flex justify-content-start align-items-center', ['style' => 'margin-top: 30px; gap: 10px;']);

// Formulaire
if ($editing || $adding) {
    require_sesskey();

    $translation = null;
    $plan = $editing
        ? $DB->get_record('subscription_plan', ['id' => $DB->get_field('subscription_plan_translation', 'planid', ['id' => $editing])], '*', MUST_EXIST)
        : $DB->get_record('subscription_plan', ['id' => $adding], '*', MUST_EXIST);

    if ($editing) {
        $translation = $DB->get_record('subscription_plan_translation', ['id' => $editing], '*', MUST_EXIST);
    }

    echo html_writer::div('', '', ['style' => 'margin-top: 30px;']);
    echo $OUTPUT->heading(get_string($editing ? 'edittranslation' : 'newtranslation', 'local_subscriptions'));
    $form = new plan_translation_form(null, [
        'translation' => $translation,
        'plan' => $plan,
        'editing' => $editing
    ]);
    $form->display();
}

echo $OUTPUT->footer();