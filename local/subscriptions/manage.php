<?php

require_once(__DIR__ . '/../../config.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

use local_subscriptions\subscription_config;

subscription_config::guard_public_access();

$PAGE->requires->css(new moodle_url('/local/subscriptions/select2.min.css'));
$PAGE->requires->js(new moodle_url('/local/subscriptions/js/select2.min.js'), true);
$PAGE->requires->css(new moodle_url('/local/subscriptions/styles.css'));
//$PAGE->requires->css(new moodle_url('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css'));
//$PAGE->requires->js(new moodle_url('https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js'), true);


$PAGE->set_url(new moodle_url(subscription_config::manage_page()));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('manage_subscriptions', 'local_subscriptions'));
$PAGE->set_heading(get_string('manage_subscriptions', 'local_subscriptions'));


// Tabs
$currenttab = optional_param('tab', 'scopes', PARAM_ALPHANUMEXT);
$delete = optional_param('delete', 0, PARAM_INT);

if ($currenttab === 'scopes') {

    if ($delete) {
        require_once($CFG->dirroot . '/local/subscriptions/lib/scopes_lib.php');
        local_subscriptions_delete_scope($delete);
    }
    
    require_once($CFG->dirroot . '/local/subscriptions/forms/access_scope_form.php');
    // Instancie le form avec son action sur l’onglet scopes.
    $formaction = new moodle_url(subscription_config::manage_page(), ['tab' => 'scopes']);
    $mform = new access_scope_form($formaction);

    if ($mform->is_cancelled()) {
        // Retour propre à la liste des plans.
        redirect(new moodle_url(subscription_config::manage_page(), ['tab' => 'scopes']));
    } else if ($data = $mform->get_data()) {
        // Sauvegarde du scope.
        global $DB;

        $rec = new stdClass();
        $rec->id             = (int)($data->id ?? 0);
        $rec->name           = $data->name;
        $rec->course_ids     = is_array($data->course_ids) ? implode(',', $data->course_ids) : '';
        $rec->last_update    = time();

        if ($rec->id) {
            $DB->update_record('subscription_access_scope', $rec);
        } else {
            $rec->creation_date = time();
            $rec->id = $DB->insert_record('subscription_access_scope', $rec);

            if ($rec->id) {
                redirect(
                    new moodle_url(subscription_config::scopes_translations_page(), [
                        'accessscopeid' => $rec->id,
                        'add' => $rec->id,
                        'sesskey' => sesskey()
                    ]),
                    get_string('scopecreated', 'local_subscriptions'),
                    null,
                    \core\output\notification::NOTIFY_SUCCESS
                );
            } else {
                redirect(
                    new moodle_url(subscription_config::manage_page(), ['tab' => 'scopes']),
                    get_string('scopecreateerror', 'local_subscriptions'),
                    null,
                    \core\output\notification::NOTIFY_ERROR
                );
            }
        }

        redirect(new moodle_url(subscription_config::manage_page(), ['tab' => 'scopes']),
            get_string('changessaved'), 2, \core\output\notification::NOTIFY_SUCCESS
        );
    }

    // IMPORTANT : on laisse $mform disponible pour l’affichage plus bas.
}


elseif ($currenttab === 'plans') {

    if ($delete) {
        require_once($CFG->dirroot . '/local/subscriptions/lib/plans_lib.php');
        local_subscriptions_delete_plan($delete);
    }

    require_once($CFG->dirroot . '/local/subscriptions/forms/plan_form.php');
    // Instancie le form avec son action sur l’onglet plans.
    $formaction = new moodle_url(subscription_config::manage_page(), ['tab' => 'plans']);
    $mform = new plan_form($formaction);

    if ($mform->is_cancelled()) {
        // Retour propre à la liste des plans.
        redirect(new moodle_url(subscription_config::manage_page(), ['tab' => 'plans']));
    } else if ($data = $mform->get_data()) {
        // Sauvegarde du plan + highlight_type.
        global $DB;

        $rec = new stdClass();
        $rec->id             = (int)($data->id ?? 0);
        $rec->name           = $data->name;
        $rec->accessscopeid  = (int)$data->accessscopeid;
        $rec->duration_key   = $data->duration_key;
        $rec->highlight_type = in_array($data->highlight_type ?? '', ['popular','premium'], true) ? $data->highlight_type : null;
        $rec->last_update    = time();
        $rec->is_active      = 0;
        $rec->is_recurring   = (int)$data->is_recurring;

        if ($rec->id) {
            $DB->update_record('subscription_plan', $rec);
        } else {
            $rec->creation_date = time();
            $rec->id = $DB->insert_record('subscription_plan', $rec);

            if ($rec->id) {
                redirect(
                    new moodle_url(subscription_config::plans_translations_page(), [
                        'planid' => $rec->id,
                        'add' => $rec->id,
                        'sesskey' => sesskey()
                    ]),
                    get_string('plancreated', 'local_subscriptions'),
                    null,
                    \core\output\notification::NOTIFY_SUCCESS
                );
            } else {
                redirect(
                    new moodle_url(subscription_config::manage_page(), ['tab' => 'plans']),
                    get_string('plancreateerror', 'local_subscriptions'),
                    null,
                    \core\output\notification::NOTIFY_ERROR
                );
            }
        }

        // Option : un seul "popular"
        if ($rec->highlight_type === 'popular') {
            $DB->execute("UPDATE {subscription_plan} SET highlight_type = NULL WHERE id <> :id AND highlight_type = 'popular'", ['id' => $rec->id]);
        }

        redirect(new moodle_url(subscription_config::manage_page(), ['tab' => 'plans']),
            get_string('changessaved'), 2, \core\output\notification::NOTIFY_SUCCESS
        );
    }

    // IMPORTANT : on laisse $mform disponible pour l’affichage plus bas.
}



echo $OUTPUT->header();

$tabs = [
    new tabobject('scopes', new moodle_url(subscription_config::manage_page(), ['tab' => 'scopes']), get_string('scopes', 'local_subscriptions')),
    new tabobject('plans', new moodle_url(subscription_config::manage_page(), ['tab' => 'plans']), get_string('plans', 'local_subscriptions')),
];

print_tabs([$tabs], $currenttab);

// Include selected tab
switch ($currenttab) {
    case 'plans':
        include_once(__DIR__ . '/tabs/plans.php');
        break;
    case 'scopes':
    default:
        include_once(__DIR__ . '/tabs/scopes.php');
        break;
}

echo $OUTPUT->footer();
