<?php
require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\commerce\rendering\CommerceOffersAccessNavigationRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\subscription_config;

$context = AdminSecurity::require(Capabilities::VIEW_PAYMENTS);
$url = new moodle_url('/local/subscriptions/admin/commerce/offers-access/index.php');
$title = get_string('commerce_offers_access_title', 'local_subscriptions');
CrmPageConfigurator::configure($PAGE, $context, $url, $title, 'local-subscriptions-commerce-offers-access-page');

$now = time();
$activeoffers = $DB->count_records_select('local_subs_commerce_offer', "status = :status AND (expiresat IS NULL OR expiresat > :now)", ['status' => 'issued', 'now' => $now]);
$redeemedoffers = $DB->count_records('local_subs_commerce_offer', ['status' => 'redeemed']);
$grants = (int)$DB->count_records_select(
    'local_subs_commerce_grant',
    $DB->sql_like('purchasereference', ':manualsource', false),
    ['manualsource' => 'manual-u%']
);
$offercampaigns = $DB->count_records_select('local_subs_commerce_offer_campaign', "status NOT IN ('closed','completed')");
$grantcampaigns = $DB->count_records_select('local_subs_commerce_grant_campaign', "status NOT IN ('completed','closed')");
$pending = $DB->count_records_select('local_subs_commerce_grant_campaign', 'processedcount < selectedcount');
$errors = $DB->get_field_sql('SELECT COALESCE(SUM(failedcount),0) FROM {local_subs_commerce_grant_campaign}');

$recentactivity = [];

foreach ($DB->get_records(
    'local_subs_commerce_offer',
    null,
    'timecreated DESC',
    'id,beneficiaryemail,targetproductid,status,timecreated',
    0,
    4
) as $offer) {
    $product = $DB->get_record(
        'local_subs_commerce_product',
        ['id' => (int)$offer->targetproductid],
        'id,name',
        IGNORE_MISSING
    );
    $recentactivity[] = [
        'time' => (int)$offer->timecreated,
        'icon' => 'fa-tag',
        'class' => 'is-offer',
        'title' => get_string(
            'commerce_offers_access_recent_offer',
            'local_subscriptions',
            (object)[
                'email' => (string)$offer->beneficiaryemail,
                'product' => $product ? (string)$product->name : '',
            ]
        ),
        'status' => (string)$offer->status,
        'url' => new moodle_url(
            '/local/subscriptions/admin/commerce/personal-offers/view.php',
            ['id' => (int)$offer->id]
        ),
    ];
}

[$manualsql, $manualparams] = [
    $DB->sql_like('purchasereference', ':manualsource', false),
    ['manualsource' => 'manual-u%'],
];
foreach ($DB->get_records_select(
    'local_subs_commerce_grant',
    $manualsql,
    $manualparams,
    'timecreated DESC',
    'id,beneficiaryemail,productsku,status,timecreated',
    0,
    4
) as $grant) {
    $product = $DB->get_record(
        'local_subs_commerce_product',
        ['sku' => (string)$grant->productsku],
        'id,name',
        IGNORE_MISSING
    );
    $recentactivity[] = [
        'time' => (int)$grant->timecreated,
        'icon' => 'fa-key',
        'class' => 'is-grant',
        'title' => get_string(
            'commerce_offers_access_recent_grant',
            'local_subscriptions',
            (object)[
                'email' => (string)$grant->beneficiaryemail,
                'product' => $product ? (string)$product->name : (string)$grant->productsku,
            ]
        ),
        'status' => (string)$grant->status,
        'url' => new moodle_url(
            '/local/subscriptions/admin/commerce/grants/view.php',
            ['id' => (int)$grant->id]
        ),
    ];
}

foreach ($DB->get_records(
    'local_subs_commerce_offer_campaign',
    null,
    'timecreated DESC',
    'id,name,status,timecreated',
    0,
    3
) as $campaign) {
    $recentactivity[] = [
        'time' => (int)$campaign->timecreated,
        'icon' => 'fa-users',
        'class' => 'is-campaign',
        'title' => get_string(
            'commerce_offers_access_recent_campaign',
            'local_subscriptions',
            (string)$campaign->name
        ),
        'status' => (string)$campaign->status,
        'url' => new moodle_url(
            '/local/subscriptions/admin/commerce/personal-offers/campaign_view.php',
            ['id' => (int)$campaign->id]
        ),
    ];
}
usort(
    $recentactivity,
    static fn(array $a, array $b): int => $b['time'] <=> $a['time']
);
$recentactivity = array_slice($recentactivity, 0, 6);

