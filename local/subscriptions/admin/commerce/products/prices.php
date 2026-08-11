<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\catalog\currency\CommerceCurrencyRegistry;
use local_subscriptions\commerce\catalog\domain\CommerceProductPrice;
use local_subscriptions\commerce\catalog\rendering\CommerceProductEditorNavigationRenderer;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\commerce\domain\value\CommerceMoney;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$sku = required_param('sku', PARAM_RAW_TRIMMED);
$factory = new CommerceCatalogFactory($DB);
$manager = $factory->product_manager();
$editor = $manager->get_editor_data($sku);
$product = $editor->get_product();
$registry = new CommerceCurrencyRegistry();
$pageurl = new moodle_url('/local/subscriptions/admin/commerce/products/prices.php', ['sku' => $sku]);
CrmPageConfigurator::configure($PAGE, $context, $pageurl, get_string('commerce_product_prices_title', 'local_subscriptions'), 'local-subscriptions-commerce-product-prices-page');

$action = optional_param('action', '', PARAM_ALPHA);
if ($action !== '' && confirm_sesskey()) {
    if ($action === 'delete') {
        $manager->delete_price($sku, required_param('priceid', PARAM_INT));
        redirect($pageurl, get_string('commerce_price_deleted', 'local_subscriptions'));
    }

    $priceid = optional_param('priceid', 0, PARAM_INT);
    $existingprice = null;
    if ($priceid > 0) {
        foreach ($editor->get_prices() as $candidate) {
            if ($candidate->get_id() === $priceid) {
                $existingprice = $candidate;
                break;
            }
        }
        if ($existingprice === null) {
            throw new moodle_exception('invalidrecord', 'error');
        }
    }
    $currency = $registry->require_enabled(strtoupper(required_param('currency', PARAM_ALPHA)));
    $amount = str_replace(',', '.', required_param('amount', PARAM_RAW_TRIMMED));
    if (!is_numeric($amount) || (float)$amount < 0) {
        throw new moodle_exception('commerce_invalid_price', 'local_subscriptions');
    }
    if ($manager->price_currency_exists($sku, $currency, $priceid ?: null)) {
        throw new moodle_exception('commerce_price_currency_duplicate', 'local_subscriptions');
    }
    $price = new CommerceProductPrice(
        $sku,
        CommerceMoney::from_minor((int)round(((float)$amount) * 100), $currency),
        optional_param('active', 0, PARAM_BOOL) === 1,
        $existingprice?->get_provider(),
        $existingprice?->get_provider_price_id(),
        [],
        $priceid ?: null
    );
    if ($priceid > 0) {
        $manager->update_price($price);
    } else {
        $manager->save_price($price);
    }
    redirect($pageurl, get_string('changessaved'));
}

$prices = $editor->get_prices();
$currencyoptions = $registry->options();
$renderform = static function(?CommerceProductPrice $price = null) use ($sku, $currencyoptions): string {
    $id = $price?->get_id() ?? 0;
    $amount = $price ? number_format($price->get_amount_minor() / 100, 2, '.', '') : '';
    $html = html_writer::start_tag('form', ['method' => 'post', 'class' => 'row g-2 align-items-end']);
    $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sku', 'value' => $sku]);
    $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'priceid', 'value' => $id]);
    $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => $id ? 'update' : 'create']);
    $html .= html_writer::div(html_writer::tag('label', get_string('currency'), ['class' => 'form-label']) . html_writer::select($currencyoptions, 'currency', $price?->get_currency() ?? '', false, ['class' => 'form-select']), 'col-md-2');
    $html .= html_writer::div(html_writer::tag('label', get_string('commerce_price_amount', 'local_subscriptions'), ['class' => 'form-label']) . html_writer::empty_tag('input', ['name' => 'amount', 'value' => $amount, 'class' => 'form-control', 'required' => true]), 'col-md-2');
    $activeid = 'commerce-price-active-' . ($id ?: 'new');
    $activecheckbox = html_writer::empty_tag('input', [
        'type' => 'checkbox',
        'name' => 'active',
        'id' => $activeid,
        'value' => 1,
        'class' => 'form-check-input mt-0',
    ] + (($price?->is_active() ?? false) ? ['checked' => 'checked'] : []));
    $activelabel = html_writer::tag('label', get_string('active'), [
        'for' => $activeid,
        'class' => 'form-check-label ms-2 mb-0',
    ]);
    $html .= html_writer::div(
        html_writer::div($activecheckbox . $activelabel, 'form-check d-flex align-items-center'),
        'col-md-2 pb-2'
    );
    $buttons = html_writer::tag('button', $id ? get_string('savechanges') : get_string('add'), ['type' => 'submit', 'class' => 'btn btn-primary']);
    if ($id) {
        $deleteurl = new moodle_url('/local/subscriptions/admin/commerce/products/prices.php', ['sku' => $sku, 'action' => 'delete', 'priceid' => $id, 'sesskey' => sesskey()]);
        $buttons .= html_writer::link($deleteurl, get_string('delete'), ['class' => 'btn btn-outline-danger ms-2', 'data-confirmation' => 'modal', 'data-confirmation-title-str' => json_encode(['delete']), 'data-confirmation-question-str' => json_encode(['areyousure'])]);
    }
    $html .= html_writer::div($buttons, 'col-md-4');
    $html .= html_writer::end_tag('form');
    return $html;
};

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CommerceProductEditorNavigationRenderer::breadcrumb($product->get_name(), get_string('commerce_product_step_prices', 'local_subscriptions'));
echo CommerceProductEditorNavigationRenderer::render($product, CommerceProductEditorNavigationRenderer::PRICES);
echo $OUTPUT->heading(get_string('commerce_product_prices_title', 'local_subscriptions'));
echo html_writer::tag('p', get_string('commerce_prices_catalogue_help', 'local_subscriptions'), ['class' => 'text-muted']);
foreach ($prices as $price) {
    echo html_writer::div($renderform($price), 'card card-body mb-3');
}
echo html_writer::tag('h3', get_string('commerce_add_price', 'local_subscriptions'), ['class' => 'h5 mt-4']);
echo html_writer::div($renderform(), 'card card-body border-primary');
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
