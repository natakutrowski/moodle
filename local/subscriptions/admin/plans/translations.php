<?php

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/local/subscriptions/forms/plan_translation_form.php');
require_once($CFG->dirroot . '/local/subscriptions/lib/plans_lib.php');
require_once($CFG->dirroot . '/local/subscriptions/renderer/plans_renderer.php');

use local_subscriptions\subscription_config;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBackLinkRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);

global $DB;

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

$pageurl = new moodle_url(
    subscription_config::plans_translations_page(),
    $planid > 0 ? ['planid' => $planid] : []
);

$pagetitle = get_string('crm_plan_translations_title', 'local_subscriptions');

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    'local-subscriptions-commerce-plan-translations-page'
);
$PAGE->requires->js_call_amd('local_subscriptions/deleteplantranslation', 'init', [[
    'deleteurl' => (new moodle_url(subscription_config::plans_translations_page()))->out(false),
]]);

echo $OUTPUT->header();

echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);

echo CrmBreadcrumbRenderer::render([
    [
        'label' => get_string('crm_commerce_title', 'local_subscriptions'),
        'url' => new moodle_url(subscription_config::admin_commerce_page()),
    ],
    [
        'label' => get_string('crm_subscription_configuration_title', 'local_subscriptions'),
        'url' => new moodle_url(subscription_config::manage_page(), ['tab' => 'plans']),
    ],
    [
        'label' => $pagetitle,
        'url' => null,
    ],
]);

echo CrmBackLinkRenderer::render(
    new moodle_url(subscription_config::manage_page(), ['tab' => 'plans']),
    get_string('backtoplanlist', 'local_subscriptions')
);

echo CrmPageHeader::render(
    $pagetitle,
    get_string('crm_plan_translations_description', 'local_subscriptions'),
    HelpContext::SUBSCRIPTIONS
);

// Table
echo local_subscriptions_plans_renderer::local_subscriptions_render_plans_translations_table($plans, $translations, $planid, $adding, $editing);

if ($planid) {
    echo html_writer::div(
        html_writer::link(
            new moodle_url(subscription_config::plans_translations_page()),
            get_string('showalltranslations', 'local_subscriptions'),
            ['class' => 'btn btn-outline-secondary']
        ),
        'mt-4'
    );
}

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
    echo $OUTPUT->heading(get_string($editing ? 'edittranslation' : 'newtranslation', 'local_subscriptions'), 3);
    $form = new plan_translation_form(null, [
        'translation' => $translation,
        'plan' => $plan,
        'editing' => $editing
    ]);
    $form->display();
}

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();