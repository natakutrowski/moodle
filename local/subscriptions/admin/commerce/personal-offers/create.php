<?php
require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\personaloffer\admin\CommercePersonalOfferCrmInput;
use local_subscriptions\commerce\personaloffer\admin\CommercePersonalOfferCrmPresentation;
use local_subscriptions\commerce\order\reference\CommercePublicOrderReference;
use local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignManager;
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOfferTerms;
use local_subscriptions\commerce\personaloffer\mail\CommercePersonalOfferMailImageService;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_CRM_ADMIN_TOOLS);
$url = new moodle_url('/local/subscriptions/admin/commerce/personal-offers/create.php');
$title = get_string('commerce_personal_offer_create_individual', 'local_subscriptions');
CrmPageConfigurator::configure($PAGE, $context, $url, $title, 'local-subscriptions-commerce-personal-offer-create-page');

$products = $DB->get_records('local_subs_commerce_product', [], 'name ASC', 'id,sku,name,status');
$currencies = array_values(array_unique(array_map(
    static fn($r): string => strtoupper((string)$r->currency),
    array_values($DB->get_records_sql("SELECT DISTINCT currency FROM {local_subs_commerce_prod_price} WHERE active = 1 ORDER BY currency"))
)));
if ($currencies === []) { $currencies = ['EUR', 'RUB']; }

$campaigns = $DB->get_records('local_subs_commerce_offer_campaign', [], 'name ASC', 'id,campaignkey,name');
$emailrecords = $DB->get_records_sql(
    "SELECT id, firstname, lastname, email
       FROM {user}
      WHERE deleted = 0 AND email <> ''
   ORDER BY lastname, firstname, email",
    [],
    0,
    1000
);
$purchases = $DB->get_records_sql(
    "SELECT id, reference, customeremail, timecreated
       FROM {local_subscriptions_commerce_purchase}
   ORDER BY timecreated DESC",
    [],
    0,
    300
);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    try {
        $amountvalues = [];
        foreach ($currencies as $currency) {
            $amountvalues[$currency] = optional_param('amount_' . strtolower($currency), '', PARAM_RAW_TRIMMED);
        }
        $terms = CommercePersonalOfferCrmInput::terms(
            required_param('strategy', PARAM_ALPHANUMEXT),
            CommercePersonalOfferCrmInput::amounts_from_major($amountvalues),
            optional_param('percent', 0, PARAM_INT)
        );
        $email = CommercePersonalOfferCrmInput::resolve_beneficiary_email(
            $DB,
            required_param('email', PARAM_RAW_TRIMMED)
        );
        $u = $DB->get_record('user', ['email' => $email, 'deleted' => 0], 'id,email', IGNORE_MULTIPLE);
        $sourcemode = optional_param('sourcemode', 'none', PARAM_ALPHANUMEXT);
        $sourcepurchaseid = null;
        if ($sourcemode === 'purchase') {
            $sourcepurchaseid = CommercePersonalOfferCrmInput::resolve_purchase_id(
                $DB,
                optional_param('sourcepurchase', '', PARAM_RAW_TRIMMED)
            );
            if ($sourcepurchaseid === null) { throw new coding_exception('Unable to resolve the selected source purchase.'); }
        } else if ($sourcemode === 'product') {
            $ownership = CommercePersonalOfferCrmInput::resolve_product_ownership(
                $DB, $email, $u ? (int)$u->id : null, required_param('sourceproductid', PARAM_INT)
            );
            if (!$ownership['owned']) {
                throw new coding_exception('The beneficiary does not own the selected source product.');
            }
            $sourcepurchaseid = $ownership['sourcepurchaseid'];
            $ownershipsource = $ownership['source'];
            $ownershipproductid = $ownership['productid'] ?? null;
            $ownershipproductsku = $ownership['productsku'] ?? null;
        }
        $campaignkey = optional_param('campaignkey', '', PARAM_TEXT);
        $res = CommercePersonalOfferCampaignManager::create($DB)->issue_individual([
            'email' => $email,
            'beneficiaryuserid' => $u ? (int)$u->id : null,
            'sourcepurchaseid' => $sourcepurchaseid,
            'targetproductid' => required_param('targetproductid', PARAM_INT),
            'eligibilitymode' => $sourcemode === 'purchase' ? 'source_purchase' : ($sourcemode === 'product' ? 'product_ownership' : 'standalone'),
            'ownershipsource' => $ownershipsource ?? null,
            'ownershipproductid' => $ownershipproductid ?? null,
            'ownershipproductsku' => $ownershipproductsku ?? null,
            'campaignkey' => $campaignkey !== '' ? $campaignkey : 'crm-individual',
            'terms' => $terms->get_data(),
            'validfrom' => CommercePersonalOfferCrmInput::timestamp(optional_param('validfrom', '', PARAM_RAW_TRIMMED)),
            'expiresat' => CommercePersonalOfferCrmInput::timestamp(optional_param('expiresat', '', PARAM_RAW_TRIMMED), true),
        ], (int)$USER->id);

        $offerid = (int)$res['offer']->get_id();
        if (!empty($_FILES['mailimage']['tmp_name'])) {
            (new CommercePersonalOfferMailImageService())->save_uploaded_file(
                $offerid,
                (array)$_FILES['mailimage']
            );
        }

        redirect(new moodle_url('/local/subscriptions/admin/commerce/personal-offers/view.php', ['id' => $offerid]));
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('commerce_personal_offers_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/personal-offers/index.php')],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render($title, get_string('commerce_personal_offer_create_individual_help', 'local_subscriptions'), HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::PERSONAL_OFFERS, $context);
if ($error !== '') { echo html_writer::div(s($error), 'alert alert-danger'); }

