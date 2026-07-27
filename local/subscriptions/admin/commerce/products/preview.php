<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\commerce\catalog\rendering\CommerceProductEditorNavigationRenderer;
use local_subscriptions\commerce\catalog\presentation\CommerceProductPresentation;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$sku = required_param('sku', PARAM_RAW_TRIMMED);
$factory = new CommerceCatalogFactory($DB);
$manager = $factory->product_manager();
$editor = $manager->get_editor_data($sku);
$product = $editor->get_product();

if (!$product->is_bundle()) {
    throw new coding_exception('Only Bundle products have a bundle preview.');
}

$pageurl = new moodle_url('/local/subscriptions/admin/commerce/products/preview.php', ['sku' => $sku]);
$pagetitle = get_string('commerce_bundle_preview_title', 'local_subscriptions', $product->get_name());
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $pagetitle, 'local-subscriptions-commerce-bundle-preview-page');

$preview = null;
$previewerror = null;

try {
    $preview = $factory->bundle_preview_service()->build($sku);
} catch (Throwable $exception) {
    $previewerror = $exception->getMessage();
}

$formatmoney = static function($price): string {
    return format_float($price->get_amount_minor() / 100, 2) . ' ' . s($price->get_currency());
};

$formatduration = static function($entitlement): string {
    if ($entitlement->is_lifetime()) {
        return get_string('commerce_entitlement_lifetime', 'local_subscriptions');
    }
    return format_time($entitlement->get_duration_seconds());
};

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CommerceProductEditorNavigationRenderer::breadcrumb($product->get_name(), get_string('commerce_product_step_preview', 'local_subscriptions'));
echo CommerceProductEditorNavigationRenderer::render($sku, CommerceProductEditorNavigationRenderer::PREVIEW);
echo html_writer::div(
    html_writer::div(
        html_writer::tag('div', get_string('commerce_bundle_preview_eyebrow', 'local_subscriptions'), ['class' => 'crm-commerce-eyebrow']) .
        $OUTPUT->heading(format_string($pagetitle), 2) .
        html_writer::tag('p', get_string('commerce_bundle_preview_intro', 'local_subscriptions'), ['class' => 'text-muted mb-0']),
        'flex-grow-1'
    ) .
    html_writer::link(
        new moodle_url('/local/subscriptions/admin/commerce/products/index.php'),
        get_string('commerce_back_to_products', 'local_subscriptions'),
        ['class' => 'btn btn-outline-secondary']
    ),
    'crm-commerce-page-header'
);


if ($previewerror !== null) {
    echo html_writer::div(
        html_writer::tag('h3', get_string('commerce_bundle_preview_unavailable', 'local_subscriptions'), ['class' => 'h5']) .
        html_writer::tag('p', s($previewerror), ['class' => 'mb-3']) .
        html_writer::link(
            new moodle_url('/local/subscriptions/admin/commerce/products/components.php', ['sku' => $sku]),
            get_string('commerce_bundle_fix_components', 'local_subscriptions'),
            ['class' => 'btn btn-primary']
        ),
        'alert alert-warning'
    );
} else {
    $metrics = [
        [get_string('commerce_bundle_preview_products', 'local_subscriptions'), $preview->get_product_count()],
        [get_string('commerce_bundle_preview_quantity', 'local_subscriptions'), $preview->get_total_quantity()],
        [get_string('commerce_bundle_preview_entitlements', 'local_subscriptions'), $preview->get_entitlement_count()],
        [get_string('commerce_bundle_preview_depth', 'local_subscriptions'), $preview->get_maximum_depth()],
    ];
    $metriccards = '';
    foreach ($metrics as [$label, $value]) {
        $metriccards .= html_writer::div(
            html_writer::tag('div', (string)$value, ['class' => 'crm-commerce-metric-value']) .
            html_writer::tag('div', s($label), ['class' => 'crm-commerce-metric-label']),
            'crm-commerce-metric'
        );
    }
    echo html_writer::div($metriccards, 'crm-commerce-metrics');

    if ($preview->get_items() === []) {
        echo html_writer::div(get_string('commerce_bundle_preview_empty', 'local_subscriptions'), 'crm-commerce-empty-state');
    }

    foreach ($preview->get_items() as $item) {
        $leaf = $item->get_product();
        $pricehtml = '';
        foreach ($item->get_prices() as $price) {
            $pricehtml .= html_writer::span($formatmoney($price), 'badge bg-light text-dark border me-1 mb-1');
        }
        if ($pricehtml === '') {
            $pricehtml = html_writer::span(get_string('commerce_no_active_price', 'local_subscriptions'), 'text-warning');
        }

        $entitlementrows = '';
        foreach ($item->get_entitlements() as $entitlement) {
            $entitlementrows .= html_writer::tag('li',
                CommerceProductPresentation::entitlement_html(
                    $entitlement->get_type(),
                    $entitlement->get_resource_key(),
                    $DB
                ) .
                html_writer::div(
                    $formatduration($entitlement) . ' · ×' . $entitlement->get_quantity(),
                    'small text-muted mt-1'
                )
            );
        }
        if ($entitlementrows === '') {
            $entitlementrows = html_writer::tag('li', get_string('commerce_no_entitlement', 'local_subscriptions'), ['class' => 'text-muted']);
        }

        $pathhtml = '';
        foreach ($item->get_paths() as $path) {
            $pathhtml .= html_writer::tag('div', s(implode(' → ', $path)), ['class' => 'small text-muted']);
        }

        echo html_writer::start_div('card crm-commerce-preview-card');
        echo html_writer::start_div('card-body');
        echo html_writer::div(
            html_writer::div(
                html_writer::tag('h3', format_string($leaf->get_name()), ['class' => 'h5 mb-1']) .
                CommerceProductPresentation::type_badge($leaf->get_type()) . ' ' .
                html_writer::tag('code', s($leaf->get_sku()), ['class' => 'small']),
                'flex-grow-1'
            ) .
            html_writer::span('×' . $item->get_quantity(), 'badge bg-primary rounded-pill fs-6'),
            'd-flex align-items-start gap-3 mb-3'
        );
        echo html_writer::start_div('crm-commerce-detail-grid');
        echo html_writer::div(
            html_writer::tag('h4', get_string('commerce_bundle_preview_prices', 'local_subscriptions'), ['class' => 'h6']) . $pricehtml,
            'crm-commerce-info-panel'
        );
        echo html_writer::div(
            html_writer::tag('h4', get_string('commerce_bundle_preview_rights', 'local_subscriptions'), ['class' => 'h6']) .
            html_writer::tag('ul', $entitlementrows, ['class' => 'mb-0 ps-3']),
            'crm-commerce-info-panel'
        );
        echo html_writer::end_div();
        if ($pathhtml !== '') {
            echo html_writer::div(
                html_writer::tag('h4', get_string('commerce_bundle_preview_paths', 'local_subscriptions'), ['class' => 'h6']) . $pathhtml,
                'mt-3 pt-3 border-top'
            );
        }
        echo html_writer::end_div();
        echo html_writer::end_div();
    }
}

$pricing = $factory->bundle_pricing_service();
echo html_writer::tag('h3', get_string('commerce_bundle_preview_pricing', 'local_subscriptions'), ['class' => 'h4 mt-4']);
echo html_writer::start_div('crm-commerce-metrics');
foreach ($factory->currency_service()->get_product_currencies($sku) as $currency) {
    try {
        $quote = $pricing->quote($sku, $currency);
        $money = static fn(int $minor): string => format_float($minor / 100, 2) . ' ' . $currency;
        $comparisonhtml = $quote->has_component_comparison()
            ? html_writer::tag('div', get_string('commerce_bundle_component_total', 'local_subscriptions') . ': ' . $money($quote->get_component_total()->get_amount_minor()), ['class' => 'small mt-2']) .
                html_writer::tag('div', get_string('commerce_bundle_savings', 'local_subscriptions') . ': ' . $money($quote->get_savings_minor()), ['class' => 'small'])
            : html_writer::tag('div', get_string('commerce_bundle_component_comparison_unavailable', 'local_subscriptions'), ['class' => 'small text-muted mt-2']);
        echo html_writer::div(
            html_writer::tag('div', $money($quote->get_final_price()->get_amount_minor()), ['class' => 'crm-commerce-metric-value']) .
            html_writer::tag('div', get_string('commerce_bundle_final_price', 'local_subscriptions'), ['class' => 'crm-commerce-metric-label']) .
            $comparisonhtml,
            'crm-commerce-metric'
        );
    } catch (\Throwable $exception) {
        echo html_writer::div(
            html_writer::tag('strong', $currency) .
            html_writer::tag('div', get_string('commerce_bundle_pricing_incomplete', 'local_subscriptions'), ['class' => 'small text-muted mt-2']),
            'crm-commerce-metric'
        );
    }
}
echo html_writer::end_div();

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
