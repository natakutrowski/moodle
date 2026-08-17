<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\catalog\currency\CommerceCurrencyRegistry;
use local_subscriptions\commerce\catalog\readmodel\CommerceCatalogReadRepository;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\commerce\promotion\domain\CommercePromotion;
use local_subscriptions\commerce\promotion\repository\MoodleCommercePromotionRepository;
use local_subscriptions\commerce\promotion\service\CommercePromotionValidator;
use local_subscriptions\commerce\promotion\eligibility\CommercePromotionEligibilityRuleSet;
use local_subscriptions\crm\commerce\rendering\CommerceOffersAccessNavigationRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$id = optional_param('id', 0, PARAM_INT);
$repository = new MoodleCommercePromotionRepository();
$promotion = $id > 0 ? $repository->get_by_id($id) : null;
if ($id > 0 && $promotion === null) {
    throw new moodle_exception('invalidrecord');
}

$listurl = new moodle_url('/local/subscriptions/admin/commerce/promotions/index.php');
$offersaccessurl = new moodle_url('/local/subscriptions/admin/commerce/offers-access/index.php');
$pageurl = new moodle_url('/local/subscriptions/admin/commerce/promotions/edit.php', $id ? ['id' => $id] : []);
$title = get_string($id ? 'commerce_promotion_edit' : 'commerce_promotion_add', 'local_subscriptions');
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $title, 'local-subscriptions-commerce-promotion-edit-page');