echo html_writer::start_tag('form', ['method' => 'post', 'enctype' => 'multipart/form-data', 'class' => 'card card-body']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

echo html_writer::start_div('mb-4');
echo html_writer::tag('label', get_string('commerce_personal_offer_email', 'local_subscriptions'), ['for' => 'offer-email', 'class' => 'form-label fw-semibold']);
echo html_writer::empty_tag('input', ['id' => 'offer-email', 'name' => 'email', 'type' => 'text', 'list' => 'offer-email-list', 'class' => 'form-control', 'autocomplete' => 'off', 'required' => 'required']);
echo html_writer::start_tag('datalist', ['id' => 'offer-email-list']);
foreach ($emailrecords as $record) {
    $fullname = trim((string)$record->firstname . ' ' . (string)$record->lastname);
    $value = $fullname !== '' ? $fullname . ' <' . $record->email . '>' : $record->email;
    echo html_writer::tag('option', '', ['value' => $value]);
}
echo html_writer::end_tag('datalist');
echo html_writer::div(get_string('commerce_personal_offer_email_help', 'local_subscriptions'), 'form-text');
echo html_writer::end_div();

echo html_writer::start_div('mb-4');
echo html_writer::tag('label', get_string('commerce_personal_offer_campaign_optional', 'local_subscriptions'), ['for' => 'campaignkey', 'class' => 'form-label fw-semibold']);
$campaignopts = ['' => get_string('commerce_personal_offer_campaign_none', 'local_subscriptions')];
foreach ($campaigns as $campaign) { $campaignopts[$campaign->campaignkey] = $campaign->name . ' [' . $campaign->campaignkey . ']'; }
echo html_writer::select($campaignopts, 'campaignkey', '', false, ['id' => 'campaignkey', 'class' => 'form-select']);
echo html_writer::div(get_string('commerce_personal_offer_campaign_optional_help', 'local_subscriptions'), 'form-text');
echo html_writer::end_div();

echo html_writer::start_div('mb-4');
echo html_writer::tag('label', get_string('commerce_personal_offer_source_basis', 'local_subscriptions'), ['for' => 'sourcemode', 'class' => 'form-label fw-semibold']);
echo html_writer::select([
    'none' => get_string('commerce_personal_offer_source_none', 'local_subscriptions'),
    'product' => get_string('commerce_personal_offer_source_product', 'local_subscriptions'),
    'purchase' => get_string('commerce_personal_offer_source_purchase_mode', 'local_subscriptions'),
], 'sourcemode', 'none', false, ['id' => 'sourcemode', 'class' => 'form-select mb-2']);
echo html_writer::div(get_string('commerce_personal_offer_source_basis_help', 'local_subscriptions'), 'form-text mb-3');

echo html_writer::tag('label', get_string('commerce_personal_offer_source_product', 'local_subscriptions'), ['for' => 'sourceproductid', 'class' => 'form-label']);
$sourceproductopts = ['' => get_string('choose')];
foreach ($products as $product) { $sourceproductopts[$product->id] = CommercePersonalOfferCrmPresentation::product_label($DB, (int)$product->id); }
echo html_writer::select($sourceproductopts, 'sourceproductid', '', false, ['id' => 'sourceproductid', 'class' => 'form-select mb-3']);

echo html_writer::tag('label', get_string('commerce_personal_offer_source_purchase_optional', 'local_subscriptions'), ['for' => 'sourcepurchase', 'class' => 'form-label']);
echo html_writer::empty_tag('input', ['id' => 'sourcepurchase', 'name' => 'sourcepurchase', 'list' => 'sourcepurchase-list', 'class' => 'form-control', 'autocomplete' => 'off', 'placeholder' => get_string('commerce_personal_offer_source_purchase_placeholder', 'local_subscriptions')]);
echo html_writer::start_tag('datalist', ['id' => 'sourcepurchase-list']);
$publicreferences = new CommercePublicOrderReference();
foreach ($purchases as $purchase) {
    $public = $publicreferences->from_internal((string)$purchase->reference, (int)$purchase->timecreated);
    $label = $public . ($purchase->customeremail ? ' — ' . $purchase->customeremail : '') . ' [' . $purchase->reference . ']';
    echo html_writer::tag('option', $label, ['value' => $purchase->reference]);
}
echo html_writer::end_tag('datalist');
echo html_writer::div(get_string('commerce_personal_offer_source_purchase_help', 'local_subscriptions'), 'form-text');
echo html_writer::end_div();

echo html_writer::start_div('mb-4');
echo html_writer::tag('label', get_string('commerce_personal_offer_target', 'local_subscriptions'), ['for' => 'targetproductid', 'class' => 'form-label fw-semibold']);
$productopts = [];
foreach ($products as $product) { $productopts[$product->id] = CommercePersonalOfferCrmPresentation::product_label($DB, (int)$product->id); }
echo html_writer::select($productopts, 'targetproductid', '', false, ['id' => 'targetproductid', 'class' => 'form-select', 'required' => 'required']);
echo html_writer::div(get_string('commerce_personal_offer_target_help', 'local_subscriptions'), 'form-text');
echo html_writer::end_div();

echo html_writer::start_div('mb-4');
echo html_writer::tag('label', get_string('commerce_personal_offer_pricing', 'local_subscriptions'), ['for' => 'strategy', 'class' => 'form-label fw-semibold']);
echo html_writer::select([
    CommercePersonalOfferTerms::STRATEGY_FIXED_PRICE => get_string('commerce_personal_offer_strategy_fixed_price', 'local_subscriptions'),
    CommercePersonalOfferTerms::STRATEGY_FIXED_DISCOUNT => get_string('commerce_personal_offer_strategy_fixed_discount', 'local_subscriptions'),
    CommercePersonalOfferTerms::STRATEGY_PERCENTAGE_DISCOUNT => get_string('commerce_personal_offer_strategy_percentage_discount', 'local_subscriptions'),
], 'strategy', CommercePersonalOfferTerms::STRATEGY_FIXED_PRICE, false, ['id' => 'strategy', 'class' => 'form-select']);
echo html_writer::div(get_string('commerce_personal_offer_pricing_help', 'local_subscriptions'), 'form-text');
echo html_writer::end_div();

echo html_writer::tag('h3', get_string('commerce_personal_offer_amounts_display_title', 'local_subscriptions'), ['class' => 'h6 mt-2']);
echo html_writer::div(get_string('commerce_personal_offer_amounts_display_help', 'local_subscriptions'), 'text-muted small mb-3');
echo html_writer::start_div('row g-3 mb-4');
foreach ($currencies as $currency) {
    echo html_writer::start_div('col-12 col-md-6');
    echo html_writer::tag('label', $currency . ($currency === 'EUR' ? ' (€)' : ($currency === 'RUB' ? ' (₽)' : '')), ['for' => 'amount-' . strtolower($currency), 'class' => 'form-label']);
    echo html_writer::empty_tag('input', ['id' => 'amount-' . strtolower($currency), 'name' => 'amount_' . strtolower($currency), 'type' => 'number', 'min' => '0', 'step' => '0.01', 'class' => 'form-control', 'placeholder' => $currency === 'EUR' ? '30.00' : ($currency === 'RUB' ? '2990.00' : '')]);
    echo html_writer::end_div();
}
echo html_writer::start_div('col-12 col-md-6');
echo html_writer::tag('label', get_string('commerce_personal_offer_percent', 'local_subscriptions'), ['for' => 'percent', 'class' => 'form-label']);
echo html_writer::empty_tag('input', ['id' => 'percent', 'name' => 'percent', 'type' => 'number', 'min' => '1', 'max' => '100', 'value' => '20', 'class' => 'form-control']);
echo html_writer::end_div();
echo html_writer::end_div();


echo html_writer::start_div('mb-4');
echo html_writer::tag(
    'label',
    get_string('commerce_personal_offer_mail_image', 'local_subscriptions'),
    ['for' => 'mailimage', 'class' => 'form-label fw-semibold']
);
echo html_writer::empty_tag('input', [
    'id' => 'mailimage',
    'name' => 'mailimage',
    'type' => 'file',
    'accept' => 'image/jpeg,image/png,image/webp',
    'class' => 'form-control',
]);
echo html_writer::div(
    get_string('commerce_personal_offer_mail_image_help', 'local_subscriptions'),
    'form-text'
);
echo html_writer::end_div();

echo html_writer::start_div('row g-3 mb-4');
foreach ([['validfrom', 'commerce_personal_offer_valid_from', 'commerce_personal_offer_valid_from_help'], ['expiresat', 'commerce_personal_offer_expires_at', 'commerce_personal_offer_expires_at_help']] as [$name, $labelkey, $helpkey]) {
    echo html_writer::start_div('col-12 col-md-6');
    echo html_writer::tag('label', get_string($labelkey, 'local_subscriptions'), ['for' => $name, 'class' => 'form-label fw-semibold']);
    echo html_writer::empty_tag('input', ['id' => $name, 'name' => $name, 'type' => 'date', 'class' => 'form-control']);
    echo html_writer::div(get_string($helpkey, 'local_subscriptions'), 'form-text');
    echo html_writer::end_div();
}
echo html_writer::end_div();

echo html_writer::div(
    html_writer::tag('button', get_string('commerce_personal_offer_create', 'local_subscriptions'), ['type' => 'submit', 'class' => 'btn btn-primary']) .
    html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/personal-offers/index.php'), get_string('cancel'), ['class' => 'btn btn-outline-secondary ms-2']),
    'd-flex gap-2'
);
echo html_writer::end_tag('form');
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
