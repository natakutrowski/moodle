<?php

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/local/subscriptions/forms/plan_price_form.php');
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
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);

global $DB, $OUTPUT, $PAGE;

$planid = optional_param('planid', 0, PARAM_INT);
if (!$planid) {
    redirect(new moodle_url(subscription_config::manage_page(), ['tab' => 'plans']));
}

$plan = $DB->get_record('subscription_plan', ['id' => $planid], '*', MUST_EXIST);
$nativeproduct = $DB->get_record_sql(
    "SELECT p.id, p.sku, p.name, p.status
       FROM {local_subs_commerce_prod_map} m
       JOIN {local_subs_commerce_product} p ON p.id = m.productid
      WHERE m.legacytable = :legacytable
        AND m.legacyid = :legacyid",
    ['legacytable' => 'subscription_plan', 'legacyid' => $planid],
    IGNORE_MISSING
);
$legacyreadonly = $nativeproduct !== false;

$pageurl = new moodle_url(
    subscription_config::plans_prices_page(),
    ['planid' => $planid]
);

$pagetitle = get_string(
    'planpricesfor',
    'local_subscriptions',
    '“' . s($plan->name) . '”'
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    'local-subscriptions-commerce-plan-prices-page'
);
$PAGE->requires->js_call_amd('local_subscriptions/deleteprice', 'init');

$add = optional_param('add', 0, PARAM_BOOL);
$edit   = optional_param('edit', 0, PARAM_INT);
$delete = optional_param('del', 0, PARAM_INT);

if ($legacyreadonly && ($add || $edit || $delete || data_submitted())) {
    redirect(
        $pageurl,
        get_string('commerce_plan_legacy_readonly_notice', 'local_subscriptions'),
        null,
        \core\output\notification::NOTIFY_WARNING
    );
}

// On prépare les données personnalisées pour le formulaire :
$formdata = ['planid' => $planid];
$formaction = new moodle_url(subscription_config::plans_prices_page(), $formdata);

// Si on est en mode édition, on ajoute la devise en cours d'édition
if (!$legacyreadonly && $edit) {
    $price = local_subscriptions_get_price($edit);
    $formdata['editingcurrency'] = $price->currency;
}

$mform = new plan_price_form($formaction, $formdata);

if ($mform->is_cancelled()) {
    redirect(new moodle_url(subscription_config::plans_prices_page(), ['planid' => $planid]));
} elseif ($data = $mform->get_data()) {

    if (!isset($data->currency)) {
        $data->currency = $DB->get_field('subscription_plan_price', 'currency', ['id' => $data->id]);
    }
    $price = str_replace(',', '.', (string)$data->price);

    $record = (object)[
        'planid' => $planid,
        'currency' => strtoupper(trim($data->currency)),
        'price' => (float)unformat_float($price, true),
        'stripe_price_id' => trim($data->stripe_price_id ?? '')
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

if ($delete && confirm_sesskey()) {
    local_subscriptions_delete_price($delete);
    redirect(new moodle_url(subscription_config::plans_prices_page(), ['planid' => $planid])
, get_string('pricedeleted', 'local_subscriptions'), null, \core\output\notification::NOTIFY_SUCCESS);
}


echo $OUTPUT->header();

echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::COMMERCE,
    $context
);

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
        'label' => get_string('commerce_plans_title', 'local_subscriptions'),
        'url' => new moodle_url(subscription_config::commerce_plans_page()),
    ],
    [
        'label' => $pagetitle,
        'url' => null,
    ],
]);

echo CrmBackLinkRenderer::render(
    new moodle_url(subscription_config::commerce_plan_view_page(), ['id' => $planid]),
    get_string('commerce_back_to_plan', 'local_subscriptions')
);

echo CrmPageHeader::render(
    $pagetitle,
    get_string('crm_plan_prices_description', 'local_subscriptions'),
    HelpContext::SUBSCRIPTIONS
);

$legacyactions = '';
if ($nativeproduct) {
    $legacyactions = html_writer::link(
        new moodle_url('/local/subscriptions/admin/commerce/products/prices.php', ['sku' => $nativeproduct->sku]),
        html_writer::tag('i', '', ['class' => 'fa fa-money me-1', 'aria-hidden' => 'true'])
            . get_string('commerce_plan_open_native_prices', 'local_subscriptions'),
        ['class' => 'btn btn-primary btn-sm me-2']
    );
    $legacyactions .= html_writer::link(
        new moodle_url('/local/subscriptions/admin/commerce/products/edit.php', ['sku' => $nativeproduct->sku]),
        html_writer::tag('i', '', ['class' => 'fa fa-pencil me-1', 'aria-hidden' => 'true'])
            . get_string('commerce_plan_open_native_translations', 'local_subscriptions'),
        ['class' => 'btn btn-outline-primary btn-sm']
    );
}

echo html_writer::div(
    html_writer::div(
        html_writer::span('LEGACY', 'badge rounded-pill text-bg-warning me-2')
            . html_writer::tag('strong', get_string('commerce_plan_legacy_compatibility_title', 'local_subscriptions'))
            . html_writer::tag('p', get_string(
                $legacyreadonly ? 'commerce_plan_legacy_mapped_readonly_desc' : 'commerce_plan_legacy_unmapped_desc',
                'local_subscriptions'
            ), ['class' => 'mb-0 mt-2 text-muted'])
    )
    . ($legacyactions !== '' ? html_writer::div($legacyactions, 'mt-3') : ''),
    'alert alert-warning commerce-plan-legacy-notice'
);

$prices = local_subscriptions_get_plan_prices($planid);
echo local_subscriptions_plans_renderer::render_prices_table($prices, $legacyreadonly);

// 📋 Formulaire
if (!$legacyreadonly && $edit) {
    $price = local_subscriptions_get_price($edit);
    $mform->set_data($price);
    echo $OUTPUT->heading(get_string('editprice', 'local_subscriptions'), 4);
    $mform->display();
} elseif (!$legacyreadonly && $add) {
    $mform->set_data((object)['planid' => $planid]);
    echo $OUTPUT->heading(get_string('addprice', 'local_subscriptions'), 4);
    $mform->display();
} elseif (!$legacyreadonly) {
    // Bouton Ajouter
    echo html_writer::link(new moodle_url(subscription_config::plans_prices_page(), ['planid' => $planid, 'add' => 1]), '➕ ' . get_string('addprice', 'local_subscriptions'), [
        'class' => 'btn btn-primary',
        'style' => 'margin-bottom: 1em; display: inline-block;'
    ]);
}

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();
