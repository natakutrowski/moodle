<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\catalog\admin\CommerceCatalogProductInput;
use local_subscriptions\commerce\catalog\admin\CommerceProductLifecycleService;
use local_subscriptions\commerce\catalog\admin\CommerceProductSkuGenerator;
use local_subscriptions\commerce\catalog\domain\CommerceProductStatus;
use local_subscriptions\commerce\catalog\domain\CommerceProductTranslation;
use local_subscriptions\commerce\catalog\presentation\CommerceProductPresentation;
use local_subscriptions\commerce\catalog\presentation\CommerceCatalogProductNameResolver;
use local_subscriptions\commerce\catalog\presentation\CommerceLanguagePresentation;
use local_subscriptions\commerce\catalog\rendering\CommerceProductEditorNavigationRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\crm\commerce\presentation\CommerceDesignSystemRenderer;
use local_subscriptions\crm\commerce\presentation\CommerceProductPageHeaderRenderer;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$sku = optional_param('sku', '', PARAM_RAW_TRIMMED);
$factory = new CommerceCatalogFactory($DB);
$manager = $factory->product_manager();
$editor = $sku !== '' ? $manager->get_editor_data($sku) : null;
$product = $editor?->get_product();
$pageurl = new moodle_url('/local/subscriptions/admin/commerce/products/edit.php', $sku !== '' ? ['sku' => $sku] : []);
$pagetitle = $product !== null
    ? CommerceCatalogProductNameResolver::resolve_native_id(
        $DB,
        (int)$product->get_id(),
        $product->get_name()
    )
    : get_string('commerce_product_add', 'local_subscriptions');
$identityeditable = $product === null
    || (new CommerceProductLifecycleService($DB))->can_change_identity(
        (int)$product->get_id(),
        $product->get_sku()
    );

CrmPageConfigurator::configure($PAGE, $context, $pageurl, $pagetitle, 'local-subscriptions-commerce-product-edit-page');

if (data_submitted() && confirm_sesskey()) {
    $producttype = required_param('producttype', PARAM_ALPHANUMEXT);
    $technicalname = required_param('productname', PARAM_TEXT);
    $requestedsku = strtoupper(trim(optional_param('productsku', '', PARAM_RAW_TRIMMED)));
    $submittedsku = $requestedsku !== ''
        ? $requestedsku
        : ($sku !== ''
            ? $sku
            : (new CommerceProductSkuGenerator($DB))->generate(
                $producttype,
                $technicalname
            ));
    $input = new CommerceCatalogProductInput(
        $submittedsku,
        $producttype,
        optional_param('productstatus', CommerceProductStatus::INACTIVE, PARAM_ALPHANUMEXT),
        $technicalname,
        optional_param('description', '', PARAM_TEXT)
    );

    $saved = $manager->save_product_input($input, $sku !== '' ? $sku : null);

    $savedsku = $saved->get_product()->get_sku();
    $translations = [];
    foreach ($factory->locale_service()->get_languages() as $language => $languagelabel) {
        $translatedname = optional_param('translation_name_' . $language, '', PARAM_TEXT);
        $shortdescription = optional_param('translation_short_' . $language, '', PARAM_TEXT);
        $translateddescription = optional_param('translation_description_' . $language, '', PARAM_TEXT);
        if (trim($translatedname) !== '') {
            $translations[] = new CommerceProductTranslation(
                $savedsku,
                $language,
                $translatedname,
                $shortdescription,
                $translateddescription
            );
        }
    }
    $manager->save_translations($savedsku, $translations);

    redirect(
        new moodle_url('/local/subscriptions/admin/commerce/products/edit.php', [
            'sku' => $saved->get_product()->get_sku(),
        ]),
        get_string('changessaved')
    );
}

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
if ($product !== null) {
    echo CommerceProductEditorNavigationRenderer::breadcrumb(
        $pagetitle,
        get_string(
            'commerce_product_step_information',
            'local_subscriptions'
        )
    );
    echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::PRODUCTS
);
echo CommerceProductEditorNavigationRenderer::render(
        $product,
        CommerceProductEditorNavigationRenderer::INFORMATION
    );
}
echo CommerceProductPageHeaderRenderer::render(
    $pagetitle,
    CommerceDesignSystemRenderer::page_intro(get_string('commerce_product_description_help', 'local_subscriptions')),
    '',
    get_string('commerce_products_title', 'local_subscriptions')
);
echo html_writer::start_tag('form', [
    'method' => 'post',
    'class' => 'card card-body crm-commerce-editor-form crm-product-edit-shell',
]);
echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'sesskey',
    'value' => sesskey(),
]);

echo html_writer::start_div('crm-product-edit-top-grid');

