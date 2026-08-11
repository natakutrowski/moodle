<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOffer;
use local_subscriptions\commerce\personaloffer\admin\CommercePersonalOfferCrmPresentation;
use local_subscriptions\commerce\personaloffer\repository\MoodleCommercePersonalOfferRepository;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::VIEW_PAYMENTS);
$query = trim(optional_param('q', '', PARAM_RAW_TRIMMED));
$status = trim(optional_param('status', '', PARAM_ALPHANUMEXT));
$campaignkey = trim(optional_param('campaignkey', '', PARAM_TEXT));
$page = max(0, optional_param('page', 0, PARAM_INT));
$perpage = min(200, max(25, optional_param('perpage', 100, PARAM_INT)));

$allowedstatuses = [
    '',
    CommercePersonalOffer::STATUS_ISSUED,
    CommercePersonalOffer::STATUS_REDEEMED,
    CommercePersonalOffer::STATUS_REVOKED,
];
if (!in_array($status, $allowedstatuses, true)) {
    $status = '';
}

$filters = array_filter([
    'beneficiaryquery' => $query,
    'status' => $status,
    'campaignkey' => $campaignkey,
], static fn(mixed $value): bool => $value !== '');

$params = ['page' => $page, 'perpage' => $perpage] + $filters;
$pageurl = new moodle_url('/local/subscriptions/admin/commerce/personal-offers/index.php', $params);
$title = get_string('commerce_personal_offers_title', 'local_subscriptions');
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $title, 'local-subscriptions-commerce-personal-offers-page');

$repository = new MoodleCommercePersonalOfferRepository($DB);
$total = $repository->count($filters);
$offers = $repository->find($filters, $perpage, $page * $perpage);
$now = time();

$statusbadge = static function(CommercePersonalOffer $offer) use ($now): string {
    $effective = $offer->get_effective_status($now);
    $map = [
        CommercePersonalOffer::STATUS_ISSUED => ['commerce_personal_offer_status_issued', 'badge bg-primary'],
        CommercePersonalOffer::STATUS_REDEEMED => ['commerce_personal_offer_status_redeemed', 'badge bg-success'],
        CommercePersonalOffer::STATUS_REVOKED => ['commerce_personal_offer_status_revoked', 'badge bg-secondary'],
        CommercePersonalOffer::EFFECTIVE_EXPIRED => ['commerce_personal_offer_status_expired', 'badge bg-warning text-dark'],
    ];
    [$key, $class] = $map[$effective];
    return html_writer::span(get_string($key, 'local_subscriptions'), $class);
};

$pricinglabel = static function(CommercePersonalOffer $offer): string {
    $terms = $offer->get_terms();
    $strategy = $terms->get_pricing_strategy();
    if ($strategy === \local_subscriptions\commerce\personaloffer\domain\CommercePersonalOfferTerms::STRATEGY_PERCENTAGE_DISCOUNT) {
        return format_float(($terms->get_percentage_basispoints() ?? 0) / 100, 2) . '%';
    }
    $amounts = $terms->get_data()['pricing']['amounts'] ?? [];
    $parts = [];
    foreach ($amounts as $currency => $minor) {
        $parts[] = s((string)$currency) . ' ' . format_float(((int)$minor) / 100, 2);
    }
    return implode(' / ', $parts);
};

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render($title, get_string('commerce_personal_offers_description', 'local_subscriptions'), HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::PERSONAL_OFFERS, $context);

echo html_writer::div(get_string('commerce_personal_offers_admin_notice', 'local_subscriptions'), 'alert alert-info');
$tools = html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/personal-offers/campaigns.php'), get_string('commerce_personal_offer_campaigns', 'local_subscriptions'), ['class' => 'btn btn-outline-primary me-2']);
$tools .= html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/personal-offers/stats.php'), get_string('commerce_personal_offer_stats_title', 'local_subscriptions'), ['class' => 'btn btn-outline-primary me-2']);
if (has_capability(Capabilities::MANAGE_CRM_ADMIN_TOOLS, $context)) {
    $tools .= html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/personal-offers/create.php'), get_string('commerce_personal_offer_create_individual', 'local_subscriptions'), ['class' => 'btn btn-primary me-2']);
    $tools .= html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/personal-offers/export.php', $campaignkey !== '' ? ['campaignkey' => $campaignkey] : []), get_string('commerce_personal_offer_export', 'local_subscriptions'), ['class' => 'btn btn-outline-secondary']);
}
echo html_writer::div($tools, 'mb-3');

