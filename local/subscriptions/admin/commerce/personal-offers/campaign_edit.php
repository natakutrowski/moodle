<?php
require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\personaloffer\admin\CommercePersonalOfferCrmInput;
use local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignManager;
use local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignValidityService;
use local_subscriptions\commerce\personaloffer\domain\CommercePersonalOfferTerms;
use local_subscriptions\commerce\catalog\presentation\CommerceProductDisplayNameResolver;
use local_subscriptions\commerce\personaloffer\audience\CommercePersonalOfferLegacyPlanAudienceProvider;
use local_subscriptions\commerce\personaloffer\audience\CommercePersonalOfferLegacyDigitalAudienceProvider;
use local_subscriptions\commerce\personaloffer\audience\CommercePersonalOfferNativeProductAudienceProvider;
use local_subscriptions\subscription_manager;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_CRM_ADMIN_TOOLS);
$url = new moodle_url('/local/subscriptions/admin/commerce/personal-offers/campaign_edit.php');
$title = get_string('commerce_personal_offer_new_campaign', 'local_subscriptions');
CrmPageConfigurator::configure($PAGE, $context, $url, $title, 'local-subscriptions-commerce-personal-offer-campaign-edit-page');

$products = $DB->get_records(
    'local_subs_commerce_product',
    [],
    'name ASC',
    'id,sku,name,status,type'
);
$productresolver = CommerceProductDisplayNameResolver::create($DB);

$legacyplans = $DB->get_records('subscription_plan', [], 'name ASC', 'id,name,is_active,duration_key');
$legacydigitals = $DB->get_records(
    'subscription_digital_product',
    [],
    'name ASC',
    'id,name,slug,enabled'
);

