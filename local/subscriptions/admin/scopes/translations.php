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
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);

global $DB;

$accessscopeid = optional_param('accessscopeid', 0, PARAM_INT);
$editing = optional_param('edit', 0, PARAM_INT);
$adding = optional_param('add', 0, PARAM_INT);
$deleteid = optional_param('del', 0, PARAM_INT);

$scopes = $DB->get_records('subscription_access_scope', null, 'name ASC');
$translations = local_subscriptions_get_scope_translations(accessscopeid: $accessscopeid);

// A Scope becomes compatibility-only once all Plans using it have been mapped to
// Native Commerce Products. During a mixed migration state, Legacy editing remains
// available because an unmapped Plan may still consume these translations.
$nativeproductsbyscope = [];
$mappedplanidsbyscope = [];
$mappedrecords = $DB->get_records_sql(
    "SELECT m.id AS mapid,
            sp.id AS planid,
            sp.accessscopeid,
            np.id AS productid,
            np.sku,
            np.name,
            np.status
       FROM {local_subs_commerce_prod_map} m
       JOIN {subscription_plan} sp
         ON sp.id = m.legacyid
       JOIN {local_subs_commerce_product} np
         ON np.id = m.productid
      WHERE m.legacytable = :legacytable
        AND sp.accessscopeid > 0
   ORDER BY sp.accessscopeid ASC, np.name ASC",
    ['legacytable' => 'subscription_plan']
);
foreach ($mappedrecords as $mappedrecord) {
    $scopeid = (int)$mappedrecord->accessscopeid;
    $nativeproductsbyscope[$scopeid] ??= [];
    $nativeproductsbyscope[$scopeid][(int)$mappedrecord->productid] = $mappedrecord;
    $mappedplanidsbyscope[$scopeid] ??= [];
    $mappedplanidsbyscope[$scopeid][(int)$mappedrecord->planid] = true;
}
$planstats = $DB->get_records_sql(
    "SELECT accessscopeid, COUNT(1) AS total
       FROM {subscription_plan}
      WHERE accessscopeid > 0
   GROUP BY accessscopeid"
);
$readonlyscopes = [];
foreach ($planstats as $scopeid => $stat) {
    $totalplans = (int)$stat->total;
    $mappedplans = count($mappedplanidsbyscope[(int)$scopeid] ?? []);
    if ($totalplans > 0 && $mappedplans === $totalplans) {
        $readonlyscopes[(int)$scopeid] = true;
    }
}
$nativeproducts = $accessscopeid > 0 ? ($nativeproductsbyscope[$accessscopeid] ?? []) : [];
$legacyreadonly = $accessscopeid > 0 && !empty($readonlyscopes[$accessscopeid]);

// Suppression.
if ($deleteid && confirm_sesskey()) {
    $deletescopeid = (int)$DB->get_field(
        'subscription_access_scope_translation',
        'accessscopeid',
        ['id' => $deleteid],
        MUST_EXIST
    );
    if (!empty($readonlyscopes[$deletescopeid])) {
        redirect(
            new moodle_url(subscription_config::scopes_translations_page(), ['accessscopeid' => $deletescopeid]),
            get_string('commerce_scope_legacy_readonly_notice', 'local_subscriptions'),
            null,
            \core\output\notification::NOTIFY_WARNING
        );
    }
    local_subscriptions_delete_scope_translation($deleteid);
    redirect(new moodle_url(subscription_config::scopes_translations_page()));
}

// Traitement du formulaire.
if (optional_param('submittranslation', false, PARAM_RAW)) {
    $submittedscopeid = required_param('accessscopeid', PARAM_INT);
    if (!empty($readonlyscopes[$submittedscopeid])) {
        redirect(
            new moodle_url(subscription_config::scopes_translations_page(), ['accessscopeid' => $submittedscopeid]),
            get_string('commerce_scope_legacy_readonly_notice', 'local_subscriptions'),
            null,
            \core\output\notification::NOTIFY_WARNING
        );
    }
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
echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::PRODUCTS,
    $context
);

