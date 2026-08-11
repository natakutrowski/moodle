<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\catalog\currency\CommerceCurrencyRegistry;
use local_subscriptions\commerce\catalog\readmodel\CommerceCatalogReadRepository;
use local_subscriptions\commerce\promotion\domain\CommercePromotion;
use local_subscriptions\commerce\promotion\repository\MoodleCommercePromotionRepository;
use local_subscriptions\commerce\promotion\service\CommercePromotionValidator;
use local_subscriptions\commerce\promotion\eligibility\CommercePromotionEligibilityRuleSet;
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
$configurl = new moodle_url('/local/subscriptions/admin/commerce/configuration/index.php');
$pageurl = new moodle_url('/local/subscriptions/admin/commerce/promotions/edit.php', $id ? ['id' => $id] : []);
$title = get_string($id ? 'commerce_promotion_edit' : 'commerce_promotion_add', 'local_subscriptions');
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $title, 'local-subscriptions-commerce-promotion-edit-page');

$currencyregistry = new CommerceCurrencyRegistry();
$currencyoptions = ['' => get_string('commerce_promotion_all_currencies', 'local_subscriptions')] + $currencyregistry->options();
$catalog = new CommerceCatalogReadRepository($DB);
$productoptions = [];
$typeoptions = [];
foreach ($catalog->find_all() as $product) {
    $productoptions[$product->get_sku()] = $product->get_name() . ' — ' . $product->get_sku();
    $type = $product->get_type();
    if (!isset($typeoptions[$type])) {
        $typeoptions[$type] = get_string_manager()->string_exists('commerce_storefront_type_' . $type, 'local_subscriptions')
            ? get_string('commerce_storefront_type_' . $type, 'local_subscriptions')
            : ucfirst(str_replace('_', ' ', $type));
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

$data = [
    'name' => $promotion?->get_name() ?? '',
    'code' => $promotion?->get_code() ?? '',
    'discounttype' => $promotion?->get_discount_type() ?? CommercePromotion::TYPE_PERCENTAGE,
    'discountvalue' => $displayvalue($promotion),
    'currency' => $promotion?->get_currency() ?? '',
    'minimumcart' => $displayminimum($promotion),
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
              'globalusagelimit', 'userusagelimit'] as $field) {
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

    $validator = new CommercePromotionValidator();
    $errors = $validator->validate($validationdata, $repository, $id ?: null);
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
            null,
            null,
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
    array $selected
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
        'col-md-6'
    );
};

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('commerce_configuration_title', 'local_subscriptions'), 'url' => $configurl],
    ['label' => get_string('commerce_promotions_title', 'local_subscriptions'), 'url' => $listurl],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render($title, get_string('commerce_promotions_description', 'local_subscriptions'), HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::CONFIGURATION, $context);
echo html_writer::div(
    html_writer::link($listurl, '← ' . get_string('commerce_promotion_back_to_list', 'local_subscriptions'), ['class' => 'btn btn-outline-secondary']),
    'mb-3'
);

echo html_writer::start_tag('form', ['method' => 'post', 'class' => 'card card-body commerce-promotion-editor']);
echo html_writer::input_hidden_params($pageurl);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::start_div('row g-4');
echo $field('name', get_string('commerce_promotion_name', 'local_subscriptions'), get_string('commerce_promotion_name_help', 'local_subscriptions'), $data, $errors);
echo $field('code', get_string('commerce_promotion_code', 'local_subscriptions'), get_string('commerce_promotion_code_help', 'local_subscriptions'), $data, $errors);
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

echo html_writer::start_div('col-12');
echo html_writer::start_div('row g-3 commerce-promotion-editor__checks');
foreach (['active', 'automatic', 'stackable'] as $checkbox) {
    echo html_writer::div(
        html_writer::div(
            html_writer::checkbox($checkbox, 1, !empty($data[$checkbox]), '', ['id' => $checkbox, 'class' => 'form-check-input']) .
            html_writer::tag('label', get_string('commerce_promotion_' . $checkbox, 'local_subscriptions'), [
                'for' => $checkbox,
                'class' => 'form-check-label',
            ]),
            'form-check'
        ) .
        html_writer::div(get_string('commerce_promotion_' . $checkbox . '_help', 'local_subscriptions'), 'form-text'),
        'col-md-4'
    );
}
echo html_writer::end_div();
echo html_writer::end_div();

echo $multiselect('productskus', get_string('commerce_promotion_productskus', 'local_subscriptions'), get_string('commerce_promotion_productskus_help', 'local_subscriptions'), $productoptions, $data['productskus']);
echo $multiselect('producttypes', get_string('commerce_promotion_producttypes', 'local_subscriptions'), get_string('commerce_promotion_producttypes_help', 'local_subscriptions'), $typeoptions, $data['producttypes']);
echo html_writer::end_div();

echo html_writer::start_div('card border-0 bg-light mt-4');
echo html_writer::start_div('card-body');
echo html_writer::tag(
    'h3',
    get_string('commerce_promotion_customer_eligibility', 'local_subscriptions'),
    ['class' => 'h5 mb-2']
);
echo html_writer::div(
    get_string('commerce_promotion_customer_eligibility_help', 'local_subscriptions'),
    'text-muted mb-3'
);
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
echo $multiselect(
    'eligibilityownsskus',
    get_string('commerce_promotion_required_owned_products', 'local_subscriptions'),
    get_string('commerce_promotion_required_owned_products_help', 'local_subscriptions'),
    $productoptions,
    $data['eligibilityownsskus']
);
echo $multiselect(
    'eligibilitynotownsskus',
    get_string('commerce_promotion_excluded_owned_products', 'local_subscriptions'),
    get_string('commerce_promotion_excluded_owned_products_help', 'local_subscriptions'),
    $productoptions,
    $data['eligibilitynotownsskus']
);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::div(
    html_writer::tag('button', get_string('savechanges'), ['type' => 'submit', 'class' => 'btn btn-primary me-2']) .
    html_writer::link($listurl, get_string('cancel'), ['class' => 'btn btn-outline-secondary']),
    'mt-4'
);
echo html_writer::end_tag('form');
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
