<?php

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/local/subscriptions/forms/access_scope_translation_form.php');
require_once($CFG->dirroot . '/local/subscriptions/lib/scopes_lib.php');
require_once($CFG->dirroot . '/local/subscriptions/renderer/scopes_renderer.php');

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

$accessscopeid = optional_param('accessscopeid', 0, PARAM_INT);
$editing = optional_param('edit', 0, PARAM_INT);
$adding = optional_param('add', 0, PARAM_INT);
$deleteid = optional_param('del', 0, PARAM_INT);

$scopes = $DB->get_records('subscription_access_scope', null, 'name ASC');
$translations = local_subscriptions_get_scope_translations(accessscopeid: $accessscopeid);

// Suppression
if ($deleteid && confirm_sesskey()) {
    local_subscriptions_delete_scope_translation($deleteid);
    redirect(new moodle_url(subscription_config::scopes_translations_page()));
}

// Traitement du formulaire (après affichage)
if (optional_param('submittranslation', false, PARAM_RAW)) {
    local_subscriptions_save_scope_translation();
}

$pageurl = new moodle_url(
    subscription_config::scopes_translations_page(),
    $accessscopeid > 0 ? ['accessscopeid' => $accessscopeid] : []
);

$pagetitle = get_string('crm_scope_translations_title', 'local_subscriptions');

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    'local-subscriptions-commerce-scope-translations-page'
);
$PAGE->requires->js_call_amd('local_subscriptions/deletescopetranslation', 'init', [[
    'deleteurl' => (new moodle_url(subscription_config::scopes_translations_page()))->out(false),
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
        'url' => new moodle_url(subscription_config::manage_page(), ['tab' => 'scopes']),
    ],
    [
        'label' => $pagetitle,
        'url' => null,
    ],
]);

echo CrmBackLinkRenderer::render(
    new moodle_url(subscription_config::manage_page(), ['tab' => 'scopes']),
    get_string('backtoscopelist', 'local_subscriptions')
);

echo CrmPageHeader::render(
    $pagetitle,
    get_string('crm_scope_translations_description', 'local_subscriptions'),
    HelpContext::SUBSCRIPTIONS
);

// Table
echo local_subscriptions_scopes_renderer::local_subscriptions_render_scopes_translations_table($scopes, $translations, $accessscopeid, $adding, $editing);

if ($accessscopeid) {
    echo html_writer::div(
        html_writer::link(
            new moodle_url(subscription_config::scopes_translations_page()),
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
    $scope = $editing
        ? $DB->get_record('subscription_access_scope', ['id' => $DB->get_field('subscription_access_scope_translation', 'accessscopeid', ['id' => $editing])], '*', MUST_EXIST)
        : $DB->get_record('subscription_access_scope', ['id' => $adding], '*', MUST_EXIST);

    if ($editing) {
        $translation = $DB->get_record('subscription_access_scope_translation', ['id' => $editing], '*', MUST_EXIST);
    }

    echo html_writer::div('', '', ['style' => 'margin-top: 30px;']);
    echo $OUTPUT->heading(get_string($editing ? 'edittranslation' : 'newtranslation', 'local_subscriptions'), 3);
    $form = new access_scope_translation_form(null, [
        'translation' => $translation,
        'scope' => $scope,
        'editing' => $editing
    ]);
    $form->display();
}

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();