<?php
require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\personaloffer\admin\CommercePersonalOfferAdminService;
use local_subscriptions\commerce\personaloffer\admin\CommercePersonalOfferCrmInput;
use local_subscriptions\commerce\personaloffer\admin\CommercePersonalOfferCrmPresentation;
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOfferTerms;
use local_subscriptions\commerce\personaloffer\mail\CommercePersonalOfferMailImageService;
use local_subscriptions\commerce\personaloffer\repository\MoodleCommercePersonalOfferRepository;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceOffersAccessNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_CRM_ADMIN_TOOLS);
$id = required_param('id', PARAM_INT);
$repo = new MoodleCommercePersonalOfferRepository($DB);
$offer = $repo->get_by_id($id) ?? throw new moodle_exception('commerce_personal_offer_not_found', 'local_subscriptions');
if ($offer->get_effective_status(time()) !== \local_subscriptions\commerce\personaloffer\domain\CommercePersonalOffer::STATUS_ISSUED) {
    throw new moodle_exception('commerce_personal_offer_edit_not_allowed', 'local_subscriptions');
}
$url = new moodle_url('/local/subscriptions/admin/commerce/personal-offers/edit.php', ['id' => $id]);
$title = get_string('commerce_personal_offer_edit', 'local_subscriptions');
CrmPageConfigurator::configure($PAGE, $context, $url, $title, 'local-subscriptions-commerce-personal-offer-edit');
$products = $DB->get_records('local_subs_commerce_product', [], 'name ASC', 'id,sku,name,status');
$campaigns = $DB->get_records('local_subs_commerce_offer_campaign', [], 'name ASC', 'id,campaignkey,name');
$currencies = ['EUR','RUB'];
$pricing = $offer->get_terms()->get_data()['pricing'] ?? [];
$strategy = (string)($pricing['strategy'] ?? CommercePersonalOfferTerms::STRATEGY_FIXED_PRICE);
$amounts = (array)($pricing['amounts'] ?? []);
$percent = isset($pricing['basispoints']) ? ((int)$pricing['basispoints'] / 100) : 20;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    try {
        $major = [];
        foreach ($currencies as $currency) { $major[$currency] = optional_param('amount_' . strtolower($currency), '', PARAM_RAW_TRIMMED); }
        $terms = CommercePersonalOfferCrmInput::terms(required_param('strategy', PARAM_ALPHANUMEXT), CommercePersonalOfferCrmInput::amounts_from_major($major), optional_param('percent', 0, PARAM_INT));
        $campaignkey = optional_param('campaignkey', '', PARAM_TEXT);
        $new = (new CommercePersonalOfferAdminService($DB))->replace(
            $offer,
            required_param('targetproductid', PARAM_INT),
            $terms,
            $campaignkey !== '' ? $campaignkey : null,
            CommercePersonalOfferCrmInput::timestamp(optional_param('validfrom', '', PARAM_RAW_TRIMMED)),
            CommercePersonalOfferCrmInput::timestamp(optional_param('expiresat', '', PARAM_RAW_TRIMMED), true),
            (int)$USER->id
        );

        $mailimages = new CommercePersonalOfferMailImageService();
        $newid = (int)$new->get_id();
        if (!empty($_FILES['mailimage']['tmp_name'])) {
            $mailimages->save_uploaded_file($newid, (array)$_FILES['mailimage']);
        } else {
            $mailimages->copy((int)$offer->get_id(), $newid);
        }

        redirect(new moodle_url('/local/subscriptions/admin/commerce/personal-offers/view.php', ['id' => $newid]), get_string('commerce_personal_offer_replaced_success', 'local_subscriptions'));
    } catch (Throwable $e) { $error = $e->getMessage(); }
}

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('commerce_offers_access_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/offers-access/index.php')],
    ['label' => get_string('commerce_personal_offers_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/personal-offers/index.php')],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render($title, get_string('commerce_personal_offer_edit_help', 'local_subscriptions'), HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::OFFERS_ACCESS,
    $context
);
echo CommerceOffersAccessNavigationRenderer::render(
    CommerceOffersAccessNavigationRenderer::OFFERS
);
echo html_writer::div(get_string('commerce_personal_offer_edit_replace_notice', 'local_subscriptions'), 'alert alert-info');
if ($error !== '') { echo html_writer::div(s($error), 'alert alert-danger'); }

echo html_writer::start_tag('form', ['method' => 'post', 'enctype' => 'multipart/form-data', 'class' => 'card card-body']);
echo html_writer::empty_tag('input', ['type'=>'hidden','name'=>'sesskey','value'=>sesskey()]);
echo html_writer::div('<strong>' . s($offer->get_beneficiary_email()) . '</strong>', 'mb-3');

