<?php

require('../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/local/subscriptions/forms/access_scope_translation_form.php');
require_once($CFG->dirroot . '/local/subscriptions/lib/scopes_lib.php');
require_once($CFG->dirroot . '/local/subscriptions/renderer/scopes_renderer.php');

use local_subscriptions\subscription_config;

global $DB;

require_login();
require_capability('moodle/site:config', context_system::instance());

$PAGE->set_url(new moodle_url(subscription_config::scopes_translations_page()));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('translationspagetitle', 'local_subscriptions'));
$PAGE->set_heading(get_string('translationspagetitle', 'local_subscriptions'));
$PAGE->requires->js_call_amd('local_subscriptions/deletetranslation', 'init');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('translationspagetitle', 'local_subscriptions'));


$scopeid = optional_param('scopeid', 0, PARAM_INT);
$editing = optional_param('edit', 0, PARAM_INT);
$adding = optional_param('add', 0, PARAM_INT);
$deleteid = optional_param('del', 0, PARAM_INT);

$scopes = $DB->get_records('subscription_access_scope', null, 'name ASC');
$translations = local_subscriptions_get_scope_translations($scopeid);

// Suppression
if ($deleteid && confirm_sesskey()) {
    local_subscriptions_delete_scope_translation($deleteid);
    redirect(new moodle_url(subscription_config::scopes_translations_page()));
}

// Table
echo local_subscriptions_scopes_renderer::local_subscriptions_render_scopes_translations_table($scopes, $translations, $scopeid, $adding, $editing);

// Boutons retour + "Afficher tout"
$returnurl = new moodle_url(subscription_config::manage_page(), ['tab' => 'scopes']);
$clearurl = new moodle_url(subscription_config::scopes_translations_page());
$buttons = html_writer::link($returnurl, '← ' . get_string('backtoscopelist', 'local_subscriptions'), ['class' => 'btn btn-link']);
if ($scopeid) {
    $buttons .= html_writer::link($clearurl, get_string('showalltranslations', 'local_subscriptions'), [
        'class' => 'btn btn-secondary', 'style' => 'margin-left: 10px;'
    ]);
}
echo html_writer::div($buttons, 'd-flex justify-content-start align-items-center', ['style' => 'margin-top: 30px; gap: 10px;']);

// Formulaire
if ($editing || $adding) {
    require_sesskey();

    $translation = null;
    $scope = $editing
        ? $DB->get_record('subscription_access_scope', ['id' => $DB->get_field('subscription_access_scope_translation', 'scope_id', ['id' => $editing])], '*', MUST_EXIST)
        : $DB->get_record('subscription_access_scope', ['id' => $adding], '*', MUST_EXIST);

    if ($editing) {
        $translation = $DB->get_record('subscription_access_scope_translation', ['id' => $editing], '*', MUST_EXIST);
    }

    echo html_writer::div('', '', ['style' => 'margin-top: 30px;']);
    echo $OUTPUT->heading(get_string($editing ? 'edittranslation' : 'newtranslation', 'local_subscriptions'));
    $form = new access_scope_translation_form(null, [
        'translation' => $translation,
        'scope' => $scope,
        'editing' => $editing
    ]);
    $form->display();
}

// Traitement du formulaire (après affichage)
if (optional_param('submittranslation', false, PARAM_RAW)) {
    local_subscriptions_save_scope_translation();
}

echo $OUTPUT->footer();