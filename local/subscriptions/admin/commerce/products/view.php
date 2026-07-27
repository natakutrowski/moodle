<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\catalog\presentation\CommerceProductPresentation;
use local_subscriptions\commerce\catalog\presentation\CommerceLanguagePresentation;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$sku = required_param('sku', PARAM_RAW_TRIMMED);
$factory = new CommerceCatalogFactory($DB);
$editor = $factory->product_manager()->get_editor_data($sku);
$product = $editor->get_product();
$pageurl = new moodle_url('/local/subscriptions/admin/commerce/products/view.php', ['sku' => $sku]);
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $product->get_name(), 'local-subscriptions-commerce-product-view-page');

$formatmoney = static fn(int $minor, string $currency): string => format_float($minor / 100, 2) . ' ' . $currency;
$installedlanguages = $factory->locale_service()->get_languages();

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('commerce_products_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/products/index.php')],
    ['label' => $product->get_name(), 'url' => null],
]);

echo html_writer::start_div('crm-commerce-page-header');
echo html_writer::div(
    $OUTPUT->heading(format_string($product->get_name()), 2) .
    html_writer::div(CommerceProductPresentation::type_badge($product->get_type()) . ' ' . CommerceProductPresentation::status_badge($product->get_status()), 'mt-2') .
    html_writer::tag('code', s($product->get_sku()), ['class' => 'd-inline-block mt-2']),
    'flex-grow-1'
);
echo html_writer::div(
    html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/products/edit.php', ['sku' => $sku]), get_string('edit'), ['class' => 'btn btn-primary']),
    'd-flex gap-2'
);
echo html_writer::end_div();

echo html_writer::start_div('row g-4');
echo html_writer::start_div('col-xl-8');

echo html_writer::start_div('card card-body mb-4');
echo html_writer::tag('h3', get_string('commerce_product_summary', 'local_subscriptions'), ['class' => 'h5']);
echo html_writer::tag('p', format_text($product->get_description(), FORMAT_PLAIN), ['class' => 'mb-0 text-muted']);
echo html_writer::end_div();

if ($editor->get_translations() !== []) {
    echo html_writer::start_div('card card-body mb-4');
    echo html_writer::tag('h3', get_string('commerce_product_translations_title', 'local_subscriptions'), ['class' => 'h5']);
    foreach ($editor->get_translations() as $translation) {
        echo html_writer::start_div('border rounded p-3 mb-3');
        echo html_writer::tag('h4',
            CommerceLanguagePresentation::label(
                $translation->get_language(),
                $installedlanguages[$translation->get_language()] ?? null
            ) . ' — ' . format_string($translation->get_name()),
            ['class' => 'h6']
        );
        if ($translation->get_short_description() !== '') {
            echo html_writer::tag('p', s($translation->get_short_description()), ['class' => 'fw-semibold']);
        }
        echo html_writer::tag('div', format_text($translation->get_description(), FORMAT_PLAIN), ['class' => 'text-muted']);
        echo html_writer::end_div();
    }
    echo html_writer::end_div();
}

if ($editor->get_entitlements() !== []) {
    echo html_writer::start_div('card card-body mb-4');
    echo html_writer::tag('h3', get_string('commerce_entitlements', 'local_subscriptions'), ['class' => 'h5']);
    $rows = '';
    foreach ($editor->get_entitlements() as $entitlement) {
        $rows .= html_writer::tag('li', CommerceProductPresentation::entitlement_html(
            $entitlement->get_type(),
            $entitlement->get_resource_key(),
            $DB
        ));
    }
    echo html_writer::tag('ul', $rows, ['class' => 'mb-0']);
    echo html_writer::end_div();
}

if ($product->is_bundle()) {
    try {
        $preview = $factory->bundle_preview_service()->build($sku);
        echo html_writer::start_div('card card-body mb-4');
        echo html_writer::tag('h3', get_string('commerce_product_step_components', 'local_subscriptions'), ['class' => 'h5']);
        foreach ($preview->get_items() as $item) {
            echo html_writer::div(
                html_writer::tag('strong', format_string($item->get_product()->get_name())) .
                ' × ' . $item->get_quantity() . ' ' . CommerceProductPresentation::type_badge($item->get_product()->get_type()),
                'border rounded p-3 mb-2'
            );
        }
        echo html_writer::end_div();
    } catch (Throwable $exception) {
        echo html_writer::div(get_string('commerce_bundle_pricing_incomplete', 'local_subscriptions'), 'alert alert-warning');
    }
}

echo html_writer::end_div();
echo html_writer::start_div('col-xl-4');

echo html_writer::start_div('card card-body mb-4');
echo html_writer::tag('h3', get_string('commerce_prices', 'local_subscriptions'), ['class' => 'h5']);
if ($editor->get_prices() === []) {
    echo html_writer::tag('p', get_string('none'), ['class' => 'text-muted mb-0']);
} else {
    foreach ($editor->get_prices() as $price) {
        echo html_writer::div(
            html_writer::tag('strong', $formatmoney($price->get_amount_minor(), $price->get_currency())) .
            html_writer::tag('div', $price->is_active() ? get_string('commerce_product_status_active', 'local_subscriptions') : get_string('commerce_product_status_inactive', 'local_subscriptions'), ['class' => 'small text-muted']),
            'border-bottom py-2'
        );
    }
}
echo html_writer::end_div();

if ($product->is_bundle()) {
    echo html_writer::start_div('card card-body mb-4');
    echo html_writer::tag('h3', get_string('commerce_bundle_preview_pricing', 'local_subscriptions'), ['class' => 'h5']);
    $currencies = $factory->currency_service()->get_product_currencies($sku);
    foreach ($currencies as $currency) {
        try {
            $quote = $factory->bundle_pricing_service()->quote($sku, $currency);
            $comparisonhtml = $quote->has_component_comparison()
                ? html_writer::tag('div', get_string('commerce_bundle_component_total', 'local_subscriptions') . ': ' . $formatmoney($quote->get_component_total()->get_amount_minor(), $currency), ['class' => 'small text-muted']) .
                    html_writer::tag('div', get_string('commerce_bundle_savings', 'local_subscriptions') . ': ' . $formatmoney($quote->get_savings_minor(), $currency), ['class' => 'small text-muted'])
                : html_writer::tag('div', get_string('commerce_bundle_component_comparison_unavailable', 'local_subscriptions'), ['class' => 'small text-muted']);
            echo html_writer::div(
                html_writer::tag('strong', $formatmoney($quote->get_final_price()->get_amount_minor(), $currency)) .
                $comparisonhtml,
                'border-bottom py-2'
            );
        } catch (Throwable) {
            echo html_writer::div($currency . ' — ' . get_string('commerce_bundle_pricing_incomplete', 'local_subscriptions'), 'small text-muted py-2');
        }
    }
    echo html_writer::end_div();
}

echo html_writer::start_div('card card-body');
echo html_writer::tag('h3', get_string('commerce_product_definition', 'local_subscriptions'), ['class' => 'h5']);
echo html_writer::tag('ul',
    html_writer::tag('li', count($editor->get_prices()) . ' ' . get_string('commerce_prices', 'local_subscriptions')) .
    html_writer::tag('li', count($editor->get_translations()) . ' ' . get_string('commerce_translations', 'local_subscriptions')) .
    html_writer::tag('li', count($editor->get_components()) . ' ' . get_string('commerce_components', 'local_subscriptions')) .
    html_writer::tag('li', count($editor->get_entitlements()) . ' ' . get_string('commerce_entitlements', 'local_subscriptions')),
    ['class' => 'mb-0']
);
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_div();

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
