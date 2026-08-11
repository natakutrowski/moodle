<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\catalog\navigation\CommerceCatalogLinkGenerator;
use local_subscriptions\commerce\catalog\presentation\CommerceCatalogPresentation;
use local_subscriptions\commerce\catalog\readmodel\CommerceCatalogListFilter;
use local_subscriptions\commerce\catalog\readmodel\CommerceCatalogReadRepository;
use local_subscriptions\commerce\catalog\validation\CommerceCatalogValidator;
use local_subscriptions\commerce\catalog\validation\CommerceCatalogActivationValidator;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\crm\commerce\presentation\CommerceDesignSystemRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$query = optional_param('q', '', PARAM_RAW_TRIMMED);
$type = optional_param('type', '', PARAM_ALPHANUMEXT);
$editorial = '';
$visibility = '';
$availability = '';
$technical = '';
$currency = strtoupper(optional_param('currency', '', PARAM_ALPHA));
$origin = optional_param('origin', '', PARAM_ALPHANUMEXT);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 25, PARAM_INT);

$filter = new CommerceCatalogListFilter($query, $type, $editorial, $visibility, $availability, $technical, $currency, $origin);
$repository = new CommerceCatalogReadRepository($DB);
$productmanager = (new CommerceCatalogFactory($DB))->product_manager();
$activationvalidator = new CommerceCatalogActivationValidator($DB);
$result = $repository->search($filter, $page, $perpage);
$allproducts = $repository->find_all();
$pageurl = new moodle_url('/local/subscriptions/admin/commerce/products/index.php');
$pagetitle = get_string('commerce_catalog_title', 'local_subscriptions');
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $pagetitle, 'local-subscriptions-commerce-products-page');

$collect = static function(array $products, callable $getter): array {
    $values = [];
    foreach ($products as $product) { $values[$getter($product)] = true; }
    ksort($values);
    return array_keys($values);
};
$options = static fn(string $dimension, array $values): array => ['' => get_string('all')] + array_combine(
    $values,
    array_map(static fn(string $value): string => CommerceCatalogPresentation::label($dimension, $value), $values)
);

$typeoptions = $options('type', $collect($allproducts, static fn($p): string => $p->get_type()));
$currencies = [];
foreach ($allproducts as $product) {
    foreach ($product->get_prices() as $price) { $currencies[$price->get_currency()] = $price->get_currency(); }
}
ksort($currencies);
$currencyoptions = ['' => get_string('all')] + $currencies;
$originoptions = [
    '' => get_string('all'),
    'native' => get_string('commerce_catalog_origin_native', 'local_subscriptions'),
    'legacy_plan' => get_string('commerce_catalog_origin_legacy_only', 'local_subscriptions'),
    'legacy_digital' => get_string('commerce_catalog_origin_legacy_only', 'local_subscriptions'),
];

$select = static function(string $name, string $label, array $choices, string $selected, string $class = 'col-md-3'): string {
    return html_writer::div(
        html_writer::tag('label', $label, ['for' => $name, 'class' => 'form-label']) .
        html_writer::select($choices, $name, $selected, false, ['id' => $name, 'class' => 'form-select']),
        $class
    );
};

$filterhtml = html_writer::start_tag('form', ['method' => 'get', 'class' => 'row g-3 align-items-end']);
$filterhtml .= html_writer::div(
    html_writer::tag('label', get_string('search'), ['for' => 'q', 'class' => 'form-label']) .
    html_writer::empty_tag('input', ['type' => 'search', 'name' => 'q', 'id' => 'q', 'value' => $query, 'class' => 'form-control']),
    'col-md-6'
);
$filterhtml .= $select('type', get_string('commerce_product_type', 'local_subscriptions'), $typeoptions, $type);
$filterhtml .= $select('currency', get_string('currency'), $currencyoptions, $currency, 'col-md-2');
$filterhtml .= $select('origin', get_string('commerce_catalog_origin', 'local_subscriptions'), $originoptions, $origin, 'col-md-2');
$filterhtml .= html_writer::div(
    html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-outline-primary me-2', 'value' => get_string('filter')]) .
    html_writer::link($pageurl, get_string('reset'), ['class' => 'btn btn-outline-secondary']),
    'col-md-2 d-flex'
);
$filterhtml .= html_writer::end_tag('form');

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => $pagetitle, 'url' => null],
]);
echo CrmPageHeader::render($pagetitle, get_string('commerce_catalog_description', 'local_subscriptions'), HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::PRODUCTS);
echo CommerceDesignSystemRenderer::filter_panel($filterhtml);
echo CommerceDesignSystemRenderer::action_bar([[
    'label' => get_string('commerce_product_add', 'local_subscriptions'),
    'url' => new moodle_url('/local/subscriptions/admin/commerce/products/edit.php'),
    'class' => 'btn btn-primary',
]], 'mb-3');

