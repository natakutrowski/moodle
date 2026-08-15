<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\commerce\dashboard\CommerceDashboardRepository;
use local_subscriptions\crm\commerce\rendering\CommerceDashboardRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\subscription_config;

global $PAGE, $OUTPUT;

$period = optional_param('period', '30', PARAM_ALPHANUMEXT);
$currency = strtoupper(optional_param('currency', '', PARAM_ALPHANUMEXT));
$customstart = null;
$customend = null;
if ($period === 'custom') {
    $from = optional_param('from', '', PARAM_RAW_TRIMMED);
    $to = optional_param('to', '', PARAM_RAW_TRIMMED);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
        $customstart = strtotime($from . ' 00:00:00') ?: null;
        $customend = strtotime($to . ' 23:59:59') ?: null;
    }
}

$context = AdminSecurity::require(
    Capabilities::VIEW_DASHBOARD
);

$pageurl = new moodle_url(
    subscription_config::
        admin_commerce_page()
);

$pagetitle = get_string(
    'crm_commerce_title',
    'local_subscriptions'
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    'local-subscriptions-commerce-page'
);

echo $OUTPUT->header();

echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::COMMERCE,
    $context
);

echo CrmBreadcrumbRenderer::render([
    [
        'label' => $pagetitle,
        'url' => null,
    ],
]);

echo CrmPageHeader::render(
    $pagetitle,
    get_string(
        'crm_commerce_description',
        'local_subscriptions'
    ),
    HelpContext::COMMERCE
);

echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::OVERVIEW
);


$dashboard = (new CommerceDashboardRepository($DB))->snapshot(null, $period, $customstart, $customend, $currency);
echo CommerceDashboardRenderer::render($dashboard);

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();