$metric = static function(string $icon, string $class, string $label, int $value, string $foot): string {
    return html_writer::div(
        html_writer::span(html_writer::tag('i', '', ['class' => 'fa ' . $icon, 'aria-hidden' => 'true']), 'crm-offers-access-metric-icon ' . $class) .
        html_writer::div(html_writer::div(s($label), 'crm-offers-access-metric-label') . html_writer::div((string)$value, 'crm-offers-access-metric-value') . html_writer::div(s($foot), 'crm-offers-access-metric-foot'), 'crm-offers-access-metric-copy'),
        'crm-offers-access-metric'
    );
};

$activitystatus = static function(array $item): string {
    $status = (string)$item['status'];
    if ($item['class'] === 'is-offer') {
        $key = match ($status) {
            'issued' => 'commerce_personal_offer_status_issued',
            'redeemed' => 'commerce_personal_offer_status_redeemed',
            'revoked' => 'commerce_personal_offer_status_revoked',
            default => null,
        };
    } else if ($item['class'] === 'is-grant') {
        $key = match ($status) {
            'planned' => 'commerce_offers_access_grant_status_planned',
            'active' => 'commerce_offers_access_grant_status_active',
            'failed' => 'commerce_offers_access_grant_status_failed',
            'completed' => 'commerce_offers_access_grant_status_completed',
            default => null,
        };
    } else {
        $key = match ($status) {
            'draft' => 'commerce_personal_offer_campaign_status_draft',
            'previewed' => 'commerce_personal_offer_campaign_status_previewed',
            'snapshot' => 'commerce_personal_offer_campaign_status_snapshot',
            'issued' => 'commerce_personal_offer_campaign_status_issued',
            'closed' => 'commerce_personal_offer_campaign_status_closed',
            default => null,
        };
    }
    return $key ? get_string($key, 'local_subscriptions') : $status;
};

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render($title, get_string('commerce_offers_access_description', 'local_subscriptions'), HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::OFFERS_ACCESS, $context);
echo CommerceOffersAccessNavigationRenderer::render(CommerceOffersAccessNavigationRenderer::OVERVIEW);

echo html_writer::start_div('crm-offers-access-action-panel');
echo html_writer::div(get_string('commerce_offers_access_question', 'local_subscriptions'), 'crm-offers-access-eyebrow');
echo html_writer::start_div('crm-offers-access-actions');
$actions = [
    ['offer', 'fa-tag', 'commerce_offers_access_offer_title', 'commerce_offers_access_offer_description', '/local/subscriptions/admin/commerce/offers-access/create.php?kind=offer', 'commerce_offers_access_offer_action'],
    ['grant', 'fa-key', 'commerce_offers_access_grant_title', 'commerce_offers_access_grant_description', '/local/subscriptions/admin/commerce/offers-access/create.php?kind=grant', 'commerce_offers_access_grant_action'],
    ['promotion', 'fa-percent', 'commerce_offers_access_promotion_title', 'commerce_offers_access_promotion_description', '/local/subscriptions/admin/commerce/promotions/edit.php', 'commerce_offers_access_promotion_action'],
];
foreach ($actions as [$kind, $icon, $heading, $description, $href, $button]) {
    echo html_writer::start_div('crm-offers-access-action-card is-' . $kind);
    echo html_writer::span(html_writer::tag('i', '', ['class' => 'fa ' . $icon, 'aria-hidden' => 'true']), 'crm-offers-access-action-icon');
    echo html_writer::tag('h2', get_string($heading, 'local_subscriptions'), ['class' => 'crm-offers-access-action-title']);
    echo html_writer::tag('p', get_string($description, 'local_subscriptions'), ['class' => 'crm-offers-access-action-description']);
    $actionurl = str_contains($href, '?')
        ? new moodle_url(
            strtok($href, '?'),
            ['kind' => str_contains($href, 'kind=grant') ? 'grant' : 'offer']
        )
        : new moodle_url($href);
    echo html_writer::link($actionurl, html_writer::tag('i', '', ['class' => 'fa fa-plus me-1', 'aria-hidden' => 'true']) . get_string($button, 'local_subscriptions'), ['class' => 'btn crm-offers-access-action-button']);
    echo html_writer::end_div();
}
echo html_writer::end_div();
echo html_writer::div(html_writer::tag('i', '', ['class' => 'fa fa-info-circle me-2', 'aria-hidden' => 'true']) . get_string('commerce_offers_access_beneficiary_hint', 'local_subscriptions'), 'crm-offers-access-hint');
echo html_writer::end_div();