$currencies = array_values(array_unique(array_map(
    static fn($r): string => strtoupper((string)$r->currency),
    array_values($DB->get_records_sql("SELECT DISTINCT currency FROM {local_subs_commerce_prod_price} WHERE active = 1 ORDER BY currency"))
)));
if ($currencies === []) { $currencies = ['EUR', 'RUB']; }
$emailrecords = $DB->get_records_sql(
    "SELECT DISTINCT LOWER(email) AS email
       FROM {user}
      WHERE deleted = 0 AND email <> ''
   ORDER BY email",
    [],
    0,
    500
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
        $type = required_param('audiencetype', PARAM_ALPHA);
        $name = required_param('name', PARAM_TEXT);
        $campaignkey = trim(optional_param('campaignkey', '', PARAM_TEXT));
        $campaignkey = trim(optional_param('campaignkey', '', PARAM_TEXT));

        if ($campaignkey === '') {
            $ascii = core_text::specialtoascii($name);
            $slug = strtolower(trim(
                preg_replace('/[^a-z0-9]+/i', '-', $ascii),
                '-'
            ));

            $campaignkey = substr(
                ($slug !== '' ? $slug : 'campaign')
                . '-'
                . userdate(time(), '%Y%m%d-%H%M%S'),
                0,
                100
            );
        }
        $sourcetype = optional_param(
            'source_type',
            CommercePersonalOfferNativeProductAudienceProvider::TYPE,
            PARAM_ALPHAEXT
        );
        $sourceid = match ($sourcetype) {
            CommercePersonalOfferLegacyPlanAudienceProvider::TYPE =>
                optional_param('source_legacy_plan_id', 0, PARAM_INT),
            CommercePersonalOfferLegacyDigitalAudienceProvider::TYPE =>
                optional_param('source_legacy_digital_id', 0, PARAM_INT),
            CommercePersonalOfferNativeProductAudienceProvider::TYPE =>
                optional_param('source_native_product_id', 0, PARAM_INT),
            default => 0,
        };

        $criteria = [
            'excludeowned' => optional_param('excludeowned', 1, PARAM_BOOL),
            'account' => optional_param('account', 'all', PARAM_ALPHA),
            'from' => CommercePersonalOfferCrmInput::timestamp(optional_param('purchasefrom', '', PARAM_RAW_TRIMMED)),
            'to' => CommercePersonalOfferCrmInput::timestamp(optional_param('purchaseto', '', PARAM_RAW_TRIMMED), true),
            'list' => optional_param('audiencelist', '', PARAM_RAW),
            'sourcetype' => $sourcetype,
            'sourceid' => $sourceid,
        ];

        $sourceproductsku = '';
        if (
            $type === CommercePersonalOfferCampaignManager::AUDIENCE_CRITERIA
            && $sourcetype === CommercePersonalOfferNativeProductAudienceProvider::TYPE
            && $sourceid > 0
            && isset($products[$sourceid])
        ) {
            $sourceproductsku = (string)$products[$sourceid]->sku;
        }

        $validitymode = CommercePersonalOfferCampaignValidityService::normalise_mode(
            optional_param('validitymode', CommercePersonalOfferCampaignValidityService::MODE_FIXED, PARAM_ALPHANUMEXT)
        );
        $validitytimezone = CommercePersonalOfferCampaignValidityService::normalise_timezone(
            optional_param('validitytimezone', CommercePersonalOfferCampaignValidityService::DEFAULT_TIMEZONE, PARAM_RAW_TRIMMED)
        );
        $validfrom = null;
        $expiresat = null;
        $validityduration = null;
        if ($validitymode === CommercePersonalOfferCampaignValidityService::MODE_FIXED) {
            $validfrom = CommercePersonalOfferCrmInput::datetime_local(
                optional_param('validfrom', '', PARAM_RAW_TRIMMED),
                $validitytimezone
            );
            $expiresat = CommercePersonalOfferCrmInput::datetime_local(
                required_param('expiresat', PARAM_RAW_TRIMMED),
                $validitytimezone
            );
            if ($validfrom !== null && $expiresat <= $validfrom) {
                throw new coding_exception('Personal Offer campaign expiration must be after its start.');
            }
        } else if ($validitymode === CommercePersonalOfferCampaignValidityService::MODE_DURATION) {
            $validityduration = CommercePersonalOfferCampaignValidityService::duration_seconds(
                required_param('validitydurationvalue', PARAM_INT),
                required_param('validitydurationunit', PARAM_ALPHA)
            );
        }

        $id = CommercePersonalOfferCampaignManager::create($DB)->create_campaign([
            'campaignkey' => $campaignkey,
            'name' => $name,
            'audiencetype' => $type,
            'sourceproductsku' => $sourceproductsku,
            'targetproductid' => required_param('targetproductid', PARAM_INT),
            'termsversion' => $terms->get_version(),
            'terms' => $terms->get_data(),
            'criteria' => $criteria,
            'validfrom' => $validfrom,
            'expiresat' => $expiresat,
            'validitymode' => $validitymode,
            'validityduration' => $validityduration,
            'validitytimezone' => $validitytimezone,
        ], (int)$USER->id);
        redirect(new moodle_url('/local/subscriptions/admin/commerce/personal-offers/campaign_view.php', ['id' => $id]));
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$productopts = ['' => get_string('choosedots')];
$nativeopts = ['' => get_string('choosedots')];
foreach ($products as $product) {
    $displayname = $productresolver->resolve(
        [(string)$product->sku],
        current_language(),
        (string)$product->name
    );
    $label = $displayname . ' [' . $product->sku . ']';
    if ((string)$product->status !== 'active') {
        $label .= ' · ' . get_string('label_inactive', 'local_subscriptions');
    }
    $productopts[(int)$product->id] = $label;
    $nativeopts[(int)$product->id] = $label;
}

$legacyplanopts = ['' => get_string('choosedots')];
foreach ($legacyplans as $plan) {
    $translated = subscription_manager::get_translated_plan_name((int)$plan->id, current_language());
    $label = $translated ?: (string)$plan->name;
    if (empty($plan->is_active)) {
        $label .= ' · ' . get_string('label_inactive', 'local_subscriptions');
    }
    $legacyplanopts[(int)$plan->id] = $label;
}

$legacydigitalopts = ['' => get_string('choosedots')];
foreach ($legacydigitals as $product) {
    $translation = $DB->get_record(
        'subscription_digital_product_lang',
        ['productid' => (int)$product->id, 'lang' => current_language()],
        'title',
        IGNORE_MISSING
    );
    $label = $translation ? (string)$translation->title : (string)$product->name;
    if (empty($product->enabled)) {
        $label .= ' · ' . get_string('label_inactive', 'local_subscriptions');
    }
    $legacydigitalopts[(int)$product->id] = $label;
}

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('commerce_personal_offers_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/personal-offers/index.php')],
    ['label' => get_string('commerce_personal_offer_campaigns', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/personal-offers/campaigns.php')],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render($title, get_string('commerce_personal_offer_new_campaign_help', 'local_subscriptions'), HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::PERSONAL_OFFERS, $context);
if ($error !== '') { echo html_writer::div(s($error), 'alert alert-danger'); }

echo html_writer::start_tag('form', ['method' => 'post', 'class' => 'card card-body']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

echo html_writer::tag('h3', get_string('commerce_personal_offer_campaign_identity_title', 'local_subscriptions'), ['class' => 'h5']);
echo html_writer::start_div('row g-3 mb-4');
echo html_writer::start_div('col-12 col-lg-7');
echo html_writer::tag('label', get_string('name'), ['for' => 'name', 'class' => 'form-label fw-semibold']);
echo html_writer::empty_tag('input', ['id' => 'name', 'name' => 'name', 'class' => 'form-control', 'required' => 'required', 'placeholder' => get_string('commerce_personal_offer_campaign_name_placeholder', 'local_subscriptions')]);
echo html_writer::div(get_string('commerce_personal_offer_campaign_name_help', 'local_subscriptions'), 'form-text');
echo html_writer::end_div();
echo html_writer::start_div('col-12 col-lg-5');
echo html_writer::tag('label', get_string('commerce_personal_offer_campaign_key', 'local_subscriptions'), ['for' => 'campaignkey', 'class' => 'form-label fw-semibold']);
echo html_writer::empty_tag('input', ['id' => 'campaignkey', 'name' => 'campaignkey', 'class' => 'form-control', 'placeholder' => get_string('commerce_personal_offer_campaign_key_auto', 'local_subscriptions')]);
echo html_writer::div(get_string('commerce_personal_offer_campaign_key_help', 'local_subscriptions'), 'form-text');
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::tag('h3', get_string('commerce_personal_offer_audience_title', 'local_subscriptions'), ['class' => 'h5 mt-2']);
echo html_writer::start_div('mb-3');
echo html_writer::tag('label', get_string('commerce_personal_offer_audience', 'local_subscriptions'), ['for' => 'audiencetype', 'class' => 'form-label fw-semibold']);
echo html_writer::select([
    'criteria' => get_string('commerce_personal_offer_audience_criteria', 'local_subscriptions'),
    'list' => get_string('commerce_personal_offer_audience_list', 'local_subscriptions'),
], 'audiencetype', 'criteria', false, ['id' => 'audiencetype', 'class' => 'form-select']);
echo html_writer::div(get_string('commerce_personal_offer_audience_help', 'local_subscriptions'), 'form-text');
echo html_writer::end_div();

echo html_writer::start_div('', ['id' => 'criteria-source-block']);
echo html_writer::start_div('row g-3 mb-3');

echo html_writer::start_div('col-12 col-lg-4');
echo html_writer::tag(
    'label',
    get_string('commerce_personal_offer_source_type', 'local_subscriptions'),
    ['for' => 'source-type', 'class' => 'form-label fw-semibold']
);
echo html_writer::select([
    CommercePersonalOfferLegacyPlanAudienceProvider::TYPE =>
        get_string('commerce_personal_offer_source_legacy_plan', 'local_subscriptions'),
    CommercePersonalOfferLegacyDigitalAudienceProvider::TYPE =>
        get_string('commerce_personal_offer_source_legacy_digital', 'local_subscriptions'),
    CommercePersonalOfferNativeProductAudienceProvider::TYPE =>
        get_string('commerce_personal_offer_source_native_product', 'local_subscriptions'),
], 'source_type', CommercePersonalOfferLegacyPlanAudienceProvider::TYPE, false, [
    'id' => 'source-type',
    'class' => 'form-select',
]);
echo html_writer::div(
    get_string('commerce_personal_offer_source_type_help', 'local_subscriptions'),
    'form-text'
);
echo html_writer::end_div();

echo html_writer::start_div('col-12 col-lg-4', ['id' => 'source-legacy-plan-wrap']);
echo html_writer::tag(
    'label',
    get_string('commerce_personal_offer_source_legacy_plan', 'local_subscriptions'),
    ['for' => 'source-legacy-plan', 'class' => 'form-label fw-semibold']
);
echo html_writer::select(
    $legacyplanopts,
    'source_legacy_plan_id',
    '',
    false,
    ['id' => 'source-legacy-plan', 'class' => 'form-select']
);
echo html_writer::end_div();

echo html_writer::start_div('col-12 col-lg-4', [
    'id' => 'source-legacy-digital-wrap',
    'style' => 'display:none;',
]);
echo html_writer::tag(
    'label',
    get_string('commerce_personal_offer_source_legacy_digital', 'local_subscriptions'),
    ['for' => 'source-legacy-digital', 'class' => 'form-label fw-semibold']
);
echo html_writer::select(
    $legacydigitalopts,
    'source_legacy_digital_id',
    '',
    false,
    ['id' => 'source-legacy-digital', 'class' => 'form-select']
);
echo html_writer::end_div();

echo html_writer::start_div('col-12 col-lg-4', [
    'id' => 'source-native-product-wrap',
    'style' => 'display:none;',
]);
echo html_writer::tag(
    'label',
    get_string('commerce_personal_offer_source_native_product', 'local_subscriptions'),
    ['for' => 'source-native-product', 'class' => 'form-label fw-semibold']
);
echo html_writer::select(
    $nativeopts,
    'source_native_product_id',
    '',
    false,
    ['id' => 'source-native-product', 'class' => 'form-select']
);
echo html_writer::end_div();

echo html_writer::start_div('col-12 col-lg-4');
echo html_writer::tag('label', get_string('commerce_personal_offer_account_filter', 'local_subscriptions'), ['for' => 'account', 'class' => 'form-label fw-semibold']);
echo html_writer::select([
    'all' => get_string('commerce_personal_offer_account_all', 'local_subscriptions'),
    'yes' => get_string('commerce_personal_offer_account_yes', 'local_subscriptions'),
    'no' => get_string('commerce_personal_offer_account_no', 'local_subscriptions'),
], 'account', 'all', false, ['id' => 'account', 'class' => 'form-select']);
echo html_writer::div(get_string('commerce_personal_offer_account_filter_help', 'local_subscriptions'), 'form-text');
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('row g-3 mb-3');
foreach ([['purchasefrom', 'commerce_personal_offer_purchase_from', 'commerce_personal_offer_purchase_from_help'], ['purchaseto', 'commerce_personal_offer_purchase_to', 'commerce_personal_offer_purchase_to_help']] as [$name, $labelkey, $helpkey]) {
    echo html_writer::start_div('col-12 col-md-6');
    echo html_writer::tag('label', get_string($labelkey, 'local_subscriptions'), ['for' => $name, 'class' => 'form-label fw-semibold']);
    echo html_writer::empty_tag('input', ['id' => $name, 'name' => $name, 'type' => 'date', 'class' => 'form-control']);
    echo html_writer::div(get_string($helpkey, 'local_subscriptions'), 'form-text');
    echo html_writer::end_div();
}
echo html_writer::end_div();

echo html_writer::start_div('form-check mb-4');
echo html_writer::empty_tag('input', ['id' => 'excludeowned', 'class' => 'form-check-input', 'type' => 'checkbox', 'name' => 'excludeowned', 'value' => '1', 'checked' => 'checked']);
echo html_writer::tag('label', get_string('commerce_personal_offer_exclude_owned', 'local_subscriptions'), ['for' => 'excludeowned', 'class' => 'form-check-label fw-semibold']);
echo html_writer::div(get_string('commerce_personal_offer_exclude_owned_help', 'local_subscriptions'), 'form-text');
echo html_writer::end_div();

echo html_writer::start_div('border rounded p-3 mb-4');
echo html_writer::tag('h4', get_string('commerce_personal_offer_explicit_list', 'local_subscriptions'), ['class' => 'h6']);
echo html_writer::div(get_string('commerce_personal_offer_explicit_list_help', 'local_subscriptions'), 'text-muted small mb-2');
echo html_writer::start_div('input-group mb-2');
echo html_writer::empty_tag('input', ['id' => 'recipient-picker', 'type' => 'email', 'list' => 'recipient-list', 'class' => 'form-control', 'placeholder' => get_string('commerce_personal_offer_recipient_picker_placeholder', 'local_subscriptions')]);
echo html_writer::tag('button', get_string('add'), ['id' => 'recipient-add', 'type' => 'button', 'class' => 'btn btn-outline-secondary']);
echo html_writer::end_div();
echo html_writer::start_tag('datalist', ['id' => 'recipient-list']);
foreach ($emailrecords as $record) { echo html_writer::tag('option', '', ['value' => $record->email]); }
echo html_writer::end_tag('datalist');
echo html_writer::tag('textarea', '', ['id' => 'audiencelist', 'name' => 'audiencelist', 'class' => 'form-control', 'rows' => 6, 'placeholder' => get_string('commerce_personal_offer_explicit_list_placeholder', 'local_subscriptions')]);
echo html_writer::end_div();

echo html_writer::tag('h3', get_string('commerce_personal_offer_offer_title', 'local_subscriptions'), ['class' => 'h5 mt-2']);
echo html_writer::start_div('mb-4');
echo html_writer::tag('label', get_string('commerce_personal_offer_target', 'local_subscriptions'), ['for' => 'targetproductid', 'class' => 'form-label fw-semibold']);
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

echo html_writer::start_div('row g-3 mb-4');
foreach ($currencies as $currency) {
    echo html_writer::start_div('col-12 col-md-4');
    echo html_writer::tag('label', $currency . ($currency === 'EUR' ? ' (€)' : ($currency === 'RUB' ? ' (₽)' : '')), ['for' => 'amount-' . strtolower($currency), 'class' => 'form-label']);
    echo html_writer::empty_tag('input', ['id' => 'amount-' . strtolower($currency), 'name' => 'amount_' . strtolower($currency), 'type' => 'number', 'min' => '0', 'step' => '0.01', 'class' => 'form-control', 'placeholder' => $currency === 'EUR' ? '30.00' : ($currency === 'RUB' ? '2990.00' : '')]);
    echo html_writer::end_div();
}
echo html_writer::start_div('col-12 col-md-4');
echo html_writer::tag('label', get_string('commerce_personal_offer_percent', 'local_subscriptions'), ['for' => 'percent', 'class' => 'form-label']);
echo html_writer::empty_tag('input', ['id' => 'percent', 'name' => 'percent', 'type' => 'number', 'min' => '1', 'max' => '100', 'value' => '20', 'class' => 'form-control']);
echo html_writer::end_div();
echo html_writer::div(get_string('commerce_personal_offer_amounts_display_help', 'local_subscriptions'), 'col-12 form-text');
echo html_writer::end_div();

echo html_writer::start_div('card mb-4');
echo html_writer::start_div('card-body');
echo html_writer::tag('h3', get_string('commerce_personal_offer_validity_title', 'local_subscriptions'), ['class' => 'h5']);
echo html_writer::div(get_string('commerce_personal_offer_validity_help', 'local_subscriptions'), 'text-muted mb-3');

echo html_writer::start_div('mb-3');
echo html_writer::tag('label', get_string('commerce_personal_offer_validity_mode', 'local_subscriptions'), ['for' => 'validitymode', 'class' => 'form-label fw-semibold']);
echo html_writer::select([
    CommercePersonalOfferCampaignValidityService::MODE_FIXED => get_string('commerce_personal_offer_validity_fixed', 'local_subscriptions'),
    CommercePersonalOfferCampaignValidityService::MODE_DURATION => get_string('commerce_personal_offer_validity_duration', 'local_subscriptions'),
], 'validitymode', CommercePersonalOfferCampaignValidityService::MODE_FIXED, false, ['id' => 'validitymode', 'class' => 'form-select']);
echo html_writer::end_div();

echo html_writer::start_div('row g-3', ['id' => 'validity-fixed']);
foreach ([['validfrom', 'commerce_personal_offer_valid_from', false], ['expiresat', 'commerce_personal_offer_expires_at', true]] as [$name, $labelkey, $required]) {
    echo html_writer::start_div('col-12 col-md-6');
    echo html_writer::tag('label', get_string($labelkey, 'local_subscriptions'), ['for' => $name, 'class' => 'form-label fw-semibold']);
    $attrs = ['id' => $name, 'name' => $name, 'type' => 'datetime-local', 'class' => 'form-control'];
    if ($required) { $attrs['data-validity-required'] = '1'; }
    echo html_writer::empty_tag('input', $attrs);
    echo html_writer::end_div();
}
echo html_writer::end_div();

echo html_writer::start_div('row g-3 d-none', ['id' => 'validity-duration']);
echo html_writer::start_div('col-7 col-md-4');
echo html_writer::tag('label', get_string('commerce_personal_offer_validity_duration_value', 'local_subscriptions'), ['for' => 'validitydurationvalue', 'class' => 'form-label fw-semibold']);
echo html_writer::empty_tag('input', ['id' => 'validitydurationvalue', 'name' => 'validitydurationvalue', 'type' => 'number', 'min' => '1', 'max' => '8760', 'value' => '48', 'class' => 'form-control']);
echo html_writer::end_div();
echo html_writer::start_div('col-5 col-md-4');
echo html_writer::tag('label', get_string('commerce_personal_offer_validity_duration_unit', 'local_subscriptions'), ['for' => 'validitydurationunit', 'class' => 'form-label fw-semibold']);
echo html_writer::select([
    'hours' => get_string('commerce_personal_offer_validity_hours', 'local_subscriptions'),
    'days' => get_string('commerce_personal_offer_validity_days', 'local_subscriptions'),
], 'validitydurationunit', 'hours', false, ['id' => 'validitydurationunit', 'class' => 'form-select']);
echo html_writer::end_div();
echo html_writer::div(get_string('commerce_personal_offer_validity_duration_help', 'local_subscriptions'), 'col-12 form-text');
echo html_writer::end_div();

echo html_writer::start_div('mt-3');
echo html_writer::tag('label', get_string('commerce_personal_offer_validity_timezone', 'local_subscriptions'), ['for' => 'validitytimezone', 'class' => 'form-label fw-semibold']);
echo html_writer::empty_tag('input', ['id' => 'validitytimezone', 'name' => 'validitytimezone', 'type' => 'text', 'value' => CommercePersonalOfferCampaignValidityService::DEFAULT_TIMEZONE, 'class' => 'form-control', 'list' => 'validity-timezones']);
echo html_writer::tag('datalist',
    html_writer::tag('option', '', ['value' => 'Europe/Paris']) .
    html_writer::tag('option', '', ['value' => 'Europe/Moscow']) .
    html_writer::tag('option', '', ['value' => 'UTC']),
    ['id' => 'validity-timezones']
);
echo html_writer::div(get_string('commerce_personal_offer_validity_timezone_help', 'local_subscriptions'), 'form-text');
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::div(
    html_writer::tag('button', get_string('savechanges'), ['type' => 'submit', 'class' => 'btn btn-primary']) .
    html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/personal-offers/campaigns.php'), get_string('cancel'), ['class' => 'btn btn-outline-secondary ms-2']),
    'd-flex gap-2'
);
echo html_writer::end_tag('form');

$PAGE->requires->js_init_code("
document.addEventListener('DOMContentLoaded', function() {
  var validityMode = document.getElementById('validitymode');
  var validityFixed = document.getElementById('validity-fixed');
  var validityDuration = document.getElementById('validity-duration');
  var expiresAt = document.getElementById('expiresat');
  var durationValue = document.getElementById('validitydurationvalue');
  function syncValidityMode() {
    var duration = validityMode && validityMode.value === 'duration';
    if (validityFixed) validityFixed.classList.toggle('d-none', duration);
    if (validityDuration) validityDuration.classList.toggle('d-none', !duration);
    if (expiresAt) expiresAt.required = !duration;
    if (durationValue) durationValue.required = duration;
  }
  if (validityMode) validityMode.addEventListener('change', syncValidityMode);
  syncValidityMode();
  var audience = document.getElementById('audiencetype');
  var criteriaBlock = document.getElementById('criteria-source-block');
  var listBlock = document.getElementById('audiencelist');
  var sourceType = document.getElementById('source-type');
  var planWrap = document.getElementById('source-legacy-plan-wrap');
  var digitalWrap = document.getElementById('source-legacy-digital-wrap');
  var nativeWrap = document.getElementById('source-native-product-wrap');
  var plan = document.getElementById('source-legacy-plan');
  var digital = document.getElementById('source-legacy-digital');
  var nativeProduct = document.getElementById('source-native-product');

  function refreshSource() {
    var value = sourceType ? sourceType.value : 'legacy_plan';
    if (planWrap) planWrap.style.display = value === 'legacy_plan' ? '' : 'none';
    if (digitalWrap) digitalWrap.style.display = value === 'legacy_digital' ? '' : 'none';
    if (nativeWrap) nativeWrap.style.display = value === 'native_product' ? '' : 'none';

    var criteria = !audience || audience.value === 'criteria';
    if (plan) plan.required = criteria && value === 'legacy_plan';
    if (digital) digital.required = criteria && value === 'legacy_digital';
    if (nativeProduct) nativeProduct.required = criteria && value === 'native_product';
  }

  function refreshAudience() {
    var criteria = !audience || audience.value === 'criteria';
    if (criteriaBlock) criteriaBlock.style.display = criteria ? '' : 'none';
    refreshSource();
  }

  if (audience) audience.addEventListener('change', refreshAudience);
  if (sourceType) sourceType.addEventListener('change', refreshSource);
  refreshAudience();

  var add = document.getElementById('recipient-add');
  var picker = document.getElementById('recipient-picker');
  var list = document.getElementById('audiencelist');
  if (add && picker && list) {
    add.addEventListener('click', function() {
      var value = picker.value.trim().toLowerCase();
      if (!value) return;
      var rows = list.value.split(/\\r?\\n/).map(function(v){return v.trim().toLowerCase();}).filter(Boolean);
      if (rows.indexOf(value) === -1) rows.push(value);
      list.value = rows.join('\\n');
      picker.value = '';
    });
  }
});
");

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