$currencyregistry = new CommerceCurrencyRegistry();
$currencyflags = [
    'EUR' => '🇪🇺',
    'RUB' => '🇷🇺',
    'USD' => '🇺🇸',
    'GBP' => '🇬🇧',
    'CHF' => '🇨🇭',
    'CAD' => '🇨🇦',
    'JPY' => '🇯🇵',
];
$currencyoptions = ['' => '🌍 ' . get_string('commerce_promotion_all_currencies', 'local_subscriptions')];
foreach ($currencyregistry->options() as $currencycode => $currencylabel) {
    $currencyoptions[$currencycode] = ($currencyflags[$currencycode] ?? '💱') . ' ' . $currencylabel;
}
$catalog = new CommerceCatalogReadRepository($DB);
$productoptions = [];
$typeoptions = [];
foreach ($catalog->find_all() as $product) {
    // Keep the SKU as the submitted identifier, but never expose it in the editorial UI.
    $productoptions[$product->get_sku()] = $product->get_name();
    $type = $product->get_type();
    if (!isset($typeoptions[$type])) {
        $knownlabels = [
            CommerceProductType::COURSE_ACCESS => get_string('commerce_product_type_course_access', 'local_subscriptions'),
            CommerceProductType::DIGITAL_DOWNLOAD => get_string('commerce_product_type_digital_download', 'local_subscriptions'),
            CommerceProductType::BUNDLE => get_string('commerce_product_type_bundle', 'local_subscriptions'),
            CommerceProductType::SERVICE => get_string('commerce_product_type_service', 'local_subscriptions'),
        ];
        $typeoptions[$type] = $knownlabels[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }
}
asort($productoptions, SORT_NATURAL | SORT_FLAG_CASE);
asort($typeoptions, SORT_NATURAL | SORT_FLAG_CASE);

$displayvalue = static function(?CommercePromotion $promotion): string {
    if ($promotion === null) {
        return '10';
    }
    return format_float($promotion->get_discount_value() / 100, 2, true, true);
};
$displayminimum = static fn(?CommercePromotion $promotion): string => $promotion === null
    ? '0'
    : format_float($promotion->get_minimum_cart_minor() / 100, 2, true, true);

$existingrules = CommercePromotionEligibilityRuleSet::from_metadata($promotion?->get_metadata() ?? []);

$formatdatetime = static function(?int $timestamp): string {
    if ($timestamp === null) {
        return '';
    }
    $date = new DateTimeImmutable('@' . $timestamp);
    return $date->setTimezone(core_date::get_user_timezone_object())->format('Y-m-d\TH:i');
};
$parsedatetime = static function(string $value): ?int {
    $value = trim($value);
    if ($value === '') {
        return null;
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value, core_date::get_user_timezone_object());
    return $date === false ? null : $date->getTimestamp();
};

$data = [
    'name' => $promotion?->get_name() ?? '',
    'code' => $promotion?->get_code() ?? '',
    'discounttype' => $promotion?->get_discount_type() ?? CommercePromotion::TYPE_PERCENTAGE,
    'discountvalue' => $displayvalue($promotion),
    'currency' => $promotion?->get_currency() ?? '',
    'minimumcart' => $displayminimum($promotion),
    'startsat' => $formatdatetime($promotion?->get_starts_at()),
    'endsat' => $formatdatetime($promotion?->get_ends_at()),
    'active' => $promotion?->is_active() ?? true,
    'automatic' => $promotion?->is_automatic() ?? false,
    'stackable' => $promotion?->is_stackable() ?? false,
    'priority' => $promotion?->get_priority() ?? 0,
    'globalusagelimit' => $promotion?->get_global_usage_limit() ?? '',
    'userusagelimit' => $promotion?->get_user_usage_limit() ?? '',
    'productskus' => $promotion?->get_product_skus() ?? [],
    'producttypes' => $promotion?->get_product_types() ?? [],
    'eligibilityrequireslogin' => $existingrules->requires_login(),
    'eligibilitymode' => $existingrules->get_mode(),
    'eligibilityownsskus' => $existingrules->get_owned_skus(),
    'eligibilitynotownsskus' => $existingrules->get_not_owned_skus(),
];
$errors = [];

$normalisedecimal = static function(string $value): float {
    return (float)str_replace(',', '.', trim($value));
};

if (data_submitted() && confirm_sesskey()) {
    foreach (['name', 'code', 'discounttype', 'discountvalue', 'currency', 'minimumcart', 'priority',
              'globalusagelimit', 'userusagelimit', 'startsat', 'endsat'] as $field) {
        $data[$field] = optional_param($field, '', PARAM_RAW_TRIMMED);
    }
    foreach (['active', 'automatic', 'stackable', 'eligibilityrequireslogin'] as $field) {
        $data[$field] = optional_param($field, 0, PARAM_BOOL);
    }
    $data['productskus'] = optional_param_array('productskus', [], PARAM_RAW_TRIMMED);
    $data['producttypes'] = optional_param_array('producttypes', [], PARAM_ALPHANUMEXT);
    $data['eligibilitymode'] = optional_param('eligibilitymode', CommercePromotionEligibilityRuleSet::MODE_ALL, PARAM_ALPHA);
    $data['eligibilityownsskus'] = optional_param_array('eligibilityownsskus', [], PARAM_RAW_TRIMMED);
    $data['eligibilitynotownsskus'] = optional_param_array('eligibilitynotownsskus', [], PARAM_RAW_TRIMMED);

    if (in_array('__all__', $data['productskus'], true)) {
        $data['productskus'] = [];
    }
    if (in_array('__all__', $data['producttypes'], true)) {
        $data['producttypes'] = [];
    }
    $data['eligibilityownsskus'] = array_values(array_intersect(array_keys($productoptions), $data['eligibilityownsskus']));
    $data['eligibilitynotownsskus'] = array_values(array_intersect(array_keys($productoptions), $data['eligibilitynotownsskus']));
    $data['eligibilitymode'] = in_array($data['eligibilitymode'], [
        CommercePromotionEligibilityRuleSet::MODE_ALL,
        CommercePromotionEligibilityRuleSet::MODE_ANY,
    ], true) ? $data['eligibilitymode'] : CommercePromotionEligibilityRuleSet::MODE_ALL;

    $discountvalue = $normalisedecimal((string)$data['discountvalue']);
    $minimumcart = $normalisedecimal((string)$data['minimumcart']);
    $validationdata = $data;
    $validationdata['discountvalue'] = (int)round($discountvalue * 100);
    $validationdata['minimumcartminor'] = (int)round($minimumcart * 100);
    $validationdata['startsat'] = $parsedatetime((string)$data['startsat']);
    $validationdata['endsat'] = $parsedatetime((string)$data['endsat']);
    if ($data['startsat'] !== '' && $validationdata['startsat'] === null) {
        $errors['startsat'] = 'invalid';
    }
    if ($data['endsat'] !== '' && $validationdata['endsat'] === null) {
        $errors['endsat'] = 'invalid';
    }

    $validator = new CommercePromotionValidator();
    $errors = array_replace($errors, $validator->validate($validationdata, $repository, $id ?: null));
    if ($discountvalue <= 0) {
        $errors['discountvalue'] = 'invalid';
    }
    if ($minimumcart < 0) {
        $errors['minimumcart'] = 'invalid';
    }

    if ($errors === []) {
        $repository->save(new CommercePromotion(
            $id ?: null,
            (string)$data['name'],
            $data['automatic'] ? null : (string)$data['code'],
            (string)$data['discounttype'],
            (int)$validationdata['discountvalue'],
            $data['currency'] === '' ? null : (string)$data['currency'],
            (int)$validationdata['minimumcartminor'],
            $validationdata['startsat'],
            $validationdata['endsat'],
            !empty($data['active']),
            !empty($data['automatic']),
            !empty($data['stackable']),
            (int)$data['priority'],
            $data['globalusagelimit'] === '' ? null : (int)$data['globalusagelimit'],
            $data['userusagelimit'] === '' ? null : (int)$data['userusagelimit'],
            array_values(array_intersect(array_keys($productoptions), $data['productskus'])),
            array_values(array_intersect(array_keys($typeoptions), $data['producttypes'])),
            array_replace(
                $promotion?->get_metadata() ?? [],
                [
                    CommercePromotionEligibilityRuleSet::METADATA_KEY =>
                        CommercePromotionEligibilityRuleSet::create(
                            !empty($data['eligibilityrequireslogin']),
                            (string)$data['eligibilitymode'],
                            $data['eligibilityownsskus'],
                            $data['eligibilitynotownsskus']
                        )->to_metadata(),
                ]
            )
        ));
        redirect($listurl, get_string('changessaved'));
    }
}

$field = static function(
    string $name,
    string $label,
    string $help,
    array $data,
    array $errors,
    string $type = 'text',
    string $step = ''
): string {
    $attributes = [
        'type' => $type,
        'name' => $name,
        'id' => $name,
        'value' => $data[$name],
        'class' => 'form-control' . (isset($errors[$name]) ? ' is-invalid' : ''),
        'aria-describedby' => $name . '-help',
    ];
    if ($step !== '') {
        $attributes['step'] = $step;
    }
    $control = html_writer::empty_tag('input', $attributes);
    $error = isset($errors[$name])
        ? html_writer::div(get_string('commerce_promotion_validation_' . $errors[$name], 'local_subscriptions'), 'invalid-feedback d-block')
        : '';
    return html_writer::div(
        html_writer::tag('label', $label, ['for' => $name, 'class' => 'form-label fw-semibold']) .
        html_writer::div($help, 'form-text mt-0 mb-2', ['id' => $name . '-help']) .
        $control . $error,
        'col-md-6'
    );
};

$selectfield = static function(
    string $name,
    string $label,
    string $help,
    array $options,
    string $selected,
    array $errors
): string {
    $control = html_writer::select($options, $name, $selected, false, [
        'id' => $name,
        'class' => 'form-select' . (isset($errors[$name]) ? ' is-invalid' : ''),
        'aria-describedby' => $name . '-help',
    ]);
    $error = isset($errors[$name])
        ? html_writer::div(get_string('commerce_promotion_validation_' . $errors[$name], 'local_subscriptions'), 'invalid-feedback d-block')
        : '';
    return html_writer::div(
        html_writer::tag('label', $label, ['for' => $name, 'class' => 'form-label fw-semibold']) .
        html_writer::div($help, 'form-text mt-0 mb-2', ['id' => $name . '-help']) .
        $control . $error,
        'col-md-6'
    );
};

$multiselect = static function(
    string $name,
    string $label,
    string $help,
    array $options,
    array $selected,
    string $columnclass = 'col-md-6'
): string {
    $allselected = $selected === [];
    $options = ['__all__' => get_string('commerce_promotion_select_all', 'local_subscriptions')] + $options;
    $selected = $allselected ? ['__all__'] : $selected;
    return html_writer::div(
        html_writer::tag('label', $label, ['for' => $name, 'class' => 'form-label fw-semibold']) .
        html_writer::div($help, 'form-text mt-0 mb-2', ['id' => $name . '-help']) .
        html_writer::select($options, $name . '[]', $selected, false, [
            'id' => $name,
            'class' => 'form-select',
            'multiple' => 'multiple',
            'size' => min(8, max(3, count($options))),
            'aria-describedby' => $name . '-help',
        ]),
        $columnclass
    );
};

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('commerce_offers_access_title', 'local_subscriptions'), 'url' => $offersaccessurl],
    ['label' => get_string('commerce_promotions_title', 'local_subscriptions'), 'url' => $listurl],
    ['label' => $title, 'url' => null],
]);
$headeractions = html_writer::link(
    $listurl,
    html_writer::tag('i', '', ['class' => 'fa fa-arrow-left me-1', 'aria-hidden' => 'true'])
        . get_string('commerce_promotion_back_to_list', 'local_subscriptions'),
    ['class' => 'btn btn-outline-secondary']
);
echo CrmPageHeader::render(
    $title,
    get_string('commerce_promotions_description', 'local_subscriptions'),
    HelpContext::COMMERCE,
    $headeractions
);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::OFFERS_ACCESS, $context);
echo CommerceOffersAccessNavigationRenderer::render(CommerceOffersAccessNavigationRenderer::PROMOTIONS);

