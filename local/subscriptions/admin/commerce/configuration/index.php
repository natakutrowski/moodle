<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\subscription_config;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$pageurl = new moodle_url('/local/subscriptions/admin/commerce/configuration/index.php');
$title = get_string('commerce_configuration_title', 'local_subscriptions');
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $title, 'local-subscriptions-commerce-configuration-page');

$cards = [
    [
        'icon' => '🔑',
        'title' => get_string('commerce_configuration_scopes_title', 'local_subscriptions'),
        'description' => get_string('commerce_configuration_scopes_description', 'local_subscriptions'),
        'url' => new moodle_url(subscription_config::commerce_access_scopes_page()),
    ],
    [
        'icon' => '🧩',
        'title' => get_string('commerce_configuration_plans_title', 'local_subscriptions'),
        'description' => get_string('commerce_configuration_plans_description', 'local_subscriptions'),
        'url' => new moodle_url(subscription_config::commerce_plans_page()),
    ],
    [
        'icon' => '🏷️',
        'title' => get_string('commerce_configuration_promotions_title', 'local_subscriptions'),
        'description' => get_string('commerce_configuration_promotions_description', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/promotions/index.php'),
    ],
];

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render($title, get_string('commerce_configuration_description', 'local_subscriptions'), HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::CONFIGURATION, $context);

echo html_writer::start_div('row g-4 commerce-configuration-hub');
foreach ($cards as $card) {
    $body = html_writer::div($card['icon'], 'commerce-configuration-card__icon', ['aria-hidden' => 'true']) .
        html_writer::tag('h2', $card['title'], ['class' => 'h4 mb-2']) .
        html_writer::tag('p', $card['description'], ['class' => 'text-muted flex-grow-1 mb-4']) .
        html_writer::span(get_string('commerce_configuration_open', 'local_subscriptions') . ' →', 'btn btn-outline-primary align-self-start');
    echo html_writer::div(
        html_writer::link($card['url'], $body, ['class' => 'card card-body h-100 text-decoration-none commerce-configuration-card']),
        'col-md-6 col-xl-4'
    );
}
echo html_writer::end_div();

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
