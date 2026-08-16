<?php
require_once(__DIR__ . '/../../../../../config.php');
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\personaloffer\admin\CommercePersonalOfferAdminService;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceOffersAccessNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
$context = AdminSecurity::require(Capabilities::VIEW_PAYMENTS);
$url = new moodle_url('/local/subscriptions/admin/commerce/personal-offers/stats.php');
CrmPageConfigurator::configure($PAGE, $context, $url, get_string('commerce_personal_offer_stats_title', 'local_subscriptions'), 'local-subscriptions-commerce-personal-offer-stats');
$service = new CommercePersonalOfferAdminService($DB); $stats = $service->global_stats();
echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('commerce_offers_access_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/offers-access/index.php')],
    ['label' => get_string('commerce_personal_offers_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/personal-offers/index.php')],
    ['label' => get_string('commerce_personal_offer_stats_title', 'local_subscriptions'), 'url' => null],
]);
echo CrmPageHeader::render(get_string('commerce_personal_offer_stats_title', 'local_subscriptions'), get_string('commerce_personal_offer_stats_help', 'local_subscriptions'), HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::OFFERS_ACCESS,
    $context
);
echo CommerceOffersAccessNavigationRenderer::render(
    CommerceOffersAccessNavigationRenderer::OFFERS
);
$table = new html_table(); $table->head = [get_string('status'), get_string('count')]; foreach ($stats as $key=>$count) {$table->data[]=[s($key),(int)$count];} echo html_writer::table($table);
echo $OUTPUT->heading(get_string('commerce_personal_offer_campaign_stats', 'local_subscriptions'), 3); $c = new html_table(); $c->head=[get_string('commerce_personal_offer_campaign','local_subscriptions'),get_string('total'),get_string('commerce_personal_offer_status_redeemed','local_subscriptions'),get_string('commerce_personal_offer_status_revoked','local_subscriptions')]; foreach($service->campaign_stats() as $row){$c->data[]=[s($row->campaignkey ?: '—'),(int)$row->total,(int)$row->redeemed,(int)$row->revoked];} echo html_writer::table($c); echo CrmWorkspaceRenderer::end(); echo $OUTPUT->footer();
