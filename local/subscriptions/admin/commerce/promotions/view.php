<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\catalog\readmodel\CommerceCatalogReadRepository;
use local_subscriptions\commerce\promotion\domain\CommercePromotion;
use local_subscriptions\commerce\promotion\eligibility\CommercePromotionEligibilityRuleSet;
use local_subscriptions\commerce\promotion\repository\MoodleCommercePromotionRepository;
use local_subscriptions\crm\commerce\rendering\CommerceOffersAccessNavigationRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$id = required_param('id', PARAM_INT);
$repository = new MoodleCommercePromotionRepository();
$promotion = $repository->get_by_id($id);
if ($promotion === null) {
    throw new moodle_exception('invalidrecord');
}

$listurl = new moodle_url('/local/subscriptions/admin/commerce/promotions/index.php');
$pageurl = new moodle_url('/local/subscriptions/admin/commerce/promotions/view.php', ['id' => $id]);
$title = $promotion->get_name();
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $title, 'local-subscriptions-commerce-promotion-view-page');

$catalog = new CommerceCatalogReadRepository($DB);
$productlabels = [];
foreach ($catalog->find_all() as $product) {
    $productlabels[$product->get_sku()] = $product->get_name();
}
$typeoptions = [
    CommerceProductType::COURSE_ACCESS => get_string('commerce_product_type_course_access', 'local_subscriptions'),
    CommerceProductType::DIGITAL_DOWNLOAD => get_string('commerce_product_type_digital_download', 'local_subscriptions'),
    CommerceProductType::BUNDLE => get_string('commerce_product_type_bundle', 'local_subscriptions'),
    CommerceProductType::SERVICE => get_string('commerce_product_type_service', 'local_subscriptions'),
];
$rules = CommercePromotionEligibilityRuleSet::from_metadata($promotion->get_metadata());

$currencyflags = [
    'EUR' => '🇪🇺',
    'RUB' => '🇷🇺',
    'USD' => '🇺🇸',
    'GBP' => '🇬🇧',
    'CHF' => '🇨🇭',
    'CAD' => '🇨🇦',
    'JPY' => '🇯🇵',
];
$currencydisplay = static function(?string $currency) use ($currencyflags): string {
    if ($currency === null || $currency === '') {
        return html_writer::span('🌍 ' . get_string('commerce_promotion_all_currencies', 'local_subscriptions'), 'badge rounded-pill bg-light text-dark border');
    }
    return html_writer::span(($currencyflags[$currency] ?? '💱') . ' ' . s($currency), 'badge rounded-pill bg-light text-dark border');
};
$unlimited = static function(): string {
    return html_writer::span('∞', 'commerce-promotion-infinity-icon', [
        'title' => get_string('commerce_promotion_unlimited', 'local_subscriptions'),
        'aria-label' => get_string('commerce_promotion_unlimited', 'local_subscriptions'),
    ]);
};

$formatvalue = static function(CommercePromotion $promotion): string {
    if ($promotion->get_discount_type() === CommercePromotion::TYPE_PERCENTAGE) {
        return format_float($promotion->get_discount_value() / 100, 2) . ' %';
    }
    return format_float($promotion->get_discount_value() / 100, 2) . ' ' . ($promotion->get_currency() ?? '');
};
$listlabels = static function(array $ids, array $labels): string {
    if ($ids === []) {
        return html_writer::span(get_string('commerce_promotion_no_restriction', 'local_subscriptions'), 'text-muted');
    }
    $items = [];
    foreach ($ids as $id) {
        $items[] = html_writer::span(s($labels[$id] ?? $id), 'badge rounded-pill bg-light text-dark border me-1 mb-1');
    }
    return implode('', $items);
};
$window = static function(?int $start, ?int $end): string {
    if ($start === null && $end === null) {
        return get_string('commerce_promotion_validity_unlimited', 'local_subscriptions');
    }
    $from = $start === null ? get_string('commerce_promotion_validity_immediate', 'local_subscriptions') : userdate($start, get_string('strftimedatetimeshort', 'langconfig'));
    $to = $end === null ? get_string('commerce_promotion_validity_no_end', 'local_subscriptions') : userdate($end, get_string('strftimedatetimeshort', 'langconfig'));
    return get_string('commerce_promotion_validity_range', 'local_subscriptions', (object)['from' => $from, 'to' => $to]);
};

$actions = html_writer::link(
    new moodle_url('/local/subscriptions/admin/commerce/promotions/edit.php', ['id' => $id]),
    html_writer::tag('i', '', ['class' => 'fa fa-pencil me-1', 'aria-hidden' => 'true']) . get_string('edit'),
    ['class' => 'btn btn-primary']
) . html_writer::link(
    $listurl,
    html_writer::tag('i', '', ['class' => 'fa fa-arrow-left me-1', 'aria-hidden' => 'true']) . get_string('back'),
    ['class' => 'btn btn-outline-secondary ms-2']
);

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('commerce_offers_access_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/offers-access/index.php')],
    ['label' => get_string('commerce_promotions_title', 'local_subscriptions'), 'url' => $listurl],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render($title, get_string('commerce_promotion_view_description', 'local_subscriptions'), HelpContext::COMMERCE, $actions);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::OFFERS_ACCESS, $context);
