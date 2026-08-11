<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\statistics\CommerceStatisticsFilter;
use local_subscriptions\commerce\statistics\CommerceStatisticsPeriod;
use local_subscriptions\commerce\statistics\CommerceStatisticsRepository;
use local_subscriptions\commerce\statistics\CommerceStatisticsService;
use local_subscriptions\commerce\statistics\CommerceStatisticsSeriesService;
use local_subscriptions\crm\commerce\presentation\CommerceDesignSystemRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\commerce\statistics\CommerceStatisticsFilterRenderer;
use local_subscriptions\crm\commerce\statistics\CommerceStatisticsPageRenderer;
use local_subscriptions\crm\commerce\statistics\CommerceProductStatisticsRenderer;
use local_subscriptions\crm\commerce\statistics\CommerceStatisticsChartRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\subscription_config;

$context = AdminSecurity::require(Capabilities::VIEW_STATISTICS);
$days = max(1, min(365, optional_param('days', 30, PARAM_INT)));
$currency = strtoupper(optional_param('currency', '', PARAM_ALPHA));
$provider = strtolower(optional_param('provider', '', PARAM_ALPHANUMEXT));
$chartmode = optional_param('chartmode', 'instant', PARAM_ALPHA);
if (!in_array($chartmode, ['instant', 'cumulative'], true)) {
    $chartmode = 'instant';
}
if (!in_array($currency, ['', 'EUR', 'RUB'], true)) {
    $currency = '';
}
if (!in_array($provider, ['', 'stripe', 'alfa'], true)) {
    $provider = '';
}

$pageurl = new moodle_url('/local/subscriptions/admin/commerce/statistics/index.php', array_filter([
    'days' => $days,
    'currency' => $currency,
    'provider' => $provider,
    'chartmode' => $chartmode,
]));
$baseurl = new moodle_url('/local/subscriptions/admin/commerce/statistics/index.php');
$pagetitle = get_string('commerce_statistics_title', 'local_subscriptions');

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    'local-subscriptions-commerce-statistics-page'
);

$period = CommerceStatisticsPeriod::last_days($days);
$filter = new CommerceStatisticsFilter($currency !== '' ? $currency : null, $provider !== '' ? $provider : null);
$repository = new CommerceStatisticsRepository($DB);
$snapshot = (new CommerceStatisticsService($repository))->snapshot($period, $filter);
$series = new CommerceStatisticsSeriesService($repository);
$revenueseries = $series->revenue($period, $filter);
$ordersseries = $series->orders($period, $filter);
$paymenthealth = $series->payment_health($period, $filter);
$topproducts = $series->top_products($period, $filter);
$productstatistics = $repository->product_statistics($period, $filter);

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    [
        'label' => get_string('crm_commerce_title', 'local_subscriptions'),
        'url' => new moodle_url(subscription_config::admin_commerce_page()),
    ],
    ['label' => $pagetitle, 'url' => null],
]);
echo CrmPageHeader::render(
    $pagetitle,
    get_string('commerce_statistics_description', 'local_subscriptions'),
    HelpContext::COMMERCE
);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::STATISTICS);
echo CommerceDesignSystemRenderer::filter_panel(
    CommerceStatisticsFilterRenderer::render($baseurl, $days, $currency, $provider, $chartmode)
);
echo html_writer::tag(
    'p',
    get_string('commerce_statistics_period_summary', 'local_subscriptions', (object)[
        'from' => userdate($period->start(), get_string('strftimedatefullshort', 'langconfig')),
        'to' => userdate($period->end(), get_string('strftimedatefullshort', 'langconfig')),
    ]),
    ['class' => 'text-muted mb-4']
);
echo CommerceStatisticsPageRenderer::dashboard($snapshot);
echo html_writer::tag('h2', get_string('commerce_statistics_charts_title', 'local_subscriptions'), ['class' => 'h3 mt-5 mb-3']);
echo CommerceStatisticsChartRenderer::dashboard($OUTPUT, $revenueseries, $ordersseries, $paymenthealth, $topproducts, $chartmode === 'cumulative');
echo html_writer::tag('h2', get_string('commerce_statistics_products_title', 'local_subscriptions'), ['class' => 'h3 mt-5 mb-3']);
echo html_writer::tag('p', get_string('commerce_statistics_products_description', 'local_subscriptions'), ['class' => 'text-muted']);
echo CommerceProductStatisticsRenderer::render($productstatistics);
echo CommerceStatisticsPageRenderer::operational_shortcuts();
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