echo CrmBreadcrumbRenderer::render([
    [
        'label' => get_string('crm_commerce_title', 'local_subscriptions'),
        'url' => new moodle_url(subscription_config::admin_commerce_page()),
    ],
    [
        'label' => get_string('commerce_products_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/products/index.php'),
    ],
    [
        'label' => get_string('commerce_scopes_title', 'local_subscriptions'),
        'url' => new moodle_url(subscription_config::commerce_access_scopes_page()),
    ],
    [
        'label' => $pagetitle,
        'url' => null,
    ],
]);

echo CrmBackLinkRenderer::render(
    $accessscopeid > 0
        ? new moodle_url(subscription_config::commerce_access_scope_view_page(), ['id' => $accessscopeid])
        : new moodle_url(subscription_config::commerce_access_scopes_page()),
    $accessscopeid > 0
        ? get_string('commerce_back_to_scope', 'local_subscriptions')
        : get_string('backtoscopelist', 'local_subscriptions')
);

echo CrmPageHeader::render(
    $pagetitle,
    get_string('crm_scope_translations_description', 'local_subscriptions'),
    HelpContext::SUBSCRIPTIONS
);

$legacyactions = '';
if ($nativeproducts !== []) {
    $links = [];
    foreach ($nativeproducts as $nativeproduct) {
        $links[] = html_writer::link(
            new moodle_url('/local/subscriptions/admin/commerce/products/edit.php', ['sku' => $nativeproduct->sku]),
            html_writer::tag('i', '', ['class' => 'fa fa-language me-1', 'aria-hidden' => 'true'])
                . format_string($nativeproduct->name),
            ['class' => 'btn btn-primary btn-sm']
        );
    }
    $legacyactions = html_writer::div(
        html_writer::span(
            get_string('commerce_scope_open_native_products', 'local_subscriptions'),
            'small fw-semibold me-2'
        ) . implode(' ', $links),
        'd-flex gap-2 flex-wrap align-items-center'
    );
}

echo html_writer::div(
    html_writer::span('LEGACY', 'badge rounded-pill text-bg-warning me-2')
        . html_writer::tag('strong', get_string('commerce_scope_legacy_compatibility_title', 'local_subscriptions'))
        . html_writer::tag('p', get_string(
            $legacyreadonly ? 'commerce_scope_legacy_mapped_readonly_desc' : 'commerce_scope_legacy_unmapped_desc',
            'local_subscriptions'
        ), ['class' => 'mb-0 mt-2 text-muted'])
        . ($legacyactions !== '' ? html_writer::div($legacyactions, 'mt-3') : ''),
    'alert alert-warning commerce-scope-legacy-notice'
);

// Table.
echo local_subscriptions_scopes_renderer::local_subscriptions_render_scopes_translations_table(
    $scopes,
    $translations,
    $accessscopeid,
    $adding,
    $editing,
    $nativeproductsbyscope,
    $readonlyscopes
);

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

// Formulaire Legacy : interdit dès que le Scope alimente un Produit Native mappé.
if ($editing || $adding) {
    $formscopeid = $editing
        ? (int)$DB->get_field(
            'subscription_access_scope_translation',
            'accessscopeid',
            ['id' => $editing],
            MUST_EXIST
        )
        : $adding;

    if (!empty($readonlyscopes[$formscopeid])) {
        echo $OUTPUT->notification(
            get_string('commerce_scope_legacy_readonly_notice', 'local_subscriptions'),
            \core\output\notification::NOTIFY_WARNING
        );
        echo CrmWorkspaceRenderer::end();
        echo $OUTPUT->footer();
        exit;
    }

    require_sesskey();

    $translation = null;
    $scope = $DB->get_record('subscription_access_scope', ['id' => $formscopeid], '*', MUST_EXIST);
    if ($editing) {
        $translation = $DB->get_record('subscription_access_scope_translation', ['id' => $editing], '*', MUST_EXIST);
    }

    echo html_writer::div('', '', ['style' => 'margin-top: 30px;']);
    echo $OUTPUT->heading(get_string($editing ? 'edittranslation' : 'newtranslation', 'local_subscriptions'), 3);
    $form = new access_scope_translation_form(null, [
        'translation' => $translation,
        'scope' => $scope,
        'editing' => $editing,
    ]);
    $form->display();
}

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
