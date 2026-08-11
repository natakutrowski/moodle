<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\bundle\pricing\CommerceBundlePricingConfiguration;
use local_subscriptions\commerce\bundle\pricing\CommerceBundlePricingStrategy;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\crm\commerce\presentation\CommerceDesignSystemRenderer;
use local_subscriptions\crm\commerce\presentation\CommerceProductPageHeaderRenderer;
use local_subscriptions\commerce\catalog\rendering\CommerceProductEditorNavigationRenderer;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$sku = required_param('sku', PARAM_RAW_TRIMMED);
$factory = new CommerceCatalogFactory($DB);
$manager = $factory->product_manager();
$product = $manager->get_editor_data($sku)->get_product();
if (!$product->is_bundle()) {
    throw new coding_exception('Only Bundle products have Bundle pricing.');
}
$pricing = $factory->bundle_pricing_service();
$configuration = $pricing->get_configuration($sku);
$currencies = $factory->currency_service()->get_product_currencies($sku, true, true);
if ($currencies === []) {
    $currencies = ['EUR'];
}
$pageurl = new moodle_url('/local/subscriptions/admin/commerce/products/pricing.php', ['sku' => $sku]);
$pagetitle = get_string('commerce_bundle_pricing_title', 'local_subscriptions', $product->get_name());
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $pagetitle, 'local-subscriptions-commerce-bundle-pricing-page');

$action = optional_param('action', '', PARAM_ALPHA);
if ($action === 'deleteprice' && data_submitted() && confirm_sesskey()) {
    $priceid = required_param('priceid', PARAM_INT);
    $currency = strtoupper(required_param('currency', PARAM_ALPHA));
    $manager->delete_price($sku, $priceid);
    redirect(
        $pageurl,
        get_string('commerce_price_currency_deleted', 'local_subscriptions', $currency)
    );
}

if (data_submitted() && confirm_sesskey()) {
    $strategy = required_param('strategy', PARAM_ALPHANUMEXT);
    $discount = optional_param('discountpercent', '0', PARAM_RAW_TRIMMED);
    if (!preg_match('/^(?:100(?:\.0{1,2})?|\d{1,2}(?:\.\d{1,2})?)$/', $discount)) {
        throw new coding_exception('Invalid Bundle discount percentage.');
    }
    $discountbps = (int)round(((float)$discount) * 100);
    if ($strategy !== CommerceBundlePricingStrategy::PERCENTAGE_DISCOUNT) {
        $discountbps = 0;
    }
    $fixedprices = [];
    foreach ($currencies as $currency) {
        $raw = optional_param('price_' . strtolower($currency), '', PARAM_RAW_TRIMMED);
        if ($raw !== '') {
            if (!preg_match('/^\d+(?:[.,]\d{1,2})?$/', $raw)) {
                throw new coding_exception('Invalid fixed price for ' . $currency . '.');
            }
            $fixedprices[$currency] = (int)round(((float)str_replace(',', '.', $raw)) * 100);
        }
    }
    $newcurrency = strtoupper(optional_param('newcurrency', '', PARAM_ALPHANUMEXT));
    $newprice = optional_param('newprice', '', PARAM_RAW_TRIMMED);
    if ($newcurrency !== '' || $newprice !== '') {
        if (!preg_match('/^[A-Z]{3}$/', $newcurrency)) {
            throw new coding_exception('A currency code must contain exactly three letters.');
        }
        if (!preg_match('/^\d+(?:[.,]\d{1,2})?$/', $newprice)) {
            throw new coding_exception('Invalid price for ' . $newcurrency . '.');
        }
        $fixedprices[$newcurrency] = (int) round(((float) str_replace(',', '.', $newprice)) * 100);
    }
    $pricing->configure($sku, new CommerceBundlePricingConfiguration($strategy, $discountbps), $fixedprices);
    redirect($pageurl, get_string('changessaved'));
}

