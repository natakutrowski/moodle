<?php
require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\mail\admin\CommerceMailAdminPresentation;
use local_subscriptions\commerce\mail\admin\CommerceMailPreviewRenderer;
use local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignManager;
use local_subscriptions\commerce\personaloffer\mail\CommercePersonalOfferCampaignMailPreviewService;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::VIEW_PAYMENTS);
require_capability(Capabilities::MANAGE_CRM_ADMIN_TOOLS, $context);
$id = required_param('id', PARAM_INT);
$language = optional_param('language', 'fr', PARAM_ALPHA);
$view = optional_param('view', CommerceMailPreviewRenderer::DESKTOP, PARAM_ALPHA);
$font = optional_param('font', CommerceMailPreviewRenderer::FONT_BRAND, PARAM_ALPHA);
$firstname = optional_param('firstname', 'Natalia', PARAM_TEXT);
$campaign = CommercePersonalOfferCampaignManager::create($DB)->get_campaign($id);
$url = new moodle_url('/local/subscriptions/admin/commerce/personal-offers/campaign_email_preview.php', ['id'=>$id,'language'=>$language,'view'=>$view,'font'=>$font]);
CrmPageConfigurator::configure($PAGE, $context, $url, get_string('commerce_personal_offer_campaign_email_preview', 'local_subscriptions'), 'local-subscriptions-personal-offer-campaign-email-preview');
$PAGE->requires->css('/local/subscriptions/styles/commerce_mail_admin.css');

$service = CommercePersonalOfferCampaignMailPreviewService::create($DB);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    $testemail = required_param('testemail', PARAM_EMAIL);
    try {
        $service->send_test($id, $language, $testemail, $firstname);
        redirect($url, get_string('commerce_personal_offer_campaign_email_test_sent', 'local_subscriptions', $testemail), null, \core\output\notification::NOTIFY_SUCCESS);
    } catch (Throwable $e) {
        redirect($url, $e->getMessage(), null, \core\output\notification::NOTIFY_ERROR);
    }
}
$message = $service->preview($id, $language, $firstname);
$back = new moodle_url('/local/subscriptions/admin/commerce/personal-offers/campaign_view.php', ['id'=>$id]);

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label'=>get_string('commerce_personal_offer_campaigns','local_subscriptions'),'url'=>new moodle_url('/local/subscriptions/admin/commerce/personal-offers/campaigns.php')],
    ['label'=>(string)$campaign->name,'url'=>$back],
    ['label'=>get_string('commerce_personal_offer_campaign_email_preview','local_subscriptions'),'url'=>null],
]);
echo CrmPageHeader::render(get_string('commerce_personal_offer_campaign_email_preview','local_subscriptions'), get_string('commerce_personal_offer_campaign_email_preview_help','local_subscriptions'), HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::PERSONAL_OFFERS, $context);

echo html_writer::div(html_writer::link($back, get_string('back'), ['class'=>'btn btn-outline-secondary']), 'mb-3');
echo html_writer::tag('h3', s($message->get_subject()), ['class'=>'h5 mb-3']);

$langopts=[]; foreach(['fr','en','ru'] as $code){$langopts[$code]=CommerceMailAdminPresentation::language_label($code);} 
$form=html_writer::start_tag('form',['method'=>'get','class'=>'d-flex gap-2 align-items-end flex-wrap mb-3']);
$form.=html_writer::empty_tag('input',['type'=>'hidden','name'=>'id','value'=>$id]);
$form.=html_writer::empty_tag('input',['type'=>'hidden','name'=>'view','value'=>$view]);
$form.=html_writer::empty_tag('input',['type'=>'hidden','name'=>'font','value'=>$font]);
$form.=html_writer::select($langopts,'language',$language,false,['class'=>'form-select form-select-sm']);
$form.=html_writer::empty_tag('input',['type'=>'text','name'=>'firstname','value'=>$firstname,'class'=>'form-control form-control-sm','placeholder'=>'Natalia']);
$form.=html_writer::tag('button',get_string('commerce_personal_offer_campaign_email_preview_refresh','local_subscriptions'),['class'=>'btn btn-sm btn-outline-primary','type'=>'submit']);
$form.=html_writer::end_tag('form'); echo $form;

$nav = CommerceMailPreviewRenderer::render_navigation($url, $view);
$fontnav = in_array($view, [CommerceMailPreviewRenderer::DESKTOP, CommerceMailPreviewRenderer::MOBILE], true)
    ? CommerceMailPreviewRenderer::render_font_navigation($url, $font)
    : '';
echo html_writer::start_div('commerce-mail-preview-toolbar mb-3');
echo html_writer::div($nav, 'commerce-mail-preview-toolbar__navigation');
if ($fontnav !== '') {
    echo html_writer::div($fontnav, 'commerce-mail-preview-toolbar__font');
}
echo html_writer::end_div();
echo html_writer::div(
    CommerceMailPreviewRenderer::render($message->get_html(), $message->get_text(), $view, $font),
    'commerce-personal-offer-campaign-preview__canvas'
);

$test=html_writer::start_tag('form',['method'=>'post','class'=>'card card-body mt-4']);
$test.=html_writer::empty_tag('input',['type'=>'hidden','name'=>'sesskey','value'=>sesskey()]);
$test.=html_writer::empty_tag('input',['type'=>'email','name'=>'testemail','required'=>'required','class'=>'form-control mb-2','placeholder'=>'nata@example.com']);
$test.=html_writer::tag('button',get_string('commerce_personal_offer_campaign_email_test_send','local_subscriptions'),['type'=>'submit','class'=>'btn btn-dark']);
$test.=html_writer::end_tag('form'); echo $test;

echo CrmWorkspaceRenderer::end(); echo $OUTPUT->footer();