echo html_writer::start_div('crm-product-edit-main-panel');
echo html_writer::div(
    html_writer::tag('i', '', [
        'class' => 'fa fa-pencil me-2',
        'aria-hidden' => 'true',
    ])
    . html_writer::tag(
        'h3',
        get_string('commerce_product_edit_general_title', 'local_subscriptions'),
        ['class' => 'h5 mb-0']
    ),
    'crm-product-edit-section-title'
);

echo html_writer::start_div('mb-3');
echo html_writer::tag(
    'label',
    get_string('commerce_product_fallback_name', 'local_subscriptions'),
    ['for' => 'productname', 'class' => 'form-label']
);
echo html_writer::empty_tag('input', [
    'id' => 'productname',
    'name' => 'productname',
    'value' => $product?->get_name() ?? '',
    'class' => 'form-control',
    'required' => 'required',
]);
echo html_writer::tag(
    'div',
    get_string('commerce_product_fallback_name_help', 'local_subscriptions'),
    ['class' => 'form-text']
);
echo html_writer::end_div();

echo html_writer::start_div('mb-0');
echo html_writer::tag(
    'label',
    get_string('commerce_product_description', 'local_subscriptions'),
    ['for' => 'description', 'class' => 'form-label']
);
echo html_writer::tag(
    'textarea',
    s($product?->get_description() ?? ''),
    [
        'id' => 'description',
        'name' => 'description',
        'class' => 'form-control',
        'rows' => 5,
    ]
);
echo html_writer::tag(
    'div',
    get_string('commerce_product_description_help', 'local_subscriptions'),
    ['class' => 'form-text']
);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('crm-product-edit-identity-panel');
echo html_writer::div(
    html_writer::tag('i', '', [
        'class' => 'fa fa-fingerprint me-2',
        'aria-hidden' => 'true',
    ])
    . html_writer::tag(
        'h3',
        get_string('commerce_product_identity_title', 'local_subscriptions'),
        ['class' => 'h5 mb-0']
    ),
    'crm-product-edit-section-title'
);

$currentstatus = $product?->get_status() ?? CommerceProductStatus::INACTIVE;
echo html_writer::div(
    html_writer::span(
        get_string('commerce_product_status', 'local_subscriptions'),
        'crm-product-edit-meta-label'
    )
    . CommerceProductPresentation::status_badge($currentstatus),
    'crm-product-edit-status-row'
);
echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'productstatus',
    'value' => $currentstatus,
]);

echo html_writer::start_div('crm-product-edit-identity-field');
echo html_writer::tag(
    'label',
    get_string('commerce_product_sku', 'local_subscriptions'),
    ['for' => 'productsku', 'class' => 'form-label']
);
$skuattributes = [
    'id' => 'productsku',
    'name' => 'productsku',
    'value' => $product?->get_sku() ?? '',
    'class' => 'form-control form-control-sm font-monospace',
    'placeholder' => get_string(
        'commerce_product_sku_auto_placeholder',
        'local_subscriptions'
    ),
];
if (!$identityeditable) {
    $skuattributes['readonly'] = 'readonly';
}
echo html_writer::empty_tag('input', $skuattributes);
echo html_writer::tag(
    'div',
    get_string(
        $identityeditable
            ? 'commerce_product_identity_editable_help'
            : 'commerce_product_identity_locked_help',
        'local_subscriptions'
    ),
    ['class' => 'form-text']
);
echo html_writer::end_div();

$typecodes = array_map(
    static fn($type): string => $type->get_code(),
    $factory->product_type_registry()->all()
);
$typeoptions = [];
foreach ($typecodes as $typecode) {
    $typeoptions[$typecode] = CommerceProductPresentation::type_label($typecode);
}
echo html_writer::start_div('crm-product-edit-identity-field');
echo html_writer::tag(
    'label',
    get_string('commerce_product_type', 'local_subscriptions'),
    ['for' => 'producttype', 'class' => 'form-label']
);
$typeattributes = ['class' => 'form-select', 'id' => 'producttype'];
if (!$identityeditable) {
    $typeattributes['disabled'] = 'disabled';
}
echo html_writer::select(
    $typeoptions,
    'producttype',
    $product?->get_type() ?? 'bundle',
    false,
    $typeattributes
);
if (!$identityeditable && $product !== null) {
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'producttype',
        'value' => $product->get_type(),
    ]);
}
echo html_writer::tag(
    'div',
    get_string('commerce_product_type_help', 'local_subscriptions'),
    ['class' => 'form-text']
);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div();

