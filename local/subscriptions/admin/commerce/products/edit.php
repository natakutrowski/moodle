<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\catalog\admin\CommerceCatalogProductInput;
use local_subscriptions\commerce\catalog\domain\CommerceProductStatus;
use local_subscriptions\commerce\catalog\domain\CommerceProductTranslation;
use local_subscriptions\commerce\catalog\presentation\CommerceProductPresentation;
use local_subscriptions\commerce\catalog\presentation\CommerceLanguagePresentation;
use local_subscriptions\commerce\catalog\rendering\CommerceProductEditorNavigationRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$sku = optional_param('sku', '', PARAM_RAW_TRIMMED);
$factory = new CommerceCatalogFactory($DB);
$manager = $factory->product_manager();
$editor = $sku !== '' ? $manager->get_editor_data($sku) : null;
$product = $editor?->get_product();
$pageurl = new moodle_url('/local/subscriptions/admin/commerce/products/edit.php', $sku !== '' ? ['sku' => $sku] : []);
$pagetitle = $product ? $product->get_name() : get_string('commerce_product_add', 'local_subscriptions');

CrmPageConfigurator::configure($PAGE, $context, $pageurl, $pagetitle, 'local-subscriptions-commerce-product-edit-page');

if (data_submitted() && confirm_sesskey()) {
    $input = new CommerceCatalogProductInput(
        required_param('productsku', PARAM_RAW_TRIMMED),
        required_param('producttype', PARAM_ALPHANUMEXT),
        required_param('productstatus', PARAM_ALPHANUMEXT),
        required_param('productname', PARAM_TEXT),
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
    echo CommerceProductEditorNavigationRenderer::breadcrumb($product->get_name(), get_string('commerce_product_step_information', 'local_subscriptions'));
    echo CommerceProductEditorNavigationRenderer::render($product->get_sku(), CommerceProductEditorNavigationRenderer::INFORMATION);
}
echo $OUTPUT->heading(format_string($pagetitle));
echo html_writer::start_tag('form', ['method' => 'post', 'class' => 'card card-body']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

$fields = [
    ['productsku', get_string('commerce_product_sku', 'local_subscriptions'), $product?->get_sku() ?? '', $product !== null],
    ['productname', get_string('commerce_product_name', 'local_subscriptions'), $product?->get_name() ?? '', false],
];

foreach ($fields as [$name, $label, $value, $readonly]) {
    echo html_writer::start_div('mb-3');
    echo html_writer::tag('label', $label, ['for' => $name, 'class' => 'form-label']);
    $attributes = [
        'id' => $name,
        'name' => $name,
        'value' => $value,
        'class' => 'form-control',
        'required' => 'required',
    ];
    if ($readonly) {
        $attributes['readonly'] = 'readonly';
    }
    echo html_writer::empty_tag('input', $attributes);
    echo html_writer::end_div();
}

$typecodes = array_map(static fn($type): string => $type->get_code(), $factory->product_type_registry()->all());
echo html_writer::start_div('mb-3');
echo html_writer::tag('label', get_string('commerce_product_type', 'local_subscriptions'), ['for' => 'producttype', 'class' => 'form-label']);

$typeoptions = [];
foreach ($typecodes as $typecode) {
    $typeoptions[$typecode] = CommerceProductPresentation::type_label($typecode);
}
echo html_writer::select($typeoptions, 'producttype', $product?->get_type() ?? 'bundle', false, ['class' => 'form-select', 'id' => 'producttype'] + (($product !== null && $product->get_status() !== CommerceProductStatus::DRAFT) ? ['disabled' => 'disabled'] : []));
if ($product !== null && $product->get_status() !== CommerceProductStatus::DRAFT) {
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'producttype', 'value' => $product->get_type()]);
}
echo html_writer::tag('div', get_string('commerce_product_type_help', 'local_subscriptions'), ['class' => 'form-text']);
echo html_writer::end_div();

$statuscodes = CommerceProductStatus::all();
echo html_writer::start_div('mb-3');
echo html_writer::tag('label', get_string('commerce_product_status', 'local_subscriptions'), ['for' => 'productstatus', 'class' => 'form-label']);
$statusoptions = [];
foreach ($statuscodes as $statuscode) {
    $statusoptions[$statuscode] = CommerceProductPresentation::status_label($statuscode);
}
echo html_writer::select($statusoptions, 'productstatus', $product?->get_status() ?? CommerceProductStatus::INACTIVE, false, ['class' => 'form-select', 'id' => 'productstatus']);
echo html_writer::end_div();

echo html_writer::start_div('mb-3');
echo html_writer::tag('label', get_string('commerce_product_description', 'local_subscriptions'), ['for' => 'description', 'class' => 'form-label']);
echo html_writer::tag('textarea', s($product?->get_description() ?? ''), ['id' => 'description', 'name' => 'description', 'class' => 'form-control', 'rows' => 5]);
echo html_writer::tag('div', get_string('commerce_product_description_help', 'local_subscriptions'), ['class' => 'form-text']);
echo html_writer::end_div();

$existingtranslations = [];
foreach ($editor?->get_translations() ?? [] as $translation) {
    $existingtranslations[$translation->get_language()] = $translation;
}
echo html_writer::tag('h3', get_string('commerce_product_translations_title', 'local_subscriptions'), ['class' => 'h5 mt-4']);
echo html_writer::tag('p', get_string('commerce_product_translations_help', 'local_subscriptions'), ['class' => 'text-muted']);
foreach ($factory->locale_service()->get_languages() as $language => $languagelabel) {
    $translation = $existingtranslations[$language] ?? null;
    echo html_writer::start_div('border rounded p-3 mb-3');
    echo html_writer::tag('h4', CommerceLanguagePresentation::label($language, $languagelabel), ['class' => 'h6']);
    echo html_writer::tag('label', get_string('commerce_product_name', 'local_subscriptions'), ['for' => 'translation_name_' . $language, 'class' => 'form-label']);
    echo html_writer::empty_tag('input', ['id' => 'translation_name_' . $language, 'name' => 'translation_name_' . $language, 'value' => $translation?->get_name() ?? '', 'class' => 'form-control mb-3']);
    echo html_writer::tag('label', get_string('commerce_product_short_description', 'local_subscriptions'), ['for' => 'translation_short_' . $language, 'class' => 'form-label']);
    echo html_writer::empty_tag('input', ['id' => 'translation_short_' . $language, 'name' => 'translation_short_' . $language, 'value' => $translation?->get_short_description() ?? '', 'class' => 'form-control mb-3']);
    echo html_writer::tag('label', get_string('commerce_product_description', 'local_subscriptions'), ['for' => 'translation_description_' . $language, 'class' => 'form-label']);
    echo html_writer::tag('textarea', s($translation?->get_description() ?? ''), ['id' => 'translation_description_' . $language, 'name' => 'translation_description_' . $language, 'class' => 'form-control', 'rows' => 4]);
    echo html_writer::end_div();
}

echo html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-primary', 'value' => get_string('savechanges')]);
echo html_writer::end_tag('form');


echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