$quotes = [];
foreach ($currencies as $currency) {
    try {
        $quotes[$currency] = $pricing->quote($sku, $currency, true);
    } catch (Throwable $exception) {
        $quotes[$currency] = $exception->getMessage();
    }
}
$existingprices = [];
foreach ($manager->get_editor_data($sku)->get_prices() as $price) {
    if ($price->get_provider() === null && $price->is_active()) {
        $existingprices[$price->get_currency()] = $price;
    }
}
$strategylabels = [
    CommerceBundlePricingStrategy::FIXED => get_string('commerce_bundle_pricing_fixed', 'local_subscriptions'),
    CommerceBundlePricingStrategy::COMPONENT_SUM => get_string('commerce_bundle_pricing_sum', 'local_subscriptions'),
    CommerceBundlePricingStrategy::PERCENTAGE_DISCOUNT => get_string('commerce_bundle_pricing_discount', 'local_subscriptions'),
];
$formatmoney = static fn(int $minor, string $currency): string => format_float($minor / 100, 2) . ' ' . $currency;

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CommerceProductEditorNavigationRenderer::breadcrumb($product->get_name(), get_string('commerce_product_step_pricing', 'local_subscriptions'));
echo CommerceProductEditorNavigationRenderer::render($product, CommerceProductEditorNavigationRenderer::PRICING);
echo CommerceProductPageHeaderRenderer::render(
    $pagetitle,
    CommerceDesignSystemRenderer::page_intro(get_string('commerce_bundle_pricing_intro', 'local_subscriptions')),
    html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/products/preview.php', ['sku' => $sku]), get_string('commerce_bundle_open_preview', 'local_subscriptions'), ['class' => 'btn btn-outline-secondary']),
    get_string('commerce_bundle_pricing_eyebrow', 'local_subscriptions')
);
foreach ($existingprices as $currency => $price) {
    if ($price->get_id() === null) {
        continue;
    }
    $deleteformid = 'commerce-bundle-delete-price-' . strtolower($currency);
    echo html_writer::start_tag('form', [
        'id' => $deleteformid,
        'method' => 'post',
        'action' => $pageurl->out(false),
        'class' => 'd-none',
    ]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sku', 'value' => $sku]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'deleteprice']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'priceid', 'value' => $price->get_id()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'currency', 'value' => $currency]);
    echo html_writer::end_tag('form');
}

echo html_writer::start_tag('form', ['method' => 'post', 'class' => 'card card-body mb-4']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::tag('h3', get_string('commerce_bundle_pricing_method', 'local_subscriptions'), ['class' => 'h5']);
echo html_writer::tag('p', get_string('commerce_bundle_pricing_method_help', 'local_subscriptions'), ['class' => 'text-muted']);
echo html_writer::select($strategylabels, 'strategy', $configuration->get_strategy(), false, ['class' => 'form-select mb-3']);
echo html_writer::tag('label', get_string('commerce_bundle_discount_percent', 'local_subscriptions'), ['for' => 'discountpercent', 'class' => 'form-label']);
echo html_writer::empty_tag('input', ['id' => 'discountpercent', 'name' => 'discountpercent', 'value' => $configuration->get_discount_percent(), 'class' => 'form-control mb-3', 'inputmode' => 'decimal']);
echo html_writer::tag('h3', get_string('commerce_bundle_fixed_prices', 'local_subscriptions'), ['class' => 'h5 mt-2']);
echo html_writer::tag('p', get_string('commerce_bundle_fixed_prices_help', 'local_subscriptions'), ['class' => 'text-muted']);
echo html_writer::start_div('row g-3');
foreach ($currencies as $currency) {
    $existingprice = $existingprices[$currency] ?? null;
    echo html_writer::start_div('col-md-6');
    echo html_writer::tag('label', $currency, ['for' => 'price_' . strtolower($currency), 'class' => 'form-label']);
    echo html_writer::start_div('d-flex align-items-center gap-2');
    echo html_writer::empty_tag('input', [
        'id' => 'price_' . strtolower($currency),
        'name' => 'price_' . strtolower($currency),
        'value' => $existingprice !== null ? format_float($existingprice->get_amount_minor() / 100, 2) : '',
        'class' => 'form-control',
        'inputmode' => 'decimal',
    ]);
    if ($existingprice !== null && $existingprice->get_id() !== null) {
        $deleteformid = 'commerce-bundle-delete-price-' . strtolower($currency);
        echo html_writer::tag('button', get_string('delete'), [
            'type' => 'submit',
            'class' => 'btn btn-outline-danger flex-shrink-0',
            'form' => $deleteformid,
            'data-confirmation' => 'modal',
            'data-confirmation-title-str' => json_encode(['commerce_price_currency_delete_title', 'local_subscriptions']),
            'data-confirmation-question-str' => json_encode([
                'commerce_price_currency_delete_confirm',
                'local_subscriptions',
                $currency,
            ]),
        ]);
    }
    echo html_writer::end_div();
    echo html_writer::end_div();
}
echo html_writer::end_div();
echo html_writer::tag('h3', get_string('commerce_bundle_add_currency', 'local_subscriptions'), ['class' => 'h5 mt-4']);
echo html_writer::tag('p', get_string('commerce_bundle_add_currency_help', 'local_subscriptions'), ['class' => 'text-muted']);
echo html_writer::start_div('row g-3');
echo html_writer::div(
    html_writer::tag('label', get_string('currency'), ['for' => 'newcurrency', 'class' => 'form-label']) .
    html_writer::empty_tag('input', ['id' => 'newcurrency', 'name' => 'newcurrency', 'class' => 'form-control', 'maxlength' => 3, 'placeholder' => 'USD']),
    'col-md-4'
);
echo html_writer::div(
    html_writer::tag('label', get_string('commerce_price', 'local_subscriptions'), ['for' => 'newprice', 'class' => 'form-label']) .
    html_writer::empty_tag('input', ['id' => 'newprice', 'name' => 'newprice', 'class' => 'form-control', 'inputmode' => 'decimal', 'placeholder' => '99.00']),
    'col-md-8'
);
echo html_writer::end_div();
echo CommerceDesignSystemRenderer::form_actions(
    html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-primary', 'value' => get_string('savechanges')])
);
echo html_writer::end_tag('form');

echo html_writer::tag('h3', get_string('commerce_bundle_price_simulation', 'local_subscriptions'), ['class' => 'h4']);
echo html_writer::start_div('crm-commerce-metrics');
foreach ($quotes as $currency => $quote) {
    if (is_string($quote)) {
        echo html_writer::div(html_writer::tag('strong', $currency) . html_writer::tag('div', s($quote), ['class' => 'small text-muted mt-2']), 'crm-commerce-metric');
        continue;
    }
    $comparisonhtml = $quote->has_component_comparison()
        ? html_writer::tag('div', get_string('commerce_bundle_component_total', 'local_subscriptions') . ': ' . $formatmoney($quote->get_component_total()->get_amount_minor(), $currency), ['class' => 'small mt-2']) .
            html_writer::tag('div', get_string('commerce_bundle_savings', 'local_subscriptions') . ': ' . $formatmoney($quote->get_savings_minor(), $currency), ['class' => 'small'])
        : html_writer::tag('div', get_string('commerce_bundle_component_comparison_unavailable', 'local_subscriptions'), ['class' => 'small text-muted mt-2']);
    echo html_writer::div(
        html_writer::tag('div', $formatmoney($quote->get_final_price()->get_amount_minor(), $currency), ['class' => 'crm-commerce-metric-value']) .
        html_writer::tag('div', get_string('commerce_bundle_final_price', 'local_subscriptions'), ['class' => 'crm-commerce-metric-label']) .
        $comparisonhtml,
        'crm-commerce-metric'
    );
}
echo html_writer::end_div();
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
