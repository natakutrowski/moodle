<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\bundle\admin\CommerceBundleComponentInput;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\commerce\catalog\rendering\CommerceProductEditorNavigationRenderer;
use local_subscriptions\commerce\catalog\presentation\CommerceProductPresentation;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$sku = required_param('sku', PARAM_RAW_TRIMMED);
$rowcount = max(1, min(50, optional_param('rows', 8, PARAM_INT)));
$factory = new CommerceCatalogFactory($DB);
$manager = $factory->product_manager();
$editor = $manager->get_editor_data($sku);
$product = $editor->get_product();

if (!$product->is_bundle()) {
    throw new coding_exception('Only Bundle products have editable components.');
}

$pageurl = new moodle_url('/local/subscriptions/admin/commerce/products/components.php', ['sku' => $sku]);
$pagetitle = get_string('commerce_bundle_components_title', 'local_subscriptions', $product->get_name());
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $pagetitle, 'local-subscriptions-commerce-bundle-components-page');

if (data_submitted() && confirm_sesskey()) {
    $skus = optional_param_array('childsku', [], PARAM_RAW_TRIMMED);
    $quantities = optional_param_array('quantity', [], PARAM_INT);
    $sortorders = optional_param_array('sortorder', [], PARAM_INT);
    $rows = [];

    foreach ($skus as $index => $childsku) {
        $rows[] = [
            'sku' => $childsku,
            'quantity' => $quantities[$index] ?? 1,
            'sortorder' => $sortorders[$index] ?? $index,
        ];
    }

    $components = (new CommerceBundleComponentInput())->build($sku, $rows);
    $manager->save_bundle($product, $components);

    redirect($pageurl, get_string('changessaved'));
}

$available = [];
foreach ($manager->list_products(null, 'active') as $summary) {
    $candidate = $summary->get_product();
    if ($candidate->get_sku() !== $sku) {
        $available[$candidate->get_sku()] = $candidate->get_name() . ' — ' . CommerceProductPresentation::type_label($candidate->get_type());
    }
}

$current = $editor->get_components();
$rowcount = max($rowcount, count($current) + 2);

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CommerceProductEditorNavigationRenderer::breadcrumb($product->get_name(), get_string('commerce_product_step_components', 'local_subscriptions'));
echo CommerceProductEditorNavigationRenderer::render($sku, CommerceProductEditorNavigationRenderer::COMPONENTS);
echo $OUTPUT->heading(format_string($pagetitle));
echo html_writer::tag('p', get_string('commerce_bundle_components_help', 'local_subscriptions'), ['class' => 'text-muted']);
echo html_writer::start_tag('form', ['method' => 'post']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::start_div('d-flex flex-column gap-3');

for ($index = 0; $index < $rowcount; $index++) {
    $component = $current[$index] ?? null;
    echo html_writer::start_div('card card-body');
    echo html_writer::tag('strong', get_string('commerce_bundle_component_number', 'local_subscriptions', $index + 1), ['class' => 'mb-2']);
    echo html_writer::start_div('row g-3 align-items-end');
    echo html_writer::start_div('col-md-7');
    echo html_writer::tag('label', get_string('commerce_bundle_component_product', 'local_subscriptions'), ['class' => 'form-label']);
    echo html_writer::select($available, 'childsku[]', $component?->get_child_product_sku() ?? '', ['' => get_string('choosedots')], ['class' => 'form-select']);
    echo html_writer::end_div();
    echo html_writer::start_div('col-md-2');
    echo html_writer::tag('label', get_string('commerce_bundle_component_quantity', 'local_subscriptions'), ['class' => 'form-label']);
    echo html_writer::empty_tag('input', ['type' => 'number', 'name' => 'quantity[]', 'min' => 1, 'value' => $component?->get_quantity() ?? 1, 'class' => 'form-control']);
    echo html_writer::end_div();
    echo html_writer::start_div('col-md-2');
    echo html_writer::tag('label', get_string('commerce_bundle_component_order', 'local_subscriptions'), ['class' => 'form-label']);
    echo html_writer::empty_tag('input', ['type' => 'number', 'name' => 'sortorder[]', 'min' => 0, 'value' => $component?->get_sort_order() ?? $index, 'class' => 'form-control']);
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();
}

echo html_writer::end_div();
echo html_writer::div(
    html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-primary', 'value' => get_string('savechanges')]) .
    ' ' .
    html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/products/components.php', ['sku' => $sku, 'rows' => $rowcount + 5]), get_string('commerce_bundle_add_rows', 'local_subscriptions'), ['class' => 'btn btn-outline-secondary']),
    'mt-4'
);
echo html_writer::end_tag('form');
echo html_writer::div(
    html_writer::link(
        new moodle_url('/local/subscriptions/admin/commerce/products/preview.php', ['sku' => $sku]),
        get_string('commerce_bundle_open_preview', 'local_subscriptions'),
        ['class' => 'btn btn-outline-primary']
    ),
    'mt-3'
);

if ($editor->get_expansion() !== null) {
    echo $OUTPUT->heading(get_string('commerce_bundle_preview_title', 'local_subscriptions'), 3, 'mt-5');
    $table = new html_table();
    $table->head = [get_string('commerce_product_sku', 'local_subscriptions'), get_string('commerce_bundle_component_quantity', 'local_subscriptions')];
    foreach ($editor->get_expansion()->get_items() as $item) {
        $table->data[] = [s($item->get_product()->get_sku()), $item->get_quantity()];
    }
    echo html_writer::table($table);
}

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