$campaignopts=[''=>get_string('commerce_personal_offer_campaign_none','local_subscriptions')];
foreach($campaigns as $c){$campaignopts[$c->campaignkey]=$c->name.' ['.$c->campaignkey.']';}
echo html_writer::tag('label', get_string('commerce_personal_offer_campaign','local_subscriptions'), ['class'=>'form-label']);
echo html_writer::select($campaignopts,'campaignkey',$offer->get_campaign_key()??'',false,['class'=>'form-select mb-3']);

$productopts=[]; foreach($products as $product){$productopts[$product->id]=CommercePersonalOfferCrmPresentation::business_product_label($DB,(int)$product->id);}
echo html_writer::tag('label', get_string('commerce_personal_offer_target','local_subscriptions'), ['class'=>'form-label']);
echo html_writer::select($productopts,'targetproductid',$offer->get_target_product_id(),false,['class'=>'form-select mb-3']);

echo html_writer::tag('label', get_string('commerce_personal_offer_pricing','local_subscriptions'), ['class'=>'form-label']);
echo html_writer::select([
    CommercePersonalOfferTerms::STRATEGY_FIXED_PRICE=>get_string('commerce_personal_offer_strategy_fixed_price','local_subscriptions'),
    CommercePersonalOfferTerms::STRATEGY_FIXED_DISCOUNT=>get_string('commerce_personal_offer_strategy_fixed_discount','local_subscriptions'),
    CommercePersonalOfferTerms::STRATEGY_PERCENTAGE_DISCOUNT=>get_string('commerce_personal_offer_strategy_percentage_discount','local_subscriptions'),
],'strategy',$strategy,false,['class'=>'form-select mb-3']);

echo html_writer::start_div('row g-3 mb-3');
foreach($currencies as $currency){
    $value=array_key_exists($currency,$amounts)?format_float(((int)$amounts[$currency])/100,2,false):'';
    echo html_writer::start_div('col-md-6');
    echo html_writer::tag('label',$currency.($currency==='EUR'?' (€)':' (₽)'),['class'=>'form-label']);
    echo html_writer::empty_tag('input',['name'=>'amount_'.strtolower($currency),'type'=>'number','step'=>'0.01','min'=>'0','value'=>$value,'class'=>'form-control']);
    echo html_writer::end_div();
}
echo html_writer::start_div('col-md-6'); echo html_writer::tag('label',get_string('commerce_personal_offer_percent','local_subscriptions'),['class'=>'form-label']); echo html_writer::empty_tag('input',['name'=>'percent','type'=>'number','min'=>'1','max'=>'100','value'=>$percent,'class'=>'form-control']); echo html_writer::end_div();
echo html_writer::end_div();


$mailimageurl = (new CommercePersonalOfferMailImageService())->url((int)$offer->get_id());
echo html_writer::start_div('mb-4');
echo html_writer::tag(
    'label',
    get_string('commerce_personal_offer_mail_image', 'local_subscriptions'),
    ['for' => 'mailimage', 'class' => 'form-label fw-semibold']
);
if ($mailimageurl !== null) {
    echo html_writer::empty_tag('img', [
        'src' => $mailimageurl->out(false),
        'alt' => '',
        'style' => 'display:block;width:220px;max-width:100%;height:auto;border-radius:12px;margin-bottom:.75rem;',
    ]);
}
echo html_writer::empty_tag('input', [
    'id' => 'mailimage',
    'name' => 'mailimage',
    'type' => 'file',
    'accept' => 'image/jpeg,image/png,image/webp',
    'class' => 'form-control',
]);
echo html_writer::div(
    get_string('commerce_personal_offer_mail_image_edit_help', 'local_subscriptions'),
    'form-text'
);
echo html_writer::end_div();

echo html_writer::start_div('row g-3 mb-4');
foreach ([['validfrom','commerce_personal_offer_valid_from',$offer->get_valid_from()],['expiresat','commerce_personal_offer_expires_at',$offer->get_expires_at()]] as [$name,$key,$ts]) {
    echo html_writer::start_div('col-md-6'); echo html_writer::tag('label',get_string($key,'local_subscriptions'),['class'=>'form-label']); echo html_writer::empty_tag('input',['name'=>$name,'type'=>'date','value'=>$ts?gmdate('Y-m-d',$ts):'','class'=>'form-control']); echo html_writer::end_div();
}
echo html_writer::end_div();
echo html_writer::tag('button',get_string('savechanges'),['type'=>'submit','class'=>'btn btn-primary']);
echo ' '.html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/personal-offers/view.php',['id'=>$id]),get_string('cancel'),['class'=>'btn btn-outline-secondary']);
echo html_writer::end_tag('form');
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