echo CommerceOffersAccessNavigationRenderer::render(CommerceOffersAccessNavigationRenderer::PROMOTIONS);

$summary = new html_table();
$summary->attributes['class'] = 'table mb-0 commerce-promotion-detail-table';
$summary->data = [
    [get_string('commerce_promotion_mode', 'local_subscriptions'), $promotion->is_automatic()
        ? html_writer::span(get_string('commerce_promotion_automatic_badge', 'local_subscriptions'), 'badge rounded-pill text-bg-info')
        : html_writer::span(get_string('commerce_promotion_coupon_badge', 'local_subscriptions'), 'badge rounded-pill text-bg-warning')],
    [get_string('commerce_promotion_code', 'local_subscriptions'), $promotion->get_code() === null ? html_writer::span('—', 'text-muted') : html_writer::tag('strong', s($promotion->get_code()))],
    [get_string('commerce_promotion_value', 'local_subscriptions'), html_writer::tag('strong', s($formatvalue($promotion)))],
    [get_string('commerce_promotion_validity', 'local_subscriptions'), s($window($promotion->get_starts_at(), $promotion->get_ends_at()))],
    [get_string('status'), $promotion->is_active() ? html_writer::span(get_string('active'), 'badge rounded-pill text-bg-success') : html_writer::span(get_string('inactive'), 'badge rounded-pill bg-light text-dark border')],
    [get_string('commerce_promotion_priority', 'local_subscriptions'), (int)$promotion->get_priority()],
    [get_string('commerce_promotion_uses', 'local_subscriptions'), $repository->count_redemptions($id)],
];

echo html_writer::start_div('row g-3 commerce-promotion-view-grid');
echo html_writer::start_div('col-12 col-xl-6');
echo html_writer::start_div('card commerce-promotion-view-card h-100');
echo html_writer::div(html_writer::tag('h2', get_string('commerce_promotion_view_summary', 'local_subscriptions'), ['class' => 'h5 mb-0']), 'commerce-promotions-list-header');
echo html_writer::div(html_writer::table($summary), 'table-responsive');
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('col-12 col-xl-6');
echo html_writer::start_div('card commerce-promotion-view-card h-100');
echo html_writer::div(html_writer::tag('h2', get_string('commerce_promotion_view_limits', 'local_subscriptions'), ['class' => 'h5 mb-0']), 'commerce-promotions-list-header');
$limits = new html_table();
$limits->attributes['class'] = 'table mb-0 commerce-promotion-detail-table';
$limits->data = [
    [get_string('currency'), $currencydisplay($promotion->get_currency())],
    [get_string('commerce_promotion_minimum_display', 'local_subscriptions'), format_float($promotion->get_minimum_cart_minor() / 100, 2)],
    [get_string('commerce_promotion_global_limit', 'local_subscriptions'), $promotion->get_global_usage_limit() ?? $unlimited()],
    [get_string('commerce_promotion_user_limit', 'local_subscriptions'), $promotion->get_user_usage_limit() ?? $unlimited()],
    [get_string('commerce_promotion_stackable', 'local_subscriptions'), $promotion->is_stackable() ? get_string('yes') : get_string('no')],
];
echo html_writer::div(html_writer::table($limits), 'table-responsive');
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

$products = new html_table();
$products->attributes['class'] = 'table mb-0 commerce-promotion-detail-table';
$products->data = [
    [get_string('commerce_promotion_productskus', 'local_subscriptions'), $listlabels($promotion->get_product_skus(), $productlabels)],
    [get_string('commerce_promotion_producttypes', 'local_subscriptions'), $listlabels($promotion->get_product_types(), $typeoptions)],
];
echo html_writer::start_div('card commerce-promotion-view-card mt-3');
echo html_writer::div(html_writer::tag('h2', get_string('commerce_promotion_section_products', 'local_subscriptions'), ['class' => 'h5 mb-0']), 'commerce-promotions-list-header');
echo html_writer::div(html_writer::table($products), 'table-responsive');
echo html_writer::end_div();

$eligibility = new html_table();
$eligibility->attributes['class'] = 'table mb-0 commerce-promotion-detail-table';
$eligibility->data = [
    [get_string('commerce_promotion_requires_login', 'local_subscriptions'), $rules->requires_login() ? get_string('yes') : get_string('no')],
    [get_string('commerce_promotion_eligibility_mode', 'local_subscriptions'), get_string($rules->get_mode() === CommercePromotionEligibilityRuleSet::MODE_ALL ? 'commerce_promotion_eligibility_all' : 'commerce_promotion_eligibility_any', 'local_subscriptions')],
    [get_string('commerce_promotion_required_owned_products', 'local_subscriptions'), $listlabels($rules->get_owned_skus(), $productlabels)],
    [get_string('commerce_promotion_excluded_owned_products', 'local_subscriptions'), $listlabels($rules->get_not_owned_skus(), $productlabels)],
];
echo html_writer::start_div('card commerce-promotion-view-card mt-3');
echo html_writer::div(html_writer::tag('h2', get_string('commerce_promotion_customer_eligibility', 'local_subscriptions'), ['class' => 'h5 mb-0']), 'commerce-promotions-list-header');
echo html_writer::div(html_writer::table($eligibility), 'table-responsive');
echo html_writer::end_div();

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