if ($result->items === []) {
    echo CommerceDesignSystemRenderer::empty_state(
        get_string('commerce_products_empty_title', 'local_subscriptions'),
        get_string('commerce_products_empty', 'local_subscriptions')
    );
} else {
    $table = new html_table();
    $table->head = [
        get_string('commerce_product_name', 'local_subscriptions'),
        get_string('commerce_product_type', 'local_subscriptions'),
        get_string('commerce_product_status', 'local_subscriptions'),
        get_string('commerce_prices', 'local_subscriptions'),
        get_string('actions'),
    ];
    $table->attributes['class'] = 'generaltable table table-hover align-middle';
    $table->attributes['aria-label'] = get_string('commerce_catalog_table_label', 'local_subscriptions');
    foreach ($result->items as $product) {
        $viewurl = CommerceCatalogLinkGenerator::view_url($product);
        $originlabel = $product->get_origin() === 'native'
            ? get_string('commerce_catalog_origin_native_short', 'local_subscriptions')
            : get_string('commerce_catalog_origin_legacy_short', 'local_subscriptions');
        $name = html_writer::link($viewurl, format_string($product->get_name()), ['class' => 'fw-semibold']) .
            html_writer::div(
                s($product->get_sku()) . ' (' . s($originlabel) . ')',
                'small text-muted font-monospace'
            );
        $validation = $product->get_origin() === 'native'
            ? $activationvalidator->validate($productmanager->get_editor_data($product->get_sku())->get_product())
            : (new CommerceCatalogValidator())->validate($product);
        $lifecyclestatus = $product->get_editorial_status() === 'archived'
            ? 'archived'
            : ($product->get_availability() === 'on_sale' ? 'active' : 'inactive');
        $statuses = CommerceCatalogPresentation::badge('lifecycle', $lifecyclestatus) . ' ' .
            CommerceCatalogPresentation::badge('technical', $validation->is_valid() ? 'valid' : 'incomplete');
        if ($validation->has_issues()) {
            $statuses .= html_writer::div(implode(' · ', array_map(static fn($issue): string => s($issue->message), $validation->issues)), 'small text-muted mt-1');
        }
        $actions = html_writer::link(
            $viewurl,
            $product->get_origin() === 'native'
                ? get_string('view')
                : get_string(
                    $product->get_origin() === 'legacy_plan'
                        ? 'commerce_catalog_open_legacy_plan'
                        : 'commerce_catalog_open_legacy_digital',
                    'local_subscriptions'
                ),
            ['class' => 'btn btn-sm btn-outline-primary me-1']
        );
        if ($product->get_origin() === 'native') {
            $actions .= html_writer::link(
                new moodle_url('/local/subscriptions/admin/commerce/products/edit.php', ['sku' => $product->get_sku()]),
                get_string('edit'),
                ['class' => 'btn btn-sm btn-outline-secondary']
            );
            if ($lifecyclestatus === 'active') {
                $actions .= html_writer::link(
                    new moodle_url('/local/subscriptions/admin/commerce/products/status.php', [
                        'sku' => $product->get_sku(), 'action' => 'deactivate', 'sesskey' => sesskey(),
                    ]),
                    get_string('commerce_product_deactivate', 'local_subscriptions'),
                    ['class' => 'btn btn-sm btn-outline-warning ms-1']
                );
            } else if ($lifecyclestatus === 'inactive') {
                $actions .= html_writer::link(
                    new moodle_url('/local/subscriptions/admin/commerce/products/status.php', [
                        'sku' => $product->get_sku(), 'action' => 'activate', 'sesskey' => sesskey(),
                    ]),
                    get_string('commerce_product_activate', 'local_subscriptions'),
                    ['class' => 'btn btn-sm btn-success ms-1']
                );
            }
        } else {
            $legacyaction = $lifecyclestatus === 'active' ? 'deactivate' : 'activate';
            $actions .= html_writer::link(
                new moodle_url('/local/subscriptions/admin/commerce/products/legacy_status.php', [
                    'origin' => $product->get_origin(),
                    'id' => (int)$product->get_id(),
                    'action' => $legacyaction,
                    'return' => 'index',
                    'sesskey' => sesskey(),
                ]),
                get_string(
                    $legacyaction === 'deactivate' ? 'commerce_product_deactivate' : 'commerce_product_activate',
                    'local_subscriptions'
                ),
                [
                    'class' => $legacyaction === 'deactivate'
                        ? 'btn btn-sm btn-outline-warning ms-1'
                        : 'btn btn-sm btn-success ms-1',
                ]
            );
        }
        $table->data[] = [
            $name,
            CommerceCatalogPresentation::badge('type', $product->get_type()),
            $statuses,
            CommerceCatalogPresentation::prices($product->get_prices()),
            $actions,
        ];
    }
    echo html_writer::table($table);
    $params = compact('type', 'editorial', 'visibility', 'availability', 'technical', 'currency', 'origin', 'perpage');
    $params['q'] = $query;
    echo $OUTPUT->paging_bar($result->total, $result->page, $result->perpage, new moodle_url($pageurl, $params));
}

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