$existingtranslations = [];
foreach ($editor?->get_translations() ?? [] as $translation) {
    $existingtranslations[$translation->get_language()] = $translation;
}
$languages = $factory->locale_service()->get_languages();
echo html_writer::start_div('crm-product-edit-translations mt-4');
echo html_writer::div(
    html_writer::tag('i', '', [
        'class' => 'fa fa-language me-2',
        'aria-hidden' => 'true',
    ])
    . html_writer::tag(
        'h3',
        get_string('commerce_product_translations_title', 'local_subscriptions'),
        ['class' => 'h5 mb-0']
    ),
    'crm-product-edit-section-title'
);
echo html_writer::tag(
    'p',
    get_string('commerce_product_translations_help', 'local_subscriptions'),
    ['class' => 'text-muted mb-3']
);

echo html_writer::start_div('crm-product-language-tabs', [
    'role' => 'tablist',
    'aria-label' => get_string(
        'commerce_product_translations_title',
        'local_subscriptions'
    ),
]);
$languageindex = 0;
foreach ($languages as $language => $languagelabel) {
    echo html_writer::tag(
        'button',
        CommerceLanguagePresentation::label($language, $languagelabel),
        [
            'type' => 'button',
            'class' => 'crm-product-language-tab'
                . ($languageindex === 0 ? ' is-active' : ''),
            'data-language-tab' => $language,
            'role' => 'tab',
            'aria-selected' => $languageindex === 0 ? 'true' : 'false',
        ]
    );
    $languageindex++;
}
echo html_writer::end_div();

$languageindex = 0;
foreach ($languages as $language => $languagelabel) {
    $translation = $existingtranslations[$language] ?? null;
    echo html_writer::start_div(
        'crm-product-language-pane'
        . ($languageindex === 0 ? ' is-active' : ''),
        [
            'data-language-pane' => $language,
            'role' => 'tabpanel',
        ]
    );
    echo html_writer::start_div('row g-3');
    echo html_writer::div(
        html_writer::tag(
            'label',
            get_string('commerce_product_name', 'local_subscriptions'),
            [
                'for' => 'translation_name_' . $language,
                'class' => 'form-label',
            ]
        )
        . html_writer::empty_tag('input', [
            'id' => 'translation_name_' . $language,
            'name' => 'translation_name_' . $language,
            'value' => $translation?->get_name() ?? '',
            'class' => 'form-control',
        ]),
        'col-lg-6'
    );
    echo html_writer::div(
        html_writer::tag(
            'label',
            get_string(
                'commerce_product_short_description',
                'local_subscriptions'
            ),
            [
                'for' => 'translation_short_' . $language,
                'class' => 'form-label',
            ]
        )
        . html_writer::empty_tag('input', [
            'id' => 'translation_short_' . $language,
            'name' => 'translation_short_' . $language,
            'value' => $translation?->get_short_description() ?? '',
            'class' => 'form-control',
        ]),
        'col-lg-6'
    );
    echo html_writer::div(
        html_writer::tag(
            'label',
            get_string('commerce_product_description', 'local_subscriptions'),
            [
                'for' => 'translation_description_' . $language,
                'class' => 'form-label',
            ]
        )
        . html_writer::tag(
            'textarea',
            s($translation?->get_description() ?? ''),
            [
                'id' => 'translation_description_' . $language,
                'name' => 'translation_description_' . $language,
                'class' => 'form-control',
                'rows' => 6,
            ]
        ),
        'col-12'
    );
    echo html_writer::end_div();
    echo html_writer::end_div();
    $languageindex++;
}
echo html_writer::end_div();

$PAGE->requires->js_init_code(<<<JS
(function() {
    const tabs = Array.from(document.querySelectorAll('[data-language-tab]'));
    const panes = Array.from(document.querySelectorAll('[data-language-pane]'));
    tabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            const language = tab.getAttribute('data-language-tab');
            tabs.forEach(function(item) {
                const active = item === tab;
                item.classList.toggle('is-active', active);
                item.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            panes.forEach(function(pane) {
                pane.classList.toggle(
                    'is-active',
                    pane.getAttribute('data-language-pane') === language
                );
            });
        });
    });
})();
JS);

echo CommerceDesignSystemRenderer::form_actions(
    html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-primary', 'value' => get_string('savechanges')]),
    html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/products/index.php'), get_string('cancel'), ['class' => 'btn btn-outline-secondary'])
);
echo html_writer::end_tag('form');

if ($product !== null) {
    echo html_writer::div(
        html_writer::link(
            new moodle_url('/local/subscriptions/admin/commerce/products/lifecycle.php', ['sku' => $product->get_sku()]),
            get_string('commerce_product_lifecycle_title', 'local_subscriptions'),
            ['class' => 'btn btn-outline-danger']
        ),
        'card card-body mt-4'
    );
}

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
