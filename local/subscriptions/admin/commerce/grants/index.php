<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\grant\CommerceBulkGrantCampaignService;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\subscription_config;

$context = AdminSecurity::require(Capabilities::MANAGE_SUBSCRIPTIONS);
$url = new moodle_url('/local/subscriptions/admin/commerce/grants/index.php');
$title = get_string('commerce_grants_title', 'local_subscriptions');

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $url,
    $title,
    'local-subscriptions-commerce-grants-page'
);

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    [
        'label' => get_string('crm_commerce_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php'),
    ],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render(
    $title,
    get_string('commerce_grants_description', 'local_subscriptions'),
    HelpContext::COMMERCE
);
echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::GRANTS,
    $context
);

$cards = [
    [
        'icon' => '👤',
        'title' => get_string('commerce_grants_manual_title', 'local_subscriptions'),
        'description' => get_string('commerce_grants_manual_description', 'local_subscriptions'),
        'url' => new moodle_url(subscription_config::add_manual_subscription_page()),
        'button' => get_string('commerce_grants_manual_action', 'local_subscriptions'),
    ],
    [
        'icon' => '👥',
        'title' => get_string('commerce_bulk_grant_title', 'local_subscriptions'),
        'description' => get_string('commerce_bulk_grant_description', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/grants/bulk.php'),
        'button' => get_string('commerce_bulk_grant_open', 'local_subscriptions'),
    ],
];

echo html_writer::start_div('row');
foreach ($cards as $card) {
    echo html_writer::start_div('col-lg-6 mb-4');
    echo html_writer::start_div('card h-100 shadow-sm border-0');
    echo html_writer::start_div('card-body p-4');
    echo html_writer::div($card['icon'], 'fs-2 mb-3');
    echo html_writer::tag('h2', s($card['title']), ['class' => 'h5']);
    echo html_writer::tag('p', s($card['description']), ['class' => 'text-muted']);
    echo html_writer::link($card['url'], s($card['button']), ['class' => 'btn btn-primary']);
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();
}
echo html_writer::end_div();



$campaignservice = new CommerceBulkGrantCampaignService($DB);
$campaigns = $campaignservice->campaigns();

echo html_writer::tag(
    'h2',
    get_string('commerce_bulk_grant_campaigns_title', 'local_subscriptions'),
    ['class' => 'h4 mt-4 mb-3']
);

if ($campaigns === []) {
    echo html_writer::div(
        get_string('commerce_bulk_grant_campaigns_empty', 'local_subscriptions'),
        'alert alert-light border'
    );
} else {
    $table = new html_table();
    $table->attributes['class'] = 'generaltable table table-hover align-middle';
    $table->head = [
        get_string('name'),
        get_string('status'),
        get_string('commerce_bulk_grant_metric_total', 'local_subscriptions'),
        get_string('commerce_bulk_grant_campaign_metric_success', 'local_subscriptions'),
        get_string('commerce_bulk_grant_metric_error', 'local_subscriptions'),
        get_string('modified'),
    ];

    foreach ($campaigns as $campaign) {
        $statuskey = 'commerce_bulk_grant_campaign_status_' . (string)$campaign->status;
        $statuslabel = get_string_manager()->string_exists($statuskey, 'local_subscriptions')
            ? get_string($statuskey, 'local_subscriptions')
            : (string)$campaign->status;

        $table->data[] = [
            html_writer::link(
                new moodle_url(
                    '/local/subscriptions/admin/commerce/grants/campaign_view.php',
                    ['id' => (int)$campaign->id]
                ),
                s((string)$campaign->name)
            ),
            s($statuslabel),
            (int)$campaign->selectedcount,
            (int)$campaign->successcount,
            (int)$campaign->failedcount,
            userdate((int)$campaign->timemodified),
        ];
    }

    echo html_writer::table($table);
}

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
