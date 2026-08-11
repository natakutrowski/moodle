<?php
require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignManager;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::VIEW_PAYMENTS);
$url = new moodle_url('/local/subscriptions/admin/commerce/personal-offers/campaigns.php');
$title = get_string('commerce_personal_offer_campaigns', 'local_subscriptions');
CrmPageConfigurator::configure($PAGE, $context, $url, $title, 'local-subscriptions-commerce-personal-offer-campaigns-page');

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('commerce_personal_offers_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/personal-offers/index.php')],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render($title, get_string('commerce_personal_offer_campaigns_help', 'local_subscriptions'), HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::PERSONAL_OFFERS, $context);

if (has_capability(Capabilities::MANAGE_CRM_ADMIN_TOOLS, $context)) {
    echo html_writer::div(
        html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/personal-offers/campaign_edit.php'), get_string('commerce_personal_offer_new_campaign', 'local_subscriptions'), ['class' => 'btn btn-primary me-2']) .
        html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/personal-offers/create.php'), get_string('commerce_personal_offer_create_individual', 'local_subscriptions'), ['class' => 'btn btn-outline-primary']),
        'mb-3'
    );
}

$rows = CommercePersonalOfferCampaignManager::create($DB)->list_campaigns();
$table = new html_table();
$table->attributes['class'] = 'generaltable table table-hover align-middle';
$table->head = [
    get_string('name'),
    get_string('commerce_personal_offer_campaign_key', 'local_subscriptions'),
    get_string('commerce_personal_offer_audience', 'local_subscriptions'),
    get_string('status'),
    get_string('modified'),
];
foreach ($rows as $campaign) {
    $audiencekey = $campaign->audiencetype === 'criteria'
        ? 'commerce_personal_offer_audience_criteria'
        : 'commerce_personal_offer_audience_list';
    $table->data[] = [
        html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/personal-offers/campaign_view.php', ['id' => $campaign->id]), s($campaign->name)),
        html_writer::tag('code', s($campaign->campaignkey)),
        get_string($audiencekey, 'local_subscriptions'),
        get_string_manager()->string_exists(
            'commerce_personal_offer_campaign_status_' . (string)$campaign->status,
            'local_subscriptions'
        )
            ? get_string(
                'commerce_personal_offer_campaign_status_' . (string)$campaign->status,
                'local_subscriptions'
            )
            : s((string)$campaign->status),
        userdate($campaign->timemodified),
    ];
}
echo $rows ? html_writer::table($table) : html_writer::div(get_string('commerce_personal_offer_campaigns_empty', 'local_subscriptions'), 'alert alert-light border');
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