echo html_writer::start_tag('form', ['method' => 'post', 'class' => 'commerce-promotion-editor']);
echo html_writer::input_hidden_params($pageurl);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

// Identity and application mode.
echo html_writer::start_div('card commerce-promotion-editor-card mb-3');
echo html_writer::div(
    html_writer::div(
        html_writer::tag('h2', get_string('commerce_promotion_section_identity', 'local_subscriptions'), ['class' => 'h5 mb-1']) .
        html_writer::tag('p', get_string('commerce_promotion_section_identity_desc', 'local_subscriptions'), ['class' => 'text-muted mb-0']),
        'commerce-promotion-editor-card-copy'
    ) .
    html_writer::span(
        !empty($data['automatic'])
            ? get_string('commerce_promotion_automatic_badge', 'local_subscriptions')
            : get_string('commerce_promotion_coupon_badge', 'local_subscriptions'),
        'badge rounded-pill ' . (!empty($data['automatic']) ? 'text-bg-info' : 'text-bg-warning')
    ),
    'commerce-promotion-editor-card-header'
);
echo html_writer::start_div('card-body');
echo html_writer::start_div('row g-4');
echo $field('name', get_string('commerce_promotion_name', 'local_subscriptions'), get_string('commerce_promotion_name_help', 'local_subscriptions'), $data, $errors);
echo $field('code', get_string('commerce_promotion_code', 'local_subscriptions'), get_string('commerce_promotion_code_help', 'local_subscriptions'), $data, $errors);
echo html_writer::end_div();
echo html_writer::start_div('row g-3 mt-1 commerce-promotion-editor__checks');
foreach (['active', 'automatic', 'stackable'] as $checkbox) {
    echo html_writer::div(
        html_writer::div(
            html_writer::checkbox($checkbox, 1, !empty($data[$checkbox]), '', ['id' => $checkbox, 'class' => 'form-check-input']) .
            html_writer::tag('label', get_string('commerce_promotion_' . $checkbox, 'local_subscriptions'), [
                'for' => $checkbox,
                'class' => 'form-check-label fw-semibold',
            ]),
            'form-check'
        ) .
        html_writer::div(get_string('commerce_promotion_' . $checkbox . '_help', 'local_subscriptions'), 'form-text'),
        'col-12 col-lg-4 commerce-promotion-check-option'
    );
}
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Discount and usage rules.
echo html_writer::start_div('card commerce-promotion-editor-card mb-3');
echo html_writer::div(
    html_writer::tag('h2', get_string('commerce_promotion_section_discount', 'local_subscriptions'), ['class' => 'h5 mb-1']) .
    html_writer::tag('p', get_string('commerce_promotion_section_discount_desc', 'local_subscriptions'), ['class' => 'text-muted mb-0']),
    'commerce-promotion-editor-card-header'
);
echo html_writer::start_div('card-body');
echo html_writer::start_div('row g-4');
echo $selectfield('discounttype', get_string('commerce_promotion_type', 'local_subscriptions'), get_string('commerce_promotion_type_help', 'local_subscriptions'), [
    CommercePromotion::TYPE_PERCENTAGE => get_string('commerce_promotion_percentage', 'local_subscriptions'),
    CommercePromotion::TYPE_FIXED => get_string('commerce_promotion_fixed', 'local_subscriptions'),
], (string)$data['discounttype'], $errors);
echo $field('discountvalue', get_string('commerce_promotion_value_display', 'local_subscriptions'), get_string('commerce_promotion_value_display_help', 'local_subscriptions'), $data, $errors, 'number', '0.01');
echo $selectfield('currency', get_string('currency'), get_string('commerce_promotion_currency_help', 'local_subscriptions'), $currencyoptions, (string)$data['currency'], $errors);
echo $field('minimumcart', get_string('commerce_promotion_minimum_display', 'local_subscriptions'), get_string('commerce_promotion_minimum_help', 'local_subscriptions'), $data, $errors, 'number', '0.01');
echo $field('priority', get_string('commerce_promotion_priority', 'local_subscriptions'), get_string('commerce_promotion_priority_help', 'local_subscriptions'), $data, $errors, 'number', '1');
echo $field('globalusagelimit', get_string('commerce_promotion_global_limit', 'local_subscriptions'), get_string('commerce_promotion_global_limit_help', 'local_subscriptions'), $data, $errors, 'number', '1');
echo $field('userusagelimit', get_string('commerce_promotion_user_limit', 'local_subscriptions'), get_string('commerce_promotion_user_limit_help', 'local_subscriptions'), $data, $errors, 'number', '1');
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Validity window.
echo html_writer::start_div('card commerce-promotion-editor-card mb-3');
echo html_writer::div(
    html_writer::tag('h2', get_string('commerce_promotion_section_validity', 'local_subscriptions'), ['class' => 'h5 mb-1']) .
    html_writer::tag('p', get_string('commerce_promotion_section_validity_desc', 'local_subscriptions'), ['class' => 'text-muted mb-0']),
    'commerce-promotion-editor-card-header'
);
echo html_writer::start_div('card-body');
echo html_writer::start_div('row g-4');
echo $field('startsat', get_string('commerce_promotion_starts_at', 'local_subscriptions'), get_string('commerce_promotion_starts_at_help', 'local_subscriptions'), $data, $errors, 'datetime-local');
echo $field('endsat', get_string('commerce_promotion_ends_at', 'local_subscriptions'), get_string('commerce_promotion_ends_at_help', 'local_subscriptions'), $data, $errors, 'datetime-local');
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Product applicability.
echo html_writer::start_div('card commerce-promotion-editor-card mb-3');
echo html_writer::div(
    html_writer::tag('h2', get_string('commerce_promotion_section_products', 'local_subscriptions'), ['class' => 'h5 mb-1']) .
    html_writer::tag('p', get_string('commerce_promotion_section_products_desc', 'local_subscriptions'), ['class' => 'text-muted mb-0']),
    'commerce-promotion-editor-card-header'
);
echo html_writer::start_div('card-body');
echo html_writer::start_div('row g-4');
echo $multiselect('productskus', get_string('commerce_promotion_productskus', 'local_subscriptions'), get_string('commerce_promotion_productskus_help', 'local_subscriptions'), $productoptions, $data['productskus']);
echo $multiselect('producttypes', get_string('commerce_promotion_producttypes', 'local_subscriptions'), get_string('commerce_promotion_producttypes_help', 'local_subscriptions'), $typeoptions, $data['producttypes']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('card commerce-promotion-editor-card mb-3');
echo html_writer::div(
    html_writer::tag('h2', get_string('commerce_promotion_customer_eligibility', 'local_subscriptions'), ['class' => 'h5 mb-1']) .
    html_writer::tag('p', get_string('commerce_promotion_customer_eligibility_help', 'local_subscriptions'), ['class' => 'text-muted mb-0']),
    'commerce-promotion-editor-card-header'
);
echo html_writer::start_div('card-body');
echo html_writer::start_div('row g-4');
echo html_writer::div(
    html_writer::div(
        html_writer::checkbox(
            'eligibilityrequireslogin',
            1,
            !empty($data['eligibilityrequireslogin']),
            '',
            ['id' => 'eligibilityrequireslogin', 'class' => 'form-check-input']
        ) .
        html_writer::tag(
            'label',
            get_string('commerce_promotion_requires_login', 'local_subscriptions'),
            ['for' => 'eligibilityrequireslogin', 'class' => 'form-check-label']
        ),
        'form-check'
    ) .
    html_writer::div(
        get_string('commerce_promotion_requires_login_help', 'local_subscriptions'),
        'form-text'
    ),
    'col-12'
);
echo $selectfield(
    'eligibilitymode',
    get_string('commerce_promotion_eligibility_mode', 'local_subscriptions'),
    get_string('commerce_promotion_eligibility_mode_help', 'local_subscriptions'),
    [
        CommercePromotionEligibilityRuleSet::MODE_ALL =>
            get_string('commerce_promotion_eligibility_all', 'local_subscriptions'),
        CommercePromotionEligibilityRuleSet::MODE_ANY =>
            get_string('commerce_promotion_eligibility_any', 'local_subscriptions'),
    ],
    (string)$data['eligibilitymode'],
    $errors
);
echo html_writer::end_div();
echo html_writer::start_div('row g-4 mt-1 commerce-promotion-eligibility-products');
echo $multiselect(
    'eligibilityownsskus',
    get_string('commerce_promotion_required_owned_products', 'local_subscriptions'),
    get_string('commerce_promotion_required_owned_products_help', 'local_subscriptions'),
    $productoptions,
    $data['eligibilityownsskus'],
    'col-12'
);
echo $multiselect(
    'eligibilitynotownsskus',
    get_string('commerce_promotion_excluded_owned_products', 'local_subscriptions'),
    get_string('commerce_promotion_excluded_owned_products_help', 'local_subscriptions'),
    $productoptions,
    $data['eligibilitynotownsskus'],
    'col-12'
);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::div(
    html_writer::tag('button', get_string('savechanges'), ['type' => 'submit', 'class' => 'btn btn-primary me-2']) .
    html_writer::link($listurl, get_string('cancel'), ['class' => 'btn btn-outline-secondary']),
    'commerce-promotion-editor-actions'
);
echo html_writer::end_tag('form');
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
