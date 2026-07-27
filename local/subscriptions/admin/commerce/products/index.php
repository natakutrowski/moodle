<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\catalog\presentation\CommerceProductPresentation;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$type = optional_param('type', '', PARAM_ALPHANUMEXT);
$status = optional_param('status', '', PARAM_ALPHANUMEXT);
$pageurl = new moodle_url('/local/subscriptions/admin/commerce/products/index.php');
$pagetitle = get_string('commerce_products_title', 'local_subscriptions');

CrmPageConfigurator::configure($PAGE, $context, $pageurl, $pagetitle, 'local-subscriptions-commerce-products-page');
$factory = new CommerceCatalogFactory($DB);
$manager = $factory->product_manager();
$products = $manager->list_products($type ?: null, $status ?: null);
$typeoptions = ['' => get_string('all')] + array_combine(
    array_map(static fn($item): string => $item->get_code(), $factory->product_type_registry()->all()),
    array_map(static fn($item): string => CommerceProductPresentation::type_label($item->get_code()), $factory->product_type_registry()->all())
);
$statusoptions = ['' => get_string('all')];
foreach (\local_subscriptions\commerce\catalog\domain\CommerceProductStatus::all() as $statuscode) {
    $statusoptions[$statuscode] = CommerceProductPresentation::status_label($statuscode);
}

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => $pagetitle, 'url' => null],
]);
echo CrmPageHeader::render($pagetitle, get_string('commerce_products_description', 'local_subscriptions'), HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::PRODUCTS);

echo html_writer::start_div('card card-body mb-4');
echo html_writer::start_tag('form', ['method' => 'get', 'class' => 'row g-3 align-items-end']);
echo html_writer::start_div('col-md-4');
echo html_writer::tag('label', get_string('commerce_product_type', 'local_subscriptions'), ['for' => 'type', 'class' => 'form-label']);
echo html_writer::select($typeoptions, 'type', $type, false, ['id' => 'type', 'class' => 'form-select']);
echo html_writer::end_div();
echo html_writer::start_div('col-md-4');
echo html_writer::tag('label', get_string('commerce_product_status', 'local_subscriptions'), ['for' => 'status', 'class' => 'form-label']);
echo html_writer::select($statusoptions, 'status', $status, false, ['id' => 'status', 'class' => 'form-select']);
echo html_writer::end_div();
echo html_writer::start_div('col-md-4 d-flex gap-2');
echo html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-outline-primary', 'value' => get_string('filter')]);
echo html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/products/index.php'), get_string('reset'), ['class' => 'btn btn-outline-secondary']);
echo html_writer::end_div();
echo html_writer::end_tag('form');
echo html_writer::end_div();

echo html_writer::div(
    html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/products/edit.php'), get_string('commerce_product_add', 'local_subscriptions'), ['class' => 'btn btn-primary']),
    'mb-3'
);

if ($products === []) {
    echo html_writer::div(get_string('commerce_products_empty', 'local_subscriptions'), 'alert alert-info');
} else {
    $table = new html_table();
    $table->head = [
        get_string('commerce_product_name', 'local_subscriptions'),
        get_string('commerce_product_type', 'local_subscriptions'),
        get_string('commerce_product_status', 'local_subscriptions'),
        get_string('commerce_product_definition', 'local_subscriptions'),
        get_string('actions'),
    ];
    $table->attributes['class'] = 'generaltable table table-hover align-middle';

    foreach ($products as $summary) {
        $product = $summary->get_product();
        $viewurl = new moodle_url('/local/subscriptions/admin/commerce/products/view.php', ['sku' => $product->get_sku()]);
        $definition = get_string('commerce_product_definition_counts', 'local_subscriptions', (object) [
            'prices' => $summary->get_price_count(),
            'translations' => $summary->get_translation_count(),
            'components' => $summary->get_component_count(),
            'entitlements' => $summary->get_entitlement_count(),
        ]);
        $namehtml = html_writer::link($viewurl, format_string($product->get_name()), ['class' => 'fw-semibold']) .
            html_writer::tag('div', s($product->get_sku()), ['class' => 'small text-muted font-monospace']);
        $actions = html_writer::link($viewurl, get_string('view'), ['class' => 'btn btn-sm btn-outline-secondary me-1']) .
            html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/products/edit.php', ['sku' => $product->get_sku()]), get_string('edit'), ['class' => 'btn btn-sm btn-outline-primary me-1']);
        if (!$product->is_archived()) {
            $actions .= html_writer::link(
                new moodle_url('/local/subscriptions/admin/commerce/products/archive.php', ['sku' => $product->get_sku(), 'sesskey' => sesskey()]),
                get_string('commerce_product_archive', 'local_subscriptions'),
                ['class' => 'btn btn-sm btn-outline-danger']
            );
        }
        $table->data[] = [
            $namehtml,
            CommerceProductPresentation::type_badge($product->get_type()),
            CommerceProductPresentation::status_badge($product->get_status()),
            s($definition),
            $actions,
        ];
    }
    echo html_writer::table($table);
}

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