echo html_writer::start_div('crm-offers-access-metrics');
echo $metric('fa-tag', 'is-offer', get_string('commerce_offers_access_kpi_active_offers', 'local_subscriptions'), $activeoffers, get_string('commerce_offers_access_kpi_now', 'local_subscriptions'));
echo $metric('fa-check-circle', 'is-success', get_string('commerce_offers_access_kpi_used_offers', 'local_subscriptions'), $redeemedoffers, get_string('commerce_offers_access_kpi_total', 'local_subscriptions'));
echo $metric('fa-key', 'is-grant', get_string('commerce_offers_access_kpi_grants', 'local_subscriptions'), $grants, get_string('commerce_offers_access_kpi_total', 'local_subscriptions'));
echo $metric('fa-users', 'is-campaign', get_string('commerce_offers_access_kpi_campaigns', 'local_subscriptions'), $offercampaigns + $grantcampaigns, get_string('commerce_offers_access_kpi_active', 'local_subscriptions'));
echo $metric('fa-clock-o', 'is-pending', get_string('commerce_offers_access_kpi_pending', 'local_subscriptions'), $pending, get_string('commerce_offers_access_kpi_to_process', 'local_subscriptions'));
echo $metric('fa-exclamation-triangle', 'is-error', get_string('commerce_offers_access_kpi_attention', 'local_subscriptions'), (int)$errors, get_string('commerce_offers_access_kpi_to_check', 'local_subscriptions'));
echo html_writer::end_div();

echo html_writer::start_div('crm-offers-access-panel');
echo html_writer::div(
    html_writer::tag(
        'h2',
        get_string('commerce_offers_access_recent_title', 'local_subscriptions'),
        ['class' => 'crm-offers-access-panel-title']
    )
    . html_writer::link(
        new moodle_url('/local/subscriptions/admin/commerce/offers-access/campaigns.php'),
        get_string('commerce_offers_access_view_all', 'local_subscriptions') . ' →',
        ['class' => 'crm-offers-access-panel-link']
    ),
    'crm-offers-access-panel-head'
);
if ($recentactivity === []) {
    echo html_writer::div(
        get_string('commerce_offers_access_recent_hint', 'local_subscriptions'),
        'crm-offers-access-empty'
    );
} else {
    echo html_writer::start_div('crm-offers-access-recent-activity');
    foreach ($recentactivity as $item) {
        echo html_writer::link(
            $item['url'],
            html_writer::span(
                html_writer::tag('i', '', [
                    'class' => 'fa ' . $item['icon'],
                    'aria-hidden' => 'true',
                ]),
                'crm-offers-access-recent-activity-icon ' . $item['class']
            )
            . html_writer::div(
                html_writer::div(
                    s($item['title']),
                    'crm-offers-access-recent-activity-title'
                )
                . html_writer::div(
                    userdate(
                        $item['time'],
                        get_string('strftimedatetimeshort', 'langconfig')
                    ),
                    'crm-offers-access-recent-activity-time'
                ),
                'crm-offers-access-recent-activity-copy'
            )
            . html_writer::span(
                s($activitystatus($item)),
                'crm-offers-access-status'
            ),
            ['class' => 'crm-offers-access-recent-activity-row']
        );
    }
    echo html_writer::end_div();
}
echo html_writer::end_div();

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