$filterurl = new moodle_url('/local/subscriptions/admin/commerce/personal-offers/index.php');
echo html_writer::start_tag('form', ['method' => 'get', 'action' => $filterurl->out(false), 'class' => 'row g-2 align-items-end mb-4']);
echo html_writer::start_div('col-12 col-lg-4');
echo html_writer::tag('label', get_string('commerce_personal_offer_beneficiary_search', 'local_subscriptions'), ['for' => 'offer-query', 'class' => 'form-label']);
echo html_writer::empty_tag('input', ['id' => 'offer-query', 'type' => 'text', 'name' => 'q', 'value' => $query, 'class' => 'form-control', 'placeholder' => get_string('commerce_personal_offer_beneficiary_search_placeholder', 'local_subscriptions')]);
echo html_writer::end_div();
echo html_writer::start_div('col-12 col-lg-3');
echo html_writer::tag('label', get_string('commerce_personal_offer_campaign', 'local_subscriptions'), ['for' => 'offer-campaign', 'class' => 'form-label']);
echo html_writer::empty_tag('input', ['id' => 'offer-campaign', 'type' => 'text', 'name' => 'campaignkey', 'value' => $campaignkey, 'class' => 'form-control']);
echo html_writer::end_div();
echo html_writer::start_div('col-12 col-lg-3');
echo html_writer::tag('label', get_string('commerce_personal_offer_status', 'local_subscriptions'), ['for' => 'offer-status', 'class' => 'form-label']);
$options = ['' => get_string('all')] + [
    CommercePersonalOffer::STATUS_ISSUED => get_string('commerce_personal_offer_status_issued', 'local_subscriptions'),
    CommercePersonalOffer::STATUS_REDEEMED => get_string('commerce_personal_offer_status_redeemed', 'local_subscriptions'),
    CommercePersonalOffer::STATUS_REVOKED => get_string('commerce_personal_offer_status_revoked', 'local_subscriptions'),
];
echo html_writer::select($options, 'status', $status, false, ['id' => 'offer-status', 'class' => 'form-select']);
echo html_writer::end_div();
echo html_writer::div(html_writer::tag('button', get_string('filter'), ['type' => 'submit', 'class' => 'btn btn-primary']), 'col-auto');
echo html_writer::end_tag('form');

if ($offers === []) {
    echo html_writer::div(get_string('commerce_personal_offers_empty', 'local_subscriptions'), 'alert alert-light border');
} else {
    $table = new html_table();
    $table->attributes['class'] = 'generaltable table table-hover align-middle';
    $table->head = [
        get_string('commerce_personal_offer_id', 'local_subscriptions'),
        get_string('commerce_personal_offer_campaign', 'local_subscriptions'),
        get_string('commerce_personal_offer_beneficiary', 'local_subscriptions'),
        get_string('commerce_personal_offer_target', 'local_subscriptions'),
        get_string('commerce_personal_offer_pricing', 'local_subscriptions'),
        get_string('commerce_personal_offer_validity', 'local_subscriptions'),
        get_string('commerce_personal_offer_status', 'local_subscriptions'),
        get_string('actions'),
    ];

    foreach ($offers as $offer) {
        $beneficiaryuser = $offer->get_beneficiary_user_id() !== null
            ? $DB->get_record('user', ['id' => $offer->get_beneficiary_user_id(), 'deleted' => 0], 'id,firstname,lastname,email', IGNORE_MISSING)
            : null;
        $sourcepurchase = $offer->get_source_purchase_id() !== null
            ? $DB->get_record('local_subscriptions_commerce_purchase', ['id' => $offer->get_source_purchase_id()], 'id,reference,timecreated,customerjson', IGNORE_MISSING)
            : null;
        $name = CommercePersonalOfferCrmPresentation::customer_name_from_user($beneficiaryuser);
        if ($name === '') { $name = CommercePersonalOfferCrmPresentation::customer_name_from_purchase($sourcepurchase); }
        $beneficiary = $name !== '' ? html_writer::tag('strong', s($name)) . html_writer::div(s($offer->get_beneficiary_email()), 'small text-muted') : s($offer->get_beneficiary_email());
        if ($offer->get_beneficiary_user_id() !== null) {
            $beneficiary .= html_writer::div('user #' . $offer->get_beneficiary_user_id(), 'small text-muted');
        }
        $validity = '-';
        if ($offer->get_valid_from() !== null || $offer->get_expires_at() !== null) {
            $from = $offer->get_valid_from() === null ? '—' : userdate($offer->get_valid_from());
            $to = $offer->get_expires_at() === null ? '—' : userdate($offer->get_expires_at());
            $validity = s($from . ' → ' . $to);
        }
        $table->data[] = [
            html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/personal-offers/view.php', ['id' => $offer->get_id()]), html_writer::tag('code', s(substr($offer->get_offer_uuid(), 0, 12)))) . html_writer::div('#' . $offer->get_id(), 'small text-muted'),
            s($offer->get_campaign_key() ?? '—'),
            $beneficiary,
            s(CommercePersonalOfferCrmPresentation::product_label($DB, $offer->get_target_product_id())),
            $pricinglabel($offer),
            $validity,
            $statusbadge($offer),
            (function() use ($offer, $context): string {
                if (!has_capability(Capabilities::MANAGE_CRM_ADMIN_TOOLS, $context)) { return ''; }
                $effective = $offer->get_effective_status(time());
                $actions = [];
                if ($effective === CommercePersonalOffer::STATUS_ISSUED) {
                    $actions[] = html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/personal-offers/edit.php', ['id' => $offer->get_id()]), get_string('commerce_personal_offer_edit', 'local_subscriptions'), ['class' => 'btn btn-sm btn-outline-primary']);
                    $actions[] = html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/personal-offers/view.php', ['id' => $offer->get_id(), 'focus' => 'revoke']), get_string('commerce_personal_offer_revoke', 'local_subscriptions'), ['class' => 'btn btn-sm btn-outline-warning']);
                }
                $actions[] = html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/personal-offers/view.php', ['id' => $offer->get_id()]), get_string('view'), ['class' => 'btn btn-sm btn-outline-secondary']);
                return html_writer::div(implode(' ', $actions), 'd-flex flex-wrap gap-1');
            })(),
        ];
    }
    echo html_writer::table($table);
    if ($total > $perpage) {
        echo $OUTPUT->paging_bar($total, $page, $perpage, $filterurl, 'page');
    }
}

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
