<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\catalog\rendering\CommerceProductEditorNavigationRenderer;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\commerce\storefront\admin\CommerceStorefrontPageEditor;
use local_subscriptions\commerce\storefront\admin\CommerceStorefrontSectionStatusService;
use local_subscriptions\commerce\storefront\admin\CommerceStorefrontResetService;
use local_subscriptions\commerce\storefront\admin\CommerceStorefrontComposerTemplateService;
use local_subscriptions\commerce\storefront\admin\CommerceStorefrontVisualBuilderService;
use local_subscriptions\commerce\storefront\content\CommerceStorefrontContentFileService;
use local_subscriptions\commerce\storefront\content\CommerceStorefrontH5pService;
use local_subscriptions\commerce\storefront\transfer\CommerceStorefrontPackageService;
use local_subscriptions\commerce\storefront\localisation\CommerceStorefrontLocaleTransferService;
use local_subscriptions\commerce\storefront\translation\CommerceStorefrontAiTranslationService;
use local_subscriptions\commerce\storefront\page\CommerceStorefrontLayoutContract;
use local_subscriptions\commerce\showroom\CommerceShowroomRegistry;
use local_subscriptions\commerce\showroom\CommerceShowroomMediaService;
use local_subscriptions\crm\commerce\presentation\CommerceDesignSystemRenderer;
use local_subscriptions\crm\commerce\presentation\CommerceProductPageHeaderRenderer;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$sku = required_param('sku', PARAM_RAW_TRIMMED);
$factory = new CommerceCatalogFactory($DB);
$manager = $factory->product_manager();
$editor = $manager->get_editor_data($sku);
$product = $editor->get_product();
$pageeditor = new CommerceStorefrontPageEditor();
$contentfiles = CommerceStorefrontContentFileService::create();
$h5pservice = CommerceStorefrontH5pService::create();
$showroommedia = CommerceShowroomMediaService::create();
$builderservice = new CommerceStorefrontVisualBuilderService();
$PAGE->requires->css(
    new moodle_url(
        '/local/subscriptions/styles/storefront_builder.css'
    )
);
$PAGE->requires->js_call_amd(
    'local_subscriptions/storefront_builder_drag_drop',
    'init'
);
$editlanguage = optional_param('editlang', current_language(), PARAM_ALPHANUMEXT);
$editlanguage = strtolower(explode('_', str_replace('-', '_', $editlanguage))[0]);
$pageurl = new moodle_url('/local/subscriptions/admin/commerce/products/storefront.php', ['sku' => $product->get_sku(), 'editlang' => $editlanguage]);

CrmPageConfigurator::configure($PAGE, $context, $pageurl, get_string('commerce_storefront_editor_title', 'local_subscriptions'), 'local-subscriptions-commerce-product-storefront-page');

$storefrontaction = optional_param('storefront_action', '', PARAM_ALPHANUMEXT);
if ($storefrontaction === 'export' && confirm_sesskey()) {
    $package = new CommerceStorefrontPackageService($context);
    $archivepath = $package->export($product);
    send_temp_file(
        $archivepath,
        clean_filename(strtolower($product->get_sku()) . '-storefront.cfrproduct')
    );
}
if ($storefrontaction === 'import' && data_submitted() && confirm_sesskey()) {
    if (
        !isset($_FILES['storefront_package'])
        || (int)($_FILES['storefront_package']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
        || empty($_FILES['storefront_package']['tmp_name'])
        || !is_uploaded_file((string)$_FILES['storefront_package']['tmp_name'])
    ) {
        throw new moodle_exception('commerce_storefront_package_invalid', 'local_subscriptions');
    }
    $package = new CommerceStorefrontPackageService($context);
    $metadata = $package->import((string)$_FILES['storefront_package']['tmp_name'], $product);
    $manager->save_metadata($product->get_sku(), $metadata);
    redirect(
        $pageurl,
        get_string('commerce_storefront_package_import_success', 'local_subscriptions'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}
if ($storefrontaction === 'reset' && data_submitted() && confirm_sesskey()) {
    $resetservice = new CommerceStorefrontResetService($contentfiles);
    $manager->save_metadata(
        $product->get_sku(),
        $resetservice->reset($product->get_metadata())
    );
    redirect(
        $pageurl,
        get_string('commerce_storefront_reset_success', 'local_subscriptions'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}


$localeaction = optional_param('locale_action', '', PARAM_ALPHANUMEXT);
if ($localeaction !== '' && data_submitted() && confirm_sesskey()) {
    $localetransfer = new CommerceStorefrontLocaleTransferService();
    $source = optional_param('locale_source', '', PARAM_ALPHANUMEXT);

    if ($localeaction === 'copy') {
        $updatedmetadata = $localetransfer->copy(
            $product->get_metadata(),
            $source,
            $editlanguage
        );
        $manager->save_metadata($product->get_sku(), $updatedmetadata);
        redirect(
            $pageurl,
            get_string('commerce_storefront_locale_copy_success', 'local_subscriptions'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }

    if ($localeaction === 'translate_preview') {
        $translationservice = CommerceStorefrontAiTranslationService::create();
        $preview = $translationservice->preview(
            $product->get_metadata(),
            $source,
            $editlanguage
        );
        $token = bin2hex(random_bytes(16));
        $SESSION->local_subscriptions_storefront_translation_previews =
            is_array($SESSION->local_subscriptions_storefront_translation_previews ?? null)
                ? $SESSION->local_subscriptions_storefront_translation_previews
                : [];
        $SESSION->local_subscriptions_storefront_translation_previews[$token] = [
            'sku' => $product->get_sku(),
            'userid' => (int)$USER->id,
            'created' => time(),
            'preview' => $preview,
        ];
        redirect(new moodle_url($pageurl, ['translation_preview' => $token]));
    }

    if (in_array($localeaction, ['translate_apply', 'translate_cancel'], true)) {
        $token = required_param('translation_preview', PARAM_ALPHANUMEXT);
        $previews = is_array($SESSION->local_subscriptions_storefront_translation_previews ?? null)
            ? $SESSION->local_subscriptions_storefront_translation_previews
            : [];
        $stored = $previews[$token] ?? null;
        if (
            !is_array($stored)
            || (string)($stored['sku'] ?? '') !== $product->get_sku()
            || (int)($stored['userid'] ?? 0) !== (int)$USER->id
            || (int)($stored['created'] ?? 0) < time() - HOURSECS
            || !is_array($stored['preview'] ?? null)
        ) {
            unset($SESSION->local_subscriptions_storefront_translation_previews[$token]);
            throw new moodle_exception(
                'commerce_storefront_ai_translation_preview_expired',
                'local_subscriptions'
            );
        }

        if ($localeaction === 'translate_cancel') {
            unset($SESSION->local_subscriptions_storefront_translation_previews[$token]);
            redirect($pageurl);
        }

        $translationservice = CommerceStorefrontAiTranslationService::create();
        $updatedmetadata = $translationservice->apply_preview(
            $product->get_metadata(),
            $stored['preview']
        );
        $manager->save_metadata($product->get_sku(), $updatedmetadata);
        unset($SESSION->local_subscriptions_storefront_translation_previews[$token]);
        redirect(
            $pageurl,
            get_string('commerce_storefront_ai_translation_applied', 'local_subscriptions'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }
}

if (data_submitted() && confirm_sesskey()) {
    $builderaction = optional_param('builder_action', '', PARAM_RAW_TRIMMED);
    $buildertype = optional_param('builder_type', '', PARAM_ALPHANUMEXT);
    $buildertemplate = optional_param('builder_template', '', PARAM_ALPHANUMEXT);
    $submitted = [
        'template' => required_param('storefront_template', PARAM_ALPHANUMEXT),
        'commerce_position' => optional_param('commerce_position', 'sidebar_sticky', PARAM_ALPHANUMEXT),
        'shell_mode' => optional_param('shell_mode', 'standard', PARAM_ALPHA),
        'show_header' => optional_param('show_header', 0, PARAM_BOOL),
        'show_footer' => optional_param('show_footer', 0, PARAM_BOOL),
        'product_header_mode' => optional_param('product_header_mode', 'automatic', PARAM_ALPHA),
        'global_zones' => optional_param(
            'storefront_global_zones',
            implode(',', CommerceStorefrontLayoutContract::global_zones()),
            PARAM_RAW_TRIMMED
        ),
        'theme' => optional_param('storefront_theme', 'default', PARAM_ALPHANUMEXT),
        'featured' => optional_param('storefront_featured', 0, PARAM_BOOL),
        'displayorder' => optional_param('storefront_displayorder', 1000, PARAM_INT),
        'badges' => optional_param_array('storefront_badges', [], PARAM_ALPHANUMEXT),
        'promotion_eur_compare' => optional_param('promotion_eur_compare', '', PARAM_RAW_TRIMMED),
        'promotion_eur_start' => optional_param('promotion_eur_start', '', PARAM_RAW_TRIMMED),
        'promotion_eur_end' => optional_param('promotion_eur_end', '', PARAM_RAW_TRIMMED),
        'promotion_rub_compare' => optional_param('promotion_rub_compare', '', PARAM_RAW_TRIMMED),
        'promotion_rub_start' => optional_param('promotion_rub_start', '', PARAM_RAW_TRIMMED),
        'promotion_rub_end' => optional_param('promotion_rub_end', '', PARAM_RAW_TRIMMED),
        'group' => optional_param('storefront_group', 'auto', PARAM_ALPHANUMEXT),
        'trust' => optional_param_array('storefront_trust', [], PARAM_ALPHANUMEXT),
        'quickfacts' => optional_param('storefront_quickfacts', '', PARAM_RAW),
        'recommendations' => optional_param(
            'storefront_recommendations',
            '',
            PARAM_RAW_TRIMMED
        ),
        'seo_title' => optional_param(
            'storefront_seo_title',
            '',
            PARAM_TEXT
        ),
        'seo_description' => optional_param(
            'storefront_seo_description',
            '',
            PARAM_TEXT
        ),
        'route_slug_fr' => optional_param('storefront_route_slug_fr', '', PARAM_RAW_TRIMMED),
        'route_slug_en' => optional_param('storefront_route_slug_en', '', PARAM_RAW_TRIMMED),
        'route_slug_ru' => optional_param('storefront_route_slug_ru', '', PARAM_RAW_TRIMMED),
        'showroom_key' => optional_param('storefront_showroom_key', '', PARAM_ALPHANUMEXT),
        'showroom_discoverymode' => optional_param(
            'storefront_showroom_discoverymode',
            'storefront',
            PARAM_ALPHA
        ),
        'showroom_showstorefrontcta' => optional_param(
            'storefront_showroom_showstorefrontcta',
            0,
            PARAM_BOOL
        ),
        'showroom_mediaitemid' => optional_param('storefront_showroom_mediaitemid', 0, PARAM_INT),
        'showroom_alt' => optional_param('storefront_showroom_alt', '', PARAM_TEXT),
    ];
    for ($index = 0; $index < CommerceStorefrontPageEditor::MAX_SECTIONS; $index++) {
        $submitted['section_id_' . $index] = optional_param(
            'section_id_' . $index,
            '',
            PARAM_ALPHANUMEXT
        );
        $submitted['section_visible_' . $index] = optional_param(
            'section_visible_' . $index,
            0,
            PARAM_BOOL
        );
        $submitted['section_order_' . $index] = optional_param(
            'section_order_' . $index,
            $index * 10,
            PARAM_INT
        );
        $submitted['section_style_' . $index] = optional_param(
            'section_style_' . $index,
            'default',
            PARAM_ALPHANUMEXT
        );
        $submitted['section_type_' . $index] = optional_param('section_type_' . $index, '', PARAM_ALPHANUMEXT);
        $submitted['section_title_' . $index] = optional_param('section_title_' . $index, '', PARAM_TEXT);
        $submitted['section_subtitle_' . $index] = optional_param('section_subtitle_' . $index, '', PARAM_TEXT);
        $submitted['section_content_' . $index] = optional_param(
            'section_content_' . $index,
            '',
            PARAM_RAW
        );
        $submitted['section_content_draft_' . $index] = optional_param(
            'section_content_draft_' . $index,
            0,
            PARAM_INT
        );
        $submitted['section_content_itemid_' . $index] = optional_param(
            'section_content_itemid_' . $index,
            0,
            PARAM_INT
        );
        $submitted['section_auxiliary_' . $index] = optional_param('section_auxiliary_' . $index, '', PARAM_RAW_TRIMMED);
        $submitted['section_alt_' . $index] = optional_param('section_alt_' . $index, '', PARAM_TEXT);
        $submitted['section_items_' . $index] = optional_param('section_items_' . $index, '', PARAM_RAW);
        $submitted['section_image_mode_' . $index] = optional_param(
            'section_image_mode_' . $index,
            'upload',
            PARAM_ALPHANUMEXT
        );
        $submitted['section_image_position_' . $index] = optional_param(
            'section_image_position_' . $index,
            'left',
            PARAM_ALPHA
        );
        $submitted['section_image_fit_' . $index] = optional_param(
            'section_image_fit_' . $index,
            'cover',
            PARAM_ALPHA
        );
        $submitted['section_column_ratio_' . $index] = optional_param(
            'section_column_ratio_' . $index,
            '50_50',
            PARAM_ALPHANUMEXT
        );
        $submitted['section_video_source_' . $index] = optional_param(
            'section_video_source_' . $index,
            'upload',
            PARAM_ALPHA
        );
        $submitted['section_video_ratio_' . $index] = optional_param(
            'section_video_ratio_' . $index,
            '16_9',
            PARAM_ALPHANUMEXT
        );
        $submitted['section_h5p_contentid_' . $index] = optional_param(
            'section_h5p_contentid_' . $index,
            0,
            PARAM_INT
        );
        $submitted['section_h5p_height_' . $index] = optional_param(
            'section_h5p_height_' . $index,
            640,
            PARAM_INT
        );
        $submitted['section_row_id_' . $index] = optional_param(
            'section_row_id_' . $index,
            'row-' . ($index + 1),
            PARAM_ALPHANUMEXT
        );
        $submitted['section_column_' . $index] = optional_param(
            'section_column_' . $index,
            1,
            PARAM_INT
        );
        $submitted['section_columns_' . $index] = optional_param(
            'section_columns_' . $index,
            1,
            PARAM_INT
        );
        $submitted['section_layout_ratio_' . $index] = optional_param(
            'section_layout_ratio_' . $index,
            '100',
            PARAM_ALPHANUMEXT
        );
        $submitted['section_width_' . $index] = optional_param(
            'section_width_' . $index,
            'contained',
            PARAM_ALPHA
        );
        $submitted['section_background_' . $index] = optional_param(
            'section_background_' . $index,
            'default',
            PARAM_ALPHA
        );
        $submitted['section_spacing_' . $index] = optional_param(
            'section_spacing_' . $index,
            'medium',
            PARAM_ALPHA
        );
        $submitted['section_alignment_' . $index] = optional_param(
            'section_alignment_' . $index,
            'stretch',
            PARAM_ALPHA
        );
        $submitted['section_content_alignment_' . $index] = optional_param(
            'section_content_alignment_' . $index,
            'left',
            PARAM_ALPHA
        );
        $submitted['section_presentation_' . $index] = optional_param(
            'section_presentation_' . $index,
            'default',
            PARAM_ALPHA
        );
        $submitted['section_animation_' . $index] = optional_param(
            'section_animation_' . $index,
            'none',
            PARAM_ALPHANUMEXT
        );
    }
    for (
        $index = 0;
        $index < CommerceStorefrontPageEditor::MAX_SECTIONS;
        $index++
    ) {
        $sectiontype = (string)(
            $submitted['section_type_' . $index] ?? ''
        );
        if (
            !in_array(
                $sectiontype,
                ['hero', 'rich_text', 'image_text', 'video', 'h5p', 'cta', 'features'],
                true
            )
        ) {
            continue;
        }

        $itemid = $contentfiles->ensure_item_id(
            (int)($submitted[
                'section_content_itemid_' . $index
            ] ?? 0)
        );
        $submitted['section_content_itemid_' . $index] = $itemid;
        $submitted['section_content_' . $index] =
            $contentfiles->save_editor(
                (int)($submitted[
                    'section_content_draft_' . $index
                ] ?? 0),
                $itemid,
                (string)($submitted[
                    'section_content_' . $index
                ] ?? '')
            );

        if (in_array($sectiontype, ['hero', 'image_text'], true)) {
            $contentfiles->store_uploaded_slot(
                $itemid,
                'image',
                'section_image_file_' . $index,
                ['png', 'jpg', 'jpeg', 'webp', 'gif']
            );
        }
        if ($sectiontype === 'video') {
            $contentfiles->store_uploaded_slot(
                $itemid,
                'video',
                'section_video_file_' . $index,
                ['mp4', 'webm', 'ogv']
            );
            $contentfiles->store_uploaded_slot(
                $itemid,
                'poster',
                'section_video_poster_' . $index,
                ['png', 'jpg', 'jpeg', 'webp']
            );
        }
        if ($sectiontype === 'h5p') {
            $contentfiles->store_uploaded_slot(
                $itemid,
                'h5p',
                'section_h5p_file_' . $index,
                ['h5p']
            );
        }
    }

    $showroomitemid = $showroommedia->ensure_item_id(
        (int)($submitted['showroom_mediaitemid'] ?? 0)
    );
    $submitted['showroom_mediaitemid'] = $showroomitemid;
    $showroommedia->store_upload(
        $showroomitemid,
        'storefront_showroom_file'
    );

    $updatedmetadata = $pageeditor->merge_submission(
        $product->get_metadata(),
        $submitted,
        $editlanguage
    );
    if ($builderaction !== '') {
        $updatedmetadata = $builderservice->apply(
            $updatedmetadata,
            $editlanguage,
            $builderaction,
            $buildertype,
            $buildertemplate
        );
    }
    $manager->save_metadata($product->get_sku(), $updatedmetadata);
    redirect($pageurl, get_string('changessaved'));
}

$definition = $pageeditor->definition_from_product(
    $product,
    $editlanguage
);
$rows = array_values(array_filter(
    $pageeditor->form_rows($product, $editlanguage),
    static fn(array $row): bool => trim((string)($row['type'] ?? '')) !== ''
));
$preferrededitor = editors_get_preferred_editor(FORMAT_HTML);
$statusservice = new CommerceStorefrontSectionStatusService($contentfiles);

foreach ($rows as $index => &$row) {
    $fieldname = 'section_content_' . $index;
    $prepared = $contentfiles->prepare_editor(
        $fieldname,
        (string)$row['content'],
        (int)($row['mediaitemid'] ?? 0)
    );
    $row['content'] = $prepared['content'];
    $row['contentdraftitemid'] = $prepared['draftitemid'];
    $row['mediaitemid'] = $prepared['itemid'];
    $row['hasricheditor'] = in_array(
        $row['type'],
        ['hero', 'rich_text', 'image_text', 'video', 'h5p', 'cta', 'features'],
        true
    );
    $row['imagemediadiagnostic'] =
        $contentfiles->slot_diagnostic(
            (int)$row['mediaitemid'],
            'image'
        );
    $row['videomediadiagnostic'] =
        $contentfiles->slot_diagnostic(
            (int)$row['mediaitemid'],
            'video'
        );
    $row['postermediadiagnostic'] =
        $contentfiles->slot_diagnostic(
            (int)$row['mediaitemid'],
            'poster'
        );
    $row['h5pmediadiagnostic'] =
        $contentfiles->slot_diagnostic(
            (int)$row['mediaitemid'],
            'h5p'
        );
    $row['editorialstatus'] = $statusservice->status($row);
    $row['ready'] =
        $row['editorialstatus']
        === CommerceStorefrontSectionStatusService::READY;

    if (!$row['hasricheditor']) {
        continue;
    }

    $preferrededitor->use_editor(
        $fieldname,
        $contentfiles->editor_options(),
        $contentfiles->filepicker_options(
            $prepared['draftitemid']
        )
    );
}
unset($row);

$previewurl = new moodle_url(
    '/local/subscriptions/storefront_product.php',
    ['sku' => $product->get_sku()]
);

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CommerceProductEditorNavigationRenderer::breadcrumb($product->get_name(), get_string('commerce_product_step_storefront', 'local_subscriptions'));
echo CommerceProductEditorNavigationRenderer::render($product, CommerceProductEditorNavigationRenderer::STOREFRONT);
echo CommerceProductPageHeaderRenderer::render(
    get_string('commerce_storefront_editor_title', 'local_subscriptions'),
    CommerceDesignSystemRenderer::page_intro(get_string('commerce_storefront_editor_intro', 'local_subscriptions')),
    html_writer::link($previewurl, get_string('commerce_storefront_preview', 'local_subscriptions'), ['class' => 'btn btn-outline-primary', 'target' => '_blank', 'rel' => 'noopener']),
    $product->get_name()
);

$languageoptions = [];
foreach ($factory->locale_service()->get_languages() as $code => $label) {
    $languageoptions[$code] = $label;
}
echo html_writer::start_tag('form', ['method' => 'get', 'class' => 'card card-body mb-4']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sku', 'value' => $product->get_sku()]);
echo html_writer::tag('label', get_string('commerce_storefront_edit_language', 'local_subscriptions'), ['for' => 'editlang', 'class' => 'form-label']);
echo html_writer::select($languageoptions, 'editlang', $editlanguage, false, ['id' => 'editlang', 'class' => 'form-select mb-3', 'onchange' => 'this.form.submit()']);
echo html_writer::tag('p', get_string('commerce_storefront_edit_language_help', 'local_subscriptions'), ['class' => 'form-text mb-0']);
echo html_writer::end_tag('form');

$localesourceoptions = $languageoptions;
unset($localesourceoptions[$editlanguage]);
$defaultsource = isset($localesourceoptions['ru']) ? 'ru' : (string)array_key_first($localesourceoptions);
$translationservice = CommerceStorefrontAiTranslationService::create();

echo html_writer::start_div('card card-body mb-4 commerce-storefront-locale-tools');
echo html_writer::tag(
    'h3',
    get_string('commerce_storefront_locale_tools_title', 'local_subscriptions'),
    ['class' => 'h5']
);
echo html_writer::tag(
    'p',
    get_string('commerce_storefront_locale_tools_help', 'local_subscriptions'),
    ['class' => 'text-muted']
);
echo html_writer::start_div('row g-3 align-items-end');

echo html_writer::start_div('col-lg-6');
echo html_writer::start_tag('form', ['method' => 'post', 'class' => 'card card-body h-100']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'locale_action', 'value' => 'copy']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'editlang', 'value' => $editlanguage]);
echo html_writer::tag('h4', get_string('commerce_storefront_locale_copy_title', 'local_subscriptions'), ['class' => 'h6']);
echo html_writer::tag('p', get_string('commerce_storefront_locale_copy_help', 'local_subscriptions'), ['class' => 'form-text']);
echo html_writer::tag('label', get_string('commerce_storefront_locale_source', 'local_subscriptions'), ['for' => 'commerce-storefront-copy-source', 'class' => 'form-label']);
echo html_writer::select($localesourceoptions, 'locale_source', $defaultsource, false, [
    'id' => 'commerce-storefront-copy-source',
    'class' => 'form-select mb-3',
]);
echo html_writer::tag(
    'button',
    '<i class="fa-solid fa-copy me-2" aria-hidden="true"></i>'
        . s(get_string('commerce_storefront_locale_copy_button', 'local_subscriptions')),
    [
        'type' => 'submit',
        'class' => 'btn btn-outline-primary align-self-start',
        'onclick' => "return confirm(" . json_encode(get_string('commerce_storefront_locale_copy_confirm', 'local_subscriptions')) . ");",
    ]
);
echo html_writer::end_tag('form');
echo html_writer::end_div();

echo html_writer::start_div('col-lg-6');
echo html_writer::start_tag('form', ['method' => 'post', 'class' => 'card card-body h-100']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'locale_action', 'value' => 'translate_preview']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'editlang', 'value' => $editlanguage]);
echo html_writer::tag('h4', get_string('commerce_storefront_ai_translation_title', 'local_subscriptions'), ['class' => 'h6']);
echo html_writer::tag('p', get_string('commerce_storefront_ai_translation_help', 'local_subscriptions'), ['class' => 'form-text']);
echo html_writer::tag('label', get_string('commerce_storefront_locale_source', 'local_subscriptions'), ['for' => 'commerce-storefront-translate-source', 'class' => 'form-label']);
echo html_writer::select($localesourceoptions, 'locale_source', $defaultsource, false, [
    'id' => 'commerce-storefront-translate-source',
    'class' => 'form-select mb-3',
]);
if (!$translationservice->available()) {
    echo html_writer::div(
        get_string('commerce_storefront_ai_translation_unavailable_help', 'local_subscriptions'),
        'alert alert-warning py-2'
    );
}
echo html_writer::tag(
    'button',
    '<i class="fa-solid fa-wand-magic-sparkles me-2" aria-hidden="true"></i>'
        . s(get_string('commerce_storefront_ai_translation_preview_button', 'local_subscriptions')),
    [
        'type' => 'submit',
        'class' => 'btn btn-primary align-self-start',
        'disabled' => $translationservice->available() ? null : 'disabled',
    ]
);
echo html_writer::end_tag('form');
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

$translationpreviewtoken = optional_param('translation_preview', '', PARAM_ALPHANUMEXT);
$storedtranslationpreview = null;
if ($translationpreviewtoken !== '') {
    $previews = is_array($SESSION->local_subscriptions_storefront_translation_previews ?? null)
        ? $SESSION->local_subscriptions_storefront_translation_previews
        : [];
    $candidate = $previews[$translationpreviewtoken] ?? null;
    if (
        is_array($candidate)
        && (string)($candidate['sku'] ?? '') === $product->get_sku()
        && (int)($candidate['userid'] ?? 0) === (int)$USER->id
        && (int)($candidate['created'] ?? 0) >= time() - HOURSECS
        && is_array($candidate['preview'] ?? null)
    ) {
        $storedtranslationpreview = $candidate['preview'];
    }
}

if (is_array($storedtranslationpreview)) {
    $changes = is_array($storedtranslationpreview['changes'] ?? null)
        ? $storedtranslationpreview['changes']
        : [];
    $changedcount = count(array_filter($changes, static fn(array $change): bool => !empty($change['changed'])));
    echo html_writer::start_div('card card-body mb-4 border-primary commerce-storefront-translation-preview');
    echo html_writer::tag('h3', get_string('commerce_storefront_ai_translation_preview_title', 'local_subscriptions'), ['class' => 'h5']);
    echo html_writer::tag(
        'p',
        get_string('commerce_storefront_ai_translation_preview_summary', 'local_subscriptions', (object)[
            'source' => strtoupper((string)$storedtranslationpreview['source']),
            'target' => strtoupper((string)$storedtranslationpreview['target']),
            'count' => $changedcount,
            'model' => (string)($storedtranslationpreview['model'] ?? ''),
        ]),
        ['class' => 'text-muted']
    );
    echo html_writer::start_div('commerce-storefront-translation-preview__changes');
    foreach ($changes as $change) {
        if (!is_array($change)) {
            continue;
        }
        echo html_writer::start_tag('details', ['class' => 'border rounded-3 p-3 mb-2']);
        echo html_writer::tag('summary', s((string)($change['id'] ?? '')), ['class' => 'fw-semibold']);
        echo html_writer::start_div('row g-3 mt-1');
        echo html_writer::div(
            html_writer::tag('strong', get_string('commerce_storefront_ai_translation_source_text', 'local_subscriptions'))
                . html_writer::tag('div', s(trim(strip_tags((string)($change['source'] ?? '')))), ['class' => 'small text-muted mt-1']),
            'col-lg-6'
        );
        echo html_writer::div(
            html_writer::tag('strong', get_string('commerce_storefront_ai_translation_target_text', 'local_subscriptions'))
                . html_writer::tag('div', s(trim(strip_tags((string)($change['translated'] ?? '')))), ['class' => 'small mt-1']),
            'col-lg-6'
        );
        echo html_writer::end_div();
        echo html_writer::end_tag('details');
    }
    echo html_writer::end_div();
    echo html_writer::start_div('d-flex flex-wrap gap-2 mt-3');
    foreach (['translate_apply' => 'commerce_storefront_ai_translation_apply', 'translate_cancel' => 'cancel'] as $action => $stringkey) {
        echo html_writer::start_tag('form', ['method' => 'post']);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'locale_action', 'value' => $action]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'editlang', 'value' => $editlanguage]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'translation_preview', 'value' => $translationpreviewtoken]);
        echo html_writer::tag('button', get_string($stringkey, $stringkey === 'cancel' ? 'moodle' : 'local_subscriptions'), [
            'type' => 'submit',
            'class' => $action === 'translate_apply' ? 'btn btn-primary' : 'btn btn-secondary',
        ]);
        echo html_writer::end_tag('form');
    }
    echo html_writer::end_div();
    echo html_writer::end_div();
}

echo html_writer::start_div('card card-body mb-4 commerce-storefront-transfer');
echo html_writer::tag(
    'h3',
    get_string('commerce_storefront_package_title', 'local_subscriptions'),
    ['class' => 'h5']
);
echo html_writer::tag(
    'p',
    get_string('commerce_storefront_package_help', 'local_subscriptions'),
    ['class' => 'text-muted']
);
echo html_writer::start_div('d-flex flex-wrap gap-3 align-items-end');
echo html_writer::link(
    new moodle_url($pageurl, [
        'storefront_action' => 'export',
        'sesskey' => sesskey(),
    ]),
    '<i class="fa-solid fa-file-export me-2" aria-hidden="true"></i>'
        . s(get_string('commerce_storefront_package_export', 'local_subscriptions')),
    ['class' => 'btn btn-outline-primary']
);
echo html_writer::start_tag('form', [
    'method' => 'post',
    'enctype' => 'multipart/form-data',
    'class' => 'd-flex flex-wrap gap-2 align-items-end flex-grow-1',
]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'storefront_action', 'value' => 'import']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'editlang', 'value' => $editlanguage]);
echo html_writer::div(
    html_writer::tag(
        'label',
        get_string('commerce_storefront_package_file', 'local_subscriptions'),
        ['for' => 'storefront_package', 'class' => 'form-label']
    )
        . html_writer::empty_tag('input', [
            'type' => 'file',
            'id' => 'storefront_package',
            'name' => 'storefront_package',
            'accept' => '.cfrproduct,application/zip',
            'class' => 'form-control',
            'required' => 'required',
        ]),
    'flex-grow-1'
);
echo html_writer::tag(
    'button',
    '<i class="fa-solid fa-file-import me-2" aria-hidden="true"></i>'
        . s(get_string('commerce_storefront_package_import', 'local_subscriptions')),
    ['type' => 'submit', 'class' => 'btn btn-primary']
);
echo html_writer::end_tag('form');
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('card card-body border-danger-subtle mb-4');
echo html_writer::tag(
    'h3',
    get_string('commerce_storefront_reset_title', 'local_subscriptions'),
    ['class' => 'h5 text-danger']
);
echo html_writer::tag(
    'p',
    get_string('commerce_storefront_reset_help', 'local_subscriptions'),
    ['class' => 'text-muted mb-3']
);
echo html_writer::tag(
    'button',
    get_string('commerce_storefront_reset_button', 'local_subscriptions'),
    [
        'type' => 'button',
        'class' => 'btn btn-outline-danger align-self-start',
        'onclick' => "document.getElementById('commerce-storefront-reset-dialog').showModal()",
    ]
);
echo html_writer::end_div();

echo html_writer::start_tag('dialog', [
    'id' => 'commerce-storefront-reset-dialog',
    'class' => 'commerce-storefront-reset-dialog',
    'aria-labelledby' => 'commerce-storefront-reset-dialog-title',
]);
echo html_writer::start_div('commerce-storefront-reset-dialog__content');
echo html_writer::tag(
    'h2',
    get_string('commerce_storefront_reset_confirm_title', 'local_subscriptions'),
    ['class' => 'h5', 'id' => 'commerce-storefront-reset-dialog-title']
);
echo html_writer::tag(
    'p',
    get_string('commerce_storefront_reset_confirm_help', 'local_subscriptions'),
    ['class' => 'mb-4']
);
echo html_writer::start_tag('form', ['method' => 'post']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'storefront_action', 'value' => 'reset']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'editlang', 'value' => $editlanguage]);
echo html_writer::start_div('d-flex justify-content-end gap-2');
echo html_writer::tag('button', get_string('cancel'), [
    'type' => 'button',
    'class' => 'btn btn-secondary',
    'onclick' => "document.getElementById('commerce-storefront-reset-dialog').close()",
]);
echo html_writer::tag(
    'button',
    get_string('commerce_storefront_reset_confirm_button', 'local_subscriptions'),
    ['type' => 'submit', 'class' => 'btn btn-danger']
);
echo html_writer::end_div();
echo html_writer::end_tag('form');
echo html_writer::end_div();
echo html_writer::end_tag('dialog');

echo html_writer::start_tag('form', [
    'method' => 'post',
    'class' => 'crm-commerce-editor-form commerce-storefront-admin-workspace',
    'enctype' => 'multipart/form-data',
    'data-region' => 'storefront-builder-form',
    'data-section-save-url' => (new moodle_url(
        '/local/subscriptions/admin/commerce/products/storefront_section_save.php'
    ))->out(false),
    'data-product-sku' => $product->get_sku(),
]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'editlang', 'value' => $editlanguage]);
echo html_writer::start_div('commerce-storefront-builder');
echo html_writer::start_tag('aside', ['class' => 'commerce-storefront-builder__sidebar']);

echo html_writer::start_div('card card-body mb-4');
echo html_writer::tag('h3', get_string('commerce_storefront_layout_title', 'local_subscriptions'), ['class' => 'h5']);
$templateoptions = [];
foreach (CommerceStorefrontPageEditor::templates() as $template) {
    $templateoptions[$template] = get_string('commerce_storefront_template_' . $template, 'local_subscriptions');
}

echo html_writer::start_div(
    'commerce-storefront-admin-layout card card-body mb-4'
);
echo html_writer::tag(
    'h3',
    get_string('commerce_storefront_shell_title', 'local_subscriptions'),
    ['class' => 'h5 mb-3']
);
echo html_writer::start_div('commerce-storefront-admin-layout__fields');

echo html_writer::start_div('commerce-storefront-admin-layout__field');
echo html_writer::tag(
    'label',
    get_string(
        'commerce_storefront_commerce_position',
        'local_subscriptions'
    ),
    [
        'for' => 'commerce_position',
        'class' => 'form-label',
    ]
);
echo html_writer::select(
    [
        'hero_integrated' => get_string(
            'commerce_storefront_commerce_position_hero',
            'local_subscriptions'
        ),
        'below_hero' => get_string(
            'commerce_storefront_commerce_position_below',
            'local_subscriptions'
        ),
        'sidebar_sticky' => get_string(
            'commerce_storefront_commerce_position_sidebar',
            'local_subscriptions'
        ),
        'after_intro' => get_string(
            'commerce_storefront_commerce_position_intro',
            'local_subscriptions'
        ),
        'page_bottom' => get_string(
            'commerce_storefront_commerce_position_bottom',
            'local_subscriptions'
        ),
        'none' => get_string(
            'commerce_storefront_commerce_position_none',
            'local_subscriptions'
        ),
    ],
    'commerce_position',
    $definition['commerce_position'],
    false,
    [
        'id' => 'commerce_position',
        'class' => 'form-select',
    ]
);
echo html_writer::end_div();

echo html_writer::start_div('commerce-storefront-admin-layout__field');
echo html_writer::tag(
    'label',
    get_string('commerce_storefront_product_header_mode', 'local_subscriptions'),
    ['for' => 'product_header_mode', 'class' => 'form-label']
);
echo html_writer::select(
    [
        'automatic' => get_string('commerce_storefront_product_header_automatic', 'local_subscriptions'),
        'builder' => get_string('commerce_storefront_product_header_builder', 'local_subscriptions'),
        'hidden' => get_string('commerce_storefront_product_header_hidden', 'local_subscriptions'),
    ],
    'product_header_mode',
    $definition['product_header_mode'],
    false,
    ['id' => 'product_header_mode', 'class' => 'form-select']
);
echo html_writer::tag(
    'div',
    get_string('commerce_storefront_product_header_help', 'local_subscriptions'),
    ['class' => 'form-text']
);
echo html_writer::end_div();

echo html_writer::start_div('commerce-storefront-admin-layout__field');
echo html_writer::tag(
    'label',
    get_string('commerce_storefront_shell_mode', 'local_subscriptions'),
    [
        'for' => 'shell_mode',
        'class' => 'form-label',
    ]
);
echo html_writer::select(
    [
        'standard' => get_string(
            'commerce_storefront_shell_standard',
            'local_subscriptions'
        ),
        'fullwidth' => get_string(
            'commerce_storefront_shell_fullwidth',
            'local_subscriptions'
        ),
        'landing' => get_string(
            'commerce_storefront_shell_landing',
            'local_subscriptions'
        ),
        'immersive' => get_string(
            'commerce_storefront_shell_immersive',
            'local_subscriptions'
        ),
    ],
    'shell_mode',
    $definition['shell_mode'],
    false,
    [
        'id' => 'shell_mode',
        'class' => 'form-select',
    ]
);
echo html_writer::end_div();

echo html_writer::start_tag('fieldset', [
    'class' => 'commerce-storefront-admin-layout__visibility',
]);
echo html_writer::tag(
    'legend',
    get_string(
        'commerce_storefront_layout_visibility',
        'local_subscriptions'
    ),
    ['class' => 'visually-hidden']
);
foreach ([
    'show_header' => 'commerce_storefront_show_header',
    'show_footer' => 'commerce_storefront_show_footer',
] as $field => $key) {
    echo html_writer::start_div('form-check');
    echo html_writer::checkbox(
        $field,
        1,
        !empty($definition[$field]),
        get_string($key, 'local_subscriptions'),
        [
            'id' => $field,
            'class' => 'form-check-input',
        ]
    );
    echo html_writer::end_div();
}
echo html_writer::end_tag('fieldset');

echo html_writer::start_div('commerce-storefront-global-layout mt-4');
echo html_writer::tag(
    'h4',
    get_string('commerce_storefront_global_zones_title', 'local_subscriptions'),
    ['class' => 'h6 mb-2']
);
echo html_writer::tag(
    'p',
    get_string('commerce_storefront_global_zones_help', 'local_subscriptions'),
    ['class' => 'form-text mb-3']
);
echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'storefront_global_zones',
    'value' => implode(',', $definition['global_zones']),
    'data-region' => 'storefront-global-zones-value',
]);
echo html_writer::start_div('commerce-storefront-global-zones', [
    'data-region' => 'storefront-global-zones',
]);
foreach ($definition['global_zones'] as $zone) {
    echo html_writer::tag(
        'div',
        '<i class="fa-solid fa-grip-vertical" aria-hidden="true"></i>'
            . '<span>' . s(get_string('commerce_storefront_global_zone_' . $zone, 'local_subscriptions')) . '</span>',
        [
            'class' => 'commerce-storefront-global-zone',
            'data-zone' => $zone,
            'draggable' => 'true',
            'tabindex' => '0',
        ]
    );
}
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::tag('label', get_string('commerce_storefront_template', 'local_subscriptions'), ['for' => 'storefront_template', 'class' => 'form-label']);
echo html_writer::select($templateoptions, 'storefront_template', $definition['template'], false, ['id' => 'storefront_template', 'class' => 'form-select mb-3']);
echo html_writer::tag('label', get_string('commerce_storefront_theme', 'local_subscriptions'), ['for' => 'storefront_theme', 'class' => 'form-label']);
echo html_writer::empty_tag('input', ['id' => 'storefront_theme', 'name' => 'storefront_theme', 'value' => $definition['theme'], 'class' => 'form-control', 'maxlength' => 32]);
echo html_writer::tag('div', get_string('commerce_storefront_theme_help', 'local_subscriptions'), ['class' => 'form-text']);
echo html_writer::end_div();

echo html_writer::start_div('card card-body mb-4');
echo html_writer::tag(
    'h3',
    get_string(
        'commerce_storefront_seo_title',
        'local_subscriptions'
    ),
    ['class' => 'h5']
);
echo html_writer::tag(
    'p',
    get_string(
        'commerce_storefront_seo_help',
        'local_subscriptions'
    ),
    ['class' => 'text-muted']
);
echo html_writer::tag(
    'label',
    get_string(
        'commerce_storefront_seo_page_title',
        'local_subscriptions'
    ),
    [
        'for' => 'storefront_seo_title',
        'class' => 'form-label',
    ]
);
echo html_writer::empty_tag('input', [
    'id' => 'storefront_seo_title',
    'name' => 'storefront_seo_title',
    'value' => $definition['seo_title'],
    'class' => 'form-control mb-3',
    'maxlength' => 120,
]);
echo html_writer::tag(
    'label',
    get_string(
        'commerce_storefront_seo_description',
        'local_subscriptions'
    ),
    [
        'for' => 'storefront_seo_description',
        'class' => 'form-label',
    ]
);
echo html_writer::tag(
    'textarea',
    s($definition['seo_description']),
    [
        'id' => 'storefront_seo_description',
        'name' => 'storefront_seo_description',
        'class' => 'form-control',
        'rows' => 3,
        'maxlength' => 320,
    ]
);
echo html_writer::tag(
    'div',
    get_string(
        'commerce_storefront_seo_description_help',
        'local_subscriptions'
    ),
    ['class' => 'form-text']
);
echo html_writer::end_div();

echo html_writer::start_div('card card-body mb-4');
echo html_writer::tag('h3', get_string('commerce_routes_product_title', 'local_subscriptions'), ['class' => 'h5']);
echo html_writer::tag('p', get_string('commerce_routes_product_help', 'local_subscriptions'), ['class' => 'text-muted']);
foreach (['fr', 'en', 'ru'] as $routelang) {
    echo html_writer::tag('label', get_string('commerce_routes_slug_' . $routelang, 'local_subscriptions'), [
        'for' => 'storefront_route_slug_' . $routelang,
        'class' => 'form-label',
    ]);
    echo html_writer::empty_tag('input', [
        'id' => 'storefront_route_slug_' . $routelang,
        'name' => 'storefront_route_slug_' . $routelang,
        'value' => $definition['route_slug_' . $routelang],
        'class' => 'form-control mb-3',
        'maxlength' => 120,
        'placeholder' => $routelang === 'fr' ? 'cours-a1' : '',
    ]);
}
echo html_writer::end_div();

$showroomdefinition = $showroommedia->definition(
    $product->get_metadata(),
    $editlanguage
);
$showroomoptions = ['' => get_string('none')];
foreach (CommerceShowroomRegistry::definitions() as $showroomkey => $registeredshowroom) {
    $showroomoptions[$showroomkey] = $showroomkey;
}
echo html_writer::start_div('card card-body mb-4 commerce-storefront-showroom-media');
echo html_writer::tag('h3', get_string('commerce_storefront_showroom_media_title', 'local_subscriptions'), ['class' => 'h5']);
echo html_writer::tag('p', get_string('commerce_storefront_showroom_media_help', 'local_subscriptions'), ['class' => 'text-muted']);
echo html_writer::tag('label', get_string('commerce_storefront_showroom_key', 'local_subscriptions'), ['for' => 'storefront_showroom_key', 'class' => 'form-label']);
echo html_writer::select($showroomoptions, 'storefront_showroom_key', $showroomdefinition['key'], false, ['id' => 'storefront_showroom_key', 'class' => 'form-select mb-3']);
echo html_writer::tag(
    'label',
    get_string('commerce_product_discovery_destination', 'local_subscriptions'),
    ['for' => 'storefront_showroom_discoverymode', 'class' => 'form-label']
);
echo html_writer::select(
    [
        'storefront' => get_string('commerce_product_discovery_storefront', 'local_subscriptions'),
        'showroom' => get_string('commerce_product_discovery_showroom', 'local_subscriptions'),
    ],
    'storefront_showroom_discoverymode',
    $definition['showroom_discoverymode'],
    false,
    ['id' => 'storefront_showroom_discoverymode', 'class' => 'form-select mb-2']
);
echo html_writer::tag(
    'div',
    get_string('commerce_product_discovery_help', 'local_subscriptions'),
    ['class' => 'form-text mb-3']
);
echo html_writer::start_div('form-check mb-3');
echo html_writer::empty_tag('input', [
    'type' => 'checkbox',
    'id' => 'storefront_showroom_showstorefrontcta',
    'name' => 'storefront_showroom_showstorefrontcta',
    'value' => 1,
    'class' => 'form-check-input',
    'checked' => $definition['showroom_showstorefrontcta'] ? 'checked' : null,
]);
echo html_writer::tag(
    'label',
    get_string('commerce_product_show_full_presentation_cta', 'local_subscriptions'),
    ['for' => 'storefront_showroom_showstorefrontcta', 'class' => 'form-check-label']
);
echo html_writer::tag(
    'div',
    get_string('commerce_product_show_full_presentation_cta_help', 'local_subscriptions'),
    ['class' => 'form-text']
);
echo html_writer::end_div();
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'storefront_showroom_mediaitemid', 'value' => $showroomdefinition['mediaitemid']]);
if ($showroomdefinition['hasimage']) {
    echo html_writer::div(
        html_writer::empty_tag('img', [
            'src' => $showroomdefinition['imageurl'],
            'alt' => $showroomdefinition['alt'],
            'class' => 'img-fluid rounded-4',
        ]),
        'commerce-storefront-showroom-media__preview mb-3'
    );
}
echo html_writer::tag('label', get_string('commerce_storefront_showroom_image', 'local_subscriptions'), ['for' => 'storefront_showroom_file', 'class' => 'form-label']);
echo html_writer::empty_tag('input', [
    'type' => 'file',
    'id' => 'storefront_showroom_file',
    'name' => 'storefront_showroom_file',
    'accept' => 'image/png,image/jpeg,image/webp,image/gif',
    'class' => 'form-control mb-3',
]);
echo html_writer::tag('label', get_string('commerce_storefront_showroom_alt', 'local_subscriptions'), ['for' => 'storefront_showroom_alt', 'class' => 'form-label']);
echo html_writer::empty_tag('input', [
    'type' => 'text',
    'id' => 'storefront_showroom_alt',
    'name' => 'storefront_showroom_alt',
    'value' => $showroomdefinition['alt'],
    'class' => 'form-control',
    'maxlength' => 255,
]);
echo html_writer::end_div();

echo html_writer::end_tag('aside');
echo html_writer::start_tag('main', ['class' => 'commerce-storefront-builder__main']);

echo html_writer::start_div('card card-body mb-4');
echo html_writer::tag('h3', get_string('commerce_storefront_merchandising_title', 'local_subscriptions'), ['class' => 'h5']);
echo html_writer::tag('p', get_string('commerce_storefront_merchandising_intro', 'local_subscriptions'), ['class' => 'text-muted']);
echo html_writer::start_div('row g-3');

echo html_writer::start_div('col-lg-4');
echo html_writer::start_div('form-check mt-4');
echo html_writer::empty_tag('input', [
    'type' => 'checkbox',
    'id' => 'storefront_featured',
    'name' => 'storefront_featured',
    'value' => 1,
    'class' => 'form-check-input',
    'checked' => $definition['featured'] ? 'checked' : null,
]);
echo html_writer::tag('label', get_string('commerce_storefront_featured_product', 'local_subscriptions'), [
    'for' => 'storefront_featured',
    'class' => 'form-check-label',
]);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('col-lg-4');
echo html_writer::tag('label', get_string('commerce_storefront_display_order', 'local_subscriptions'), ['for' => 'storefront_displayorder', 'class' => 'form-label']);
echo html_writer::empty_tag('input', [
    'type' => 'number',
    'id' => 'storefront_displayorder',
    'name' => 'storefront_displayorder',
    'value' => $definition['displayorder'],
    'min' => 0,
    'max' => 999999,
    'class' => 'form-control',
]);
echo html_writer::tag('div', get_string('commerce_storefront_display_order_help', 'local_subscriptions'), ['class' => 'form-text']);
echo html_writer::end_div();

echo html_writer::start_div('col-12');
echo html_writer::tag('div', get_string('commerce_storefront_badges', 'local_subscriptions'), ['class' => 'form-label']);
echo html_writer::start_div('row g-2');
foreach (CommerceStorefrontPageEditor::badges() as $badge) {
    $id = 'storefront_badge_' . $badge;
    echo html_writer::start_div('col-sm-6 col-lg-3');
    echo html_writer::start_div('form-check');
    echo html_writer::empty_tag('input', [
        'type' => 'checkbox',
        'id' => $id,
        'name' => 'storefront_badges[]',
        'value' => $badge,
        'class' => 'form-check-input',
        'checked' => in_array($badge, $definition['badges'], true) ? 'checked' : null,
    ]);
    echo html_writer::tag('label', get_string('commerce_storefront_badge_' . $badge, 'local_subscriptions'), [
        'for' => $id,
        'class' => 'form-check-label',
    ]);
    echo html_writer::end_div();
    echo html_writer::end_div();
}
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div();

echo html_writer::tag('hr', '', ['class' => 'my-4']);
echo html_writer::tag('h4', get_string('commerce_storefront_promotions_title', 'local_subscriptions'), ['class' => 'h6']);
echo html_writer::tag('p', get_string('commerce_storefront_promotions_help', 'local_subscriptions'), ['class' => 'text-muted small']);
foreach (['eur' => 'EUR', 'rub' => 'RUB'] as $key => $currencylabel) {
    echo html_writer::start_div('row g-3 mb-3');
    echo html_writer::start_div('col-lg-2 d-flex align-items-end');
    echo html_writer::tag('strong', $currencylabel, ['class' => 'pb-2']);
    echo html_writer::end_div();
    echo html_writer::start_div('col-lg-4');
    echo html_writer::tag('label', get_string('commerce_storefront_compare_price', 'local_subscriptions'), ['for' => 'promotion_' . $key . '_compare', 'class' => 'form-label']);
    echo html_writer::empty_tag('input', [
        'id' => 'promotion_' . $key . '_compare',
        'name' => 'promotion_' . $key . '_compare',
        'value' => $definition['promotion_' . $key . '_compare'],
        'class' => 'form-control',
        'inputmode' => 'decimal',
        'placeholder' => $key === 'eur' ? '199.00' : '19900.00',
    ]);
    echo html_writer::end_div();
    foreach (['start', 'end'] as $datefield) {
        echo html_writer::start_div('col-lg-3');
        echo html_writer::tag('label', get_string('commerce_storefront_promotion_' . $datefield, 'local_subscriptions'), ['for' => 'promotion_' . $key . '_' . $datefield, 'class' => 'form-label']);
        echo html_writer::empty_tag('input', [
            'type' => 'date',
            'id' => 'promotion_' . $key . '_' . $datefield,
            'name' => 'promotion_' . $key . '_' . $datefield,
            'value' => $definition['promotion_' . $key . '_' . $datefield],
            'class' => 'form-control',
        ]);
        echo html_writer::end_div();
    }
    echo html_writer::end_div();
}
echo html_writer::end_div();

echo html_writer::start_div('card card-body mb-4');
echo html_writer::tag('h3', get_string('commerce_storefront_experience_title', 'local_subscriptions'), ['class' => 'h5']);
echo html_writer::tag('p', get_string('commerce_storefront_experience_intro', 'local_subscriptions'), ['class' => 'text-muted']);
echo html_writer::start_div('row g-3');

echo html_writer::start_div('col-lg-4');
echo html_writer::tag('label', get_string('commerce_storefront_group', 'local_subscriptions'), ['for' => 'storefront_group', 'class' => 'form-label']);
$groupoptions = [];
foreach (CommerceStorefrontPageEditor::groups() as $group) {
    $groupoptions[$group] = get_string('commerce_storefront_group_' . $group, 'local_subscriptions');
}
echo html_writer::select($groupoptions, 'storefront_group', $definition['group'], false, ['id' => 'storefront_group', 'class' => 'form-select']);
echo html_writer::end_div();

echo html_writer::start_div('col-lg-8');
echo html_writer::tag('div', get_string('commerce_storefront_trust_title', 'local_subscriptions'), ['class' => 'form-label']);
echo html_writer::start_div('row g-2');
foreach (CommerceStorefrontPageEditor::trust_items() as $trustitem) {
    $id = 'storefront_trust_' . $trustitem;
    echo html_writer::start_div('col-sm-6');
    echo html_writer::start_div('form-check');
    echo html_writer::empty_tag('input', [
        'type' => 'checkbox', 'id' => $id, 'name' => 'storefront_trust[]', 'value' => $trustitem,
        'class' => 'form-check-input', 'checked' => in_array($trustitem, $definition['trust'], true) ? 'checked' : null,
    ]);
    echo html_writer::tag('label', get_string('commerce_storefront_trust_' . $trustitem, 'local_subscriptions'), ['for' => $id, 'class' => 'form-check-label']);
    echo html_writer::end_div();
    echo html_writer::end_div();
}
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('col-12');
echo html_writer::tag('label', get_string('commerce_storefront_quickfacts', 'local_subscriptions'), ['for' => 'storefront_quickfacts', 'class' => 'form-label']);
echo html_writer::tag('textarea', s($definition['quickfacts']), ['id' => 'storefront_quickfacts', 'name' => 'storefront_quickfacts', 'class' => 'form-control font-monospace', 'rows' => 5]);
echo html_writer::tag('div', get_string('commerce_storefront_quickfacts_help', 'local_subscriptions'), ['class' => 'form-text']);
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_div();

$typeoptions = ['' => get_string('commerce_storefront_section_empty', 'local_subscriptions')];
foreach (CommerceStorefrontPageEditor::section_types() as $type) {
    $typeoptions[$type] = get_string('commerce_storefront_section_' . $type, 'local_subscriptions');
}

echo html_writer::start_div('commerce-storefront-preview-toolbar card card-body mb-4');
echo html_writer::start_div('d-flex flex-wrap justify-content-between align-items-center gap-3');
echo html_writer::div(
    html_writer::tag('h3', get_string('commerce_storefront_responsive_preview', 'local_subscriptions'), ['class' => 'h5 mb-1'])
    . html_writer::tag('p', get_string('commerce_storefront_responsive_preview_help', 'local_subscriptions'), ['class' => 'text-muted mb-0']),
    ''
);
echo html_writer::start_div('btn-group', [
    'role' => 'group',
    'aria-label' => get_string('commerce_storefront_responsive_preview', 'local_subscriptions'),
    'data-region' => 'storefront-preview-switcher',
]);
foreach (['desktop' => 'fa-desktop', 'tablet' => 'fa-tablet-screen-button', 'mobile' => 'fa-mobile-screen-button'] as $device => $icon) {
    echo html_writer::tag('button',
        '<i class="fa-solid ' . $icon . ' me-2" aria-hidden="true"></i>'
            . get_string('commerce_storefront_preview_' . $device, 'local_subscriptions'),
        [
            'type' => 'button',
            'class' => 'btn btn-outline-secondary' . ($device === 'desktop' ? ' active' : ''),
            'data-preview-device' => $device,
            'aria-pressed' => $device === 'desktop' ? 'true' : 'false',
        ]
    );
}
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('commerce-storefront-template-picker card card-body mb-4');
echo html_writer::tag('h3', get_string('commerce_storefront_composer_templates', 'local_subscriptions'), ['class' => 'h5 mb-1']);
echo html_writer::tag('p', get_string('commerce_storefront_composer_templates_help', 'local_subscriptions'), ['class' => 'text-muted mb-3']);
echo html_writer::start_div('d-flex flex-wrap gap-2 align-items-end');
$templateoptions = [];
foreach (CommerceStorefrontComposerTemplateService::TEMPLATES as $composertemplate) {
    $templateoptions[$composertemplate] = get_string('commerce_storefront_composer_template_' . $composertemplate, 'local_subscriptions');
}
echo html_writer::div(
    html_writer::tag('label', get_string('commerce_storefront_composer_template', 'local_subscriptions'), ['for' => 'builder_template', 'class' => 'form-label'])
        . html_writer::select($templateoptions, 'builder_template', '', false, ['id' => 'builder_template', 'class' => 'form-select']),
    'flex-grow-1'
);
echo html_writer::tag('button', '<i class="fa-solid fa-wand-magic-sparkles me-2" aria-hidden="true"></i>' . get_string('commerce_storefront_composer_template_insert', 'local_subscriptions'), [
    'type' => 'submit',
    'name' => 'builder_action',
    'value' => 'apply_template',
    'class' => 'btn btn-outline-primary',
]);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('commerce-storefront-builder__toolbar card card-body mb-4');
echo html_writer::tag('h3', get_string('commerce_storefront_builder_sections', 'local_subscriptions'), ['class' => 'h5 mb-1']);
echo html_writer::tag('p', get_string('commerce_storefront_builder_sections_help', 'local_subscriptions'), ['class' => 'text-muted mb-3']);
echo html_writer::start_div('d-flex flex-wrap gap-2 align-items-end');
echo html_writer::div(
    html_writer::tag('label', get_string('commerce_storefront_builder_add', 'local_subscriptions'), ['class' => 'form-label'])
    . html_writer::select($typeoptions, 'builder_type', '', false, ['class' => 'form-select']),
    'flex-grow-1'
);
echo html_writer::tag('button', '<i class="fa-solid fa-plus me-2" aria-hidden="true"></i>' . get_string('commerce_storefront_builder_add_button', 'local_subscriptions'), [
    'type' => 'submit', 'name' => 'builder_action', 'value' => 'add', 'class' => 'btn btn-primary'
]);
echo html_writer::end_div();
echo html_writer::tag(
    'p',
    get_string(
        'commerce_storefront_builder_drag_help',
        'local_subscriptions'
    ),
    ['class' => 'form-text mt-3 mb-0']
);
echo html_writer::div(
    '',
    'visually-hidden',
    [
        'data-region' => 'storefront-builder-live',
        'aria-live' => 'polite',
        'aria-atomic' => 'true',
    ]
);
echo html_writer::end_div();

echo html_writer::start_div('commerce-storefront-preview-canvas commerce-storefront-preview-canvas--desktop', [
    'data-region' => 'storefront-preview-canvas',
    'data-preview-device' => 'desktop',
]);
echo html_writer::start_div(
    'commerce-storefront-section-list',
    ['data-region' => 'storefront-section-list']
);

foreach ($rows as $index => $row) {
    $sectionlabel = $typeoptions[$row['type']] ?? $row['type'];
    $editorialstatus = (string)(
        $row['editorialstatus']
        ?? CommerceStorefrontSectionStatusService::EMPTY
    );
    $ready =
        $editorialstatus
        === CommerceStorefrontSectionStatusService::READY;
    $attention =
        $editorialstatus
        === CommerceStorefrontSectionStatusService::ATTENTION;
    $statusstring = $ready
        ? 'commerce_storefront_builder_ready'
        : (
            $attention
                ? 'commerce_storefront_builder_attention'
                : 'commerce_storefront_builder_empty_status'
        );
    $statusclass = $ready
        ? 'text-bg-success'
        : ($attention ? 'text-bg-warning' : 'text-bg-secondary');
    echo html_writer::start_tag('details', [
        'class' => 'commerce-storefront-section-card mb-3',
        'open' => $index === 0 ? 'open' : null,
        'draggable' => 'false',
        'data-region' => 'storefront-section-card',
        'data-section-index' => $index,
        'data-section-label' => $sectionlabel
            . ' — '
            . (
                $row['title']
                    ?: get_string(
                        'commerce_storefront_builder_untitled',
                        'local_subscriptions'
                    )
            ),
    ]);
    echo html_writer::start_tag('summary', ['class' => 'commerce-storefront-section-card__summary']);
    echo html_writer::span(
        '<i class="fa-solid fa-grip-vertical me-2" '
            . 'aria-hidden="true"></i>'
            . s($sectionlabel),
        'commerce-storefront-section-card__type '
            . 'commerce-storefront-section-card__drag-handle',
        [
            'role' => 'button',
            'tabindex' => '0',
            'data-drag-handle' => '1',
            'aria-label' => get_string(
                'commerce_storefront_builder_drag_handle',
                'local_subscriptions',
                $sectionlabel
            ),
            'title' => get_string(
                'commerce_storefront_builder_drag_handle',
                'local_subscriptions',
                $sectionlabel
            ),
        ]
    );
    echo html_writer::span(s($row['title'] ?: get_string('commerce_storefront_builder_untitled', 'local_subscriptions')), 'commerce-storefront-section-card__name');
    echo html_writer::span(
        get_string($statusstring, 'local_subscriptions'),
        'badge ' . $statusclass,
        ['data-section-readiness' => '1']
    );
    echo html_writer::end_tag('summary');
    echo html_writer::start_div('commerce-storefront-section-card__body');
    echo html_writer::start_div('commerce-storefront-section-card__actions');
    echo html_writer::tag(
        'button',
        '<i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>'
            . '<span class="visually-hidden">'
            . s(get_string('commerce_storefront_section_save', 'local_subscriptions'))
            . '</span>',
        [
            'type' => 'button',
            'class' => 'btn btn-sm btn-outline-primary',
            'data-save-section' => '1',
            'title' => get_string('commerce_storefront_section_save', 'local_subscriptions'),
            'aria-label' => get_string('commerce_storefront_section_save', 'local_subscriptions'),
        ]
    );
    echo html_writer::span('', 'commerce-storefront-section-card__save-status', [
        'data-section-save-status' => '1',
        'aria-live' => 'polite',
    ]);
    foreach ([
        'first' => 'fa-angles-up', 'up' => 'fa-arrow-up', 'down' => 'fa-arrow-down', 'last' => 'fa-angles-down',
        'toggle' => $row['visible'] ? 'fa-eye-slash' : 'fa-eye', 'duplicate' => 'fa-copy', 'delete' => 'fa-trash-can',
    ] as $action => $icon) {
        echo html_writer::tag('button', '<i class="fa-solid ' . $icon . '" aria-hidden="true"></i><span class="visually-hidden">' . s(get_string('commerce_storefront_builder_action_' . $action, 'local_subscriptions')) . '</span>', [
            'type' => 'submit',
            'name' => 'builder_action',
            'value' => $action . ':' . $index,
            'data-builder-command' => $action,
            'class' => 'btn btn-sm '
                . (
                    $action === 'delete'
                        ? 'btn-outline-danger'
                        : 'btn-outline-secondary'
                ),
            'title' => get_string('commerce_storefront_builder_action_' . $action, 'local_subscriptions'),
        ]);
    }
    echo html_writer::end_div();
    echo html_writer::start_div('commerce-storefront-section-card__preview commerce-storefront-section-card__preview--' . $row['type'], [
        'data-section-preview' => '1',
    ]);
    if ($row['type'] === 'image_text' && !empty($row['imagemediadiagnostic']['url'])) {
        echo html_writer::empty_tag('img', [
            'src' => $row['imagemediadiagnostic']['url'],
            'alt' => s((string)($row['alt'] ?? $row['title'] ?? '')),
            'loading' => 'lazy',
        ]);
    } else if ($row['type'] === 'video' && !empty($row['videomediadiagnostic']['url'])) {
        echo html_writer::tag('video', '', [
            'src' => $row['videomediadiagnostic']['url'],
            'controls' => 'controls',
            'preload' => 'metadata',
        ]);
    } else if ($row['type'] === 'h5p' && !empty($row['h5pmediadiagnostic'])) {
        echo html_writer::div(
            html_writer::tag('i', '', ['class' => 'fa-solid fa-puzzle-piece', 'aria-hidden' => 'true'])
                . html_writer::span(s((string)$row['h5pmediadiagnostic']['filename'])),
            'commerce-storefront-section-card__preview-file'
        );
    } else if (trim(strip_tags((string)$row['content'])) !== '') {
        echo html_writer::div(
            shorten_text(trim(strip_tags((string)$row['content'])), 180),
            'commerce-storefront-section-card__preview-excerpt'
        );
    } else {
        echo html_writer::tag('strong', s($sectionlabel));
        echo html_writer::tag('span', s($row['title'] ?: get_string('commerce_storefront_builder_untitled', 'local_subscriptions')));
    }
    echo html_writer::end_div();

    $layout = $row['layout'];
    echo html_writer::start_div('commerce-storefront-composer-layout mb-4', [
        'data-region' => 'storefront-layout-controls',
    ]);
    echo html_writer::tag('h4', get_string('commerce_storefront_composer_layout', 'local_subscriptions'), ['class' => 'h6 mb-3']);
    echo html_writer::start_div('row g-3');
    $layoutselects = [
        ['section_columns_' . $index, 'commerce_storefront_composer_columns', [1 => '1', 2 => '2', 3 => '3'], $layout['columns']],
        ['section_layout_ratio_' . $index, 'commerce_storefront_composer_ratio', [
            '100' => '100%', '50_50' => '50 / 50', '40_60' => '40 / 60', '60_40' => '60 / 40',
            '33_67' => '33 / 67', '67_33' => '67 / 33', '33_33_33' => '33 / 33 / 33',
        ], $layout['ratio']],
        ['section_column_' . $index, 'commerce_storefront_composer_column', [1 => '1', 2 => '2', 3 => '3'], $layout['column']],
        ['section_width_' . $index, 'commerce_storefront_composer_width', [
            'contained' => get_string('commerce_storefront_composer_width_contained', 'local_subscriptions'),
            'wide' => get_string('commerce_storefront_composer_width_wide', 'local_subscriptions'),
            'full' => get_string('commerce_storefront_composer_width_full', 'local_subscriptions'),
        ], $layout['width']],
        ['section_background_' . $index, 'commerce_storefront_composer_background', [
            'default' => get_string('commerce_storefront_composer_background_default', 'local_subscriptions'),
            'soft' => get_string('commerce_storefront_composer_background_soft', 'local_subscriptions'),
            'accent' => get_string('commerce_storefront_composer_background_accent', 'local_subscriptions'),
            'contrast' => get_string('commerce_storefront_composer_background_contrast', 'local_subscriptions'),
            'transparent' => get_string('commerce_storefront_composer_background_transparent', 'local_subscriptions'),
        ], $layout['background']],
        ['section_spacing_' . $index, 'commerce_storefront_composer_spacing', [
            'none' => get_string('commerce_storefront_composer_spacing_none', 'local_subscriptions'),
            'small' => get_string('commerce_storefront_composer_spacing_small', 'local_subscriptions'),
            'medium' => get_string('commerce_storefront_composer_spacing_medium', 'local_subscriptions'),
            'large' => get_string('commerce_storefront_composer_spacing_large', 'local_subscriptions'),
        ], $layout['spacing']],
        ['section_alignment_' . $index, 'commerce_storefront_composer_alignment', [
            'start' => get_string('commerce_storefront_composer_alignment_start', 'local_subscriptions'),
            'center' => get_string('commerce_storefront_composer_alignment_center', 'local_subscriptions'),
            'end' => get_string('commerce_storefront_composer_alignment_end', 'local_subscriptions'),
            'stretch' => get_string('commerce_storefront_composer_alignment_stretch', 'local_subscriptions'),
        ], $layout['alignment']],
    ];
    foreach ($layoutselects as [$name, $stringkey, $options, $selected]) {
        echo html_writer::start_div('col-md-6 col-xl-3');
        echo html_writer::tag('label', get_string($stringkey, 'local_subscriptions'), ['for' => $name, 'class' => 'form-label']);
        echo html_writer::select($options, $name, $selected, false, [
            'id' => $name,
            'class' => 'form-select',
            'data-composer-control' => '1',
        ]);
        echo html_writer::end_div();
    }
    echo html_writer::start_div('col-md-6 col-xl-3');
    echo html_writer::tag('label', get_string('commerce_storefront_composer_row', 'local_subscriptions'), ['for' => 'section_row_id_' . $index, 'class' => 'form-label']);
    echo html_writer::empty_tag('input', [
        'id' => 'section_row_id_' . $index,
        'name' => 'section_row_id_' . $index,
        'value' => $layout['rowid'],
        'class' => 'form-control',
        'maxlength' => 64,
        'data-composer-control' => '1',
    ]);
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::tag('div', get_string('commerce_storefront_composer_layout_help', 'local_subscriptions'), ['class' => 'form-text mt-2']);
    echo html_writer::start_div('row g-3 mt-1');
    echo html_writer::start_div('col-md-4');
    echo html_writer::tag('label', get_string('commerce_storefront_premium_presentation', 'local_subscriptions'), ['for' => 'section_presentation_' . $index, 'class' => 'form-label']);
    echo html_writer::select([
        'default' => get_string('commerce_storefront_premium_presentation_default', 'local_subscriptions'),
        'split' => get_string('commerce_storefront_premium_presentation_split', 'local_subscriptions'),
        'overlay' => get_string('commerce_storefront_premium_presentation_overlay', 'local_subscriptions'),
        'cards' => get_string('commerce_storefront_premium_presentation_cards', 'local_subscriptions'),
        'carousel' => get_string('commerce_storefront_premium_presentation_carousel', 'local_subscriptions'),
        'masonry' => get_string('commerce_storefront_premium_presentation_masonry', 'local_subscriptions'),
        'timeline' => get_string('commerce_storefront_premium_presentation_timeline', 'local_subscriptions'),
        'comparison' => get_string('commerce_storefront_premium_presentation_comparison', 'local_subscriptions'),
        'premium' => get_string('commerce_storefront_premium_presentation_premium', 'local_subscriptions'),
        'statement' => get_string('commerce_storefront_premium_presentation_statement', 'local_subscriptions'),
        'feature' => get_string('commerce_storefront_premium_presentation_feature', 'local_subscriptions'),
        'commerce' => get_string('commerce_storefront_premium_presentation_commerce', 'local_subscriptions'),
    ], 'section_presentation_' . $index, $row['presentation'], false, ['id' => 'section_presentation_' . $index, 'class' => 'form-select']);
    echo html_writer::end_div();
    echo html_writer::start_div('col-md-4');
    echo html_writer::tag(
        'label',
        get_string('commerce_storefront_content_alignment', 'local_subscriptions'),
        ['for' => 'section_content_alignment_' . $index, 'class' => 'form-label']
    );
    echo html_writer::select([
        'left' => get_string('commerce_storefront_content_alignment_left', 'local_subscriptions'),
        'center' => get_string('commerce_storefront_content_alignment_center', 'local_subscriptions'),
        'right' => get_string('commerce_storefront_content_alignment_right', 'local_subscriptions'),
    ], 'section_content_alignment_' . $index, $row['contentalignment'], false, [
        'id' => 'section_content_alignment_' . $index,
        'class' => 'form-select',
        'data-composer-control' => '1',
    ]);
    echo html_writer::end_div();
    echo html_writer::start_div('col-md-4');
    echo html_writer::tag('label', get_string('commerce_storefront_premium_animation', 'local_subscriptions'), ['for' => 'section_animation_' . $index, 'class' => 'form-label']);
    echo html_writer::select([
        'none' => get_string('commerce_storefront_premium_animation_none', 'local_subscriptions'),
        'fade' => get_string('commerce_storefront_premium_animation_fade', 'local_subscriptions'),
        'slide_up' => get_string('commerce_storefront_premium_animation_slide_up', 'local_subscriptions'),
        'zoom' => get_string('commerce_storefront_premium_animation_zoom', 'local_subscriptions'),
    ], 'section_animation_' . $index, $row['animation'], false, ['id' => 'section_animation_' . $index, 'class' => 'form-select']);
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();

    echo html_writer::start_div('row g-3');

    echo html_writer::start_div('col-lg-3');
    echo html_writer::tag(
        'label',
        get_string(
            'commerce_storefront_section_id',
            'local_subscriptions'
        ),
        [
            'for' => 'section_id_' . $index,
            'class' => 'form-label',
        ]
    );
    echo html_writer::empty_tag('input', [
        'id' => 'section_id_' . $index,
        'name' => 'section_id_' . $index,
        'value' => $row['id'],
        'class' => 'form-control',
        'maxlength' => 64,
    ]);
    echo html_writer::end_div();

    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'section_order_' . $index,
        'value' => $index * 10,
        'data-section-order' => '1',
    ]);

    echo html_writer::start_div('col-lg-3');
    echo html_writer::tag(
        'label',
        get_string(
            'commerce_storefront_section_style',
            'local_subscriptions'
        ),
        [
            'for' => 'section_style_' . $index,
            'class' => 'form-label',
        ]
    );
    $styleoptions = [];
    foreach (
        \local_subscriptions\commerce\storefront\page\CommerceStorefrontSectionSchema::STYLES
        as $style
    ) {
        $styleoptions[$style] = get_string(
            'commerce_storefront_section_style_' . $style,
            'local_subscriptions'
        );
    }
    echo html_writer::select(
        $styleoptions,
        'section_style_' . $index,
        $row['style'],
        false,
        [
            'id' => 'section_style_' . $index,
            'class' => 'form-select',
        ]
    );
    echo html_writer::end_div();

    echo html_writer::start_div('col-lg-4 d-flex align-items-end');
    echo html_writer::start_div('form-check mb-2');
    echo html_writer::empty_tag('input', [
        'type' => 'checkbox',
        'id' => 'section_visible_' . $index,
        'name' => 'section_visible_' . $index,
        'value' => 1,
        'class' => 'form-check-input',
        'checked' => $row['visible'] ? 'checked' : null,
    ]);
    echo html_writer::tag(
        'label',
        get_string(
            'commerce_storefront_section_visible',
            'local_subscriptions'
        ),
        [
            'for' => 'section_visible_' . $index,
            'class' => 'form-check-label',
        ]
    );
    echo html_writer::end_div();
    echo html_writer::end_div();

    $fields = [
        ['col-lg-4', 'section_type_' . $index, get_string('commerce_storefront_section_type', 'local_subscriptions'), 'select'],
        ['col-lg-4', 'section_title_' . $index, get_string('commerce_storefront_section_title', 'local_subscriptions'), 'text'],
        ['col-lg-4', 'section_subtitle_' . $index, get_string('commerce_storefront_section_subtitle', 'local_subscriptions'), 'text'],
    ];
    foreach ($fields as [$column, $name, $label, $kind]) {
        echo html_writer::start_div($column);
        echo html_writer::tag('label', $label, ['for' => $name, 'class' => 'form-label']);
        if ($kind === 'select') {
            echo html_writer::select($typeoptions, $name, $row['type'], false, ['id' => $name, 'class' => 'form-select']);
        } else {
            $key = str_contains($name, 'subtitle') ? 'subtitle' : 'title';
            echo html_writer::empty_tag('input', ['id' => $name, 'name' => $name, 'value' => $row[$key], 'class' => 'form-control']);
        }
        echo html_writer::end_div();
    }

    echo html_writer::start_div('col-12');
    echo html_writer::tag(
        'label',
        get_string(
            'commerce_storefront_section_content',
            'local_subscriptions'
        ),
        [
            'for' => 'section_content_' . $index,
            'class' => 'form-label',
        ]
    );
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'section_content_draft_' . $index,
        'value' => (int)$row['contentdraftitemid'],
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'section_content_itemid_' . $index,
        'value' => (int)$row['mediaitemid'],
    ]);
    echo html_writer::tag(
        'textarea',
        $row['content'],
        [
            'id' => 'section_content_' . $index,
            'name' => 'section_content_' . $index,
            'class' => 'form-control',
            'rows' => 8,
        ]
    );
    echo html_writer::tag(
        'div',
        $row['hasricheditor']
            ? get_string(
                'commerce_storefront_rich_text_editor_help',
                'local_subscriptions'
            )
                . ' '
                . get_string(
                    'commerce_storefront_repository_picker_help',
                    'local_subscriptions'
                )
            : get_string(
                'commerce_storefront_section_content_help',
                'local_subscriptions'
            ),
        [
            'class' => $row['hasricheditor']
                ? 'form-text text-success'
                : 'form-text',
        ]
    );
    echo html_writer::end_div();

    if (in_array($row['type'], ['hero', 'image_text'], true)) {
        echo html_writer::start_div(
            'col-12 commerce-storefront-media-config'
        );
        echo html_writer::tag(
            'h4',
            get_string(
                'commerce_storefront_image_settings',
                'local_subscriptions'
            ),
            ['class' => 'h6']
        );
        echo html_writer::start_div('row g-3');
        echo html_writer::div(
            html_writer::tag(
                'label',
                get_string(
                    'commerce_storefront_image_upload',
                    'local_subscriptions'
                ),
                ['class' => 'form-label']
            )
            . html_writer::empty_tag('input', [
                'type' => 'file',
                'name' => 'section_image_file_' . $index,
                'accept' => '.png,.jpg,.jpeg,.webp,.gif',
                'class' => 'form-control',
            ]),
            'col-lg-6'
        );
        if ($row['type'] === 'hero') {
            echo html_writer::div(
                html_writer::tag(
                    'label',
                    get_string('commerce_storefront_hero_layout', 'local_subscriptions'),
                    ['class' => 'form-label']
                )
                . html_writer::select(
                    [
                        'text_media' => get_string('commerce_storefront_hero_layout_text_media', 'local_subscriptions'),
                        'media_text' => get_string('commerce_storefront_hero_layout_media_text', 'local_subscriptions'),
                        'stacked' => get_string('commerce_storefront_hero_layout_stacked', 'local_subscriptions'),
                        'overlay' => get_string('commerce_storefront_hero_layout_overlay', 'local_subscriptions'),
                    ],
                    'section_hero_layout_' . $index,
                    $row['herolayout'],
                    false,
                    ['class' => 'form-select']
                ),
                'col-lg-4'
            );
            echo html_writer::div(
                html_writer::tag(
                    'label',
                    get_string('commerce_storefront_hero_ratio', 'local_subscriptions'),
                    ['class' => 'form-label']
                )
                . html_writer::select(
                    [
                        '50_50' => '50 / 50',
                        '55_45' => '55 / 45',
                        '60_40' => '60 / 40',
                        '45_55' => '45 / 55',
                    ],
                    'section_hero_ratio_' . $index,
                    $row['heroratio'],
                    false,
                    ['class' => 'form-select']
                ),
                'col-lg-4'
            );
            echo html_writer::div(
                html_writer::tag(
                    'label',
                    get_string('commerce_storefront_hero_media_ratio', 'local_subscriptions'),
                    ['class' => 'form-label']
                )
                . html_writer::select(
                    [
                        'original' => get_string('commerce_storefront_media_ratio_original', 'local_subscriptions'),
                        '1_1' => '1:1',
                        '4_3' => '4:3',
                        '16_9' => '16:9',
                    ],
                    'section_hero_media_ratio_' . $index,
                    $row['heromediaratio'],
                    false,
                    ['class' => 'form-select']
                ),
                'col-lg-4'
            );
        }
        if ($row['type'] === 'image_text') {
        echo html_writer::div(
            html_writer::tag(
                'label',
                get_string(
                    'commerce_storefront_image_position',
                    'local_subscriptions'
                ),
                ['class' => 'form-label']
            )
            . html_writer::select(
                [
                    'left' => get_string('commerce_storefront_image_position_left', 'local_subscriptions'),
                    'right' => get_string('commerce_storefront_image_position_right', 'local_subscriptions'),
                ],
                'section_image_position_' . $index,
                $row['imageposition'],
                false,
                ['class' => 'form-select']
            ),
            'col-lg-3'
        );
        echo html_writer::div(
            html_writer::tag(
                'label',
                get_string('commerce_storefront_image_fit', 'local_subscriptions'),
                ['class' => 'form-label']
            )
            . html_writer::select(
                [
                    'cover' => get_string('commerce_storefront_image_fit_cover', 'local_subscriptions'),
                    'contain' => get_string('commerce_storefront_image_fit_contain', 'local_subscriptions'),
                ],
                'section_image_fit_' . $index,
                $row['imagefit'],
                false,
                ['class' => 'form-select']
            ),
            'col-lg-3'
        );
        echo html_writer::div(
            html_writer::tag(
                'label',
                get_string(
                    'commerce_storefront_column_ratio',
                    'local_subscriptions'
                ),
                ['class' => 'form-label']
            )
            . html_writer::select(
                [
                    '40_60' => '40 / 60',
                    '50_50' => '50 / 50',
                    '60_40' => '60 / 40',
                ],
                'section_column_ratio_' . $index,
                $row['columnratio'],
                false,
                ['class' => 'form-select']
            ),
            'col-lg-3'
        );
        }
        echo html_writer::end_div();
        if (is_array($row['imagemediadiagnostic'])) {
            echo html_writer::div(
                s($row['imagemediadiagnostic']['filename'])
                    . ' · '
                    . display_size(
                        $row['imagemediadiagnostic']['filesize']
                    )
                    . (
                        $row['imagemediadiagnostic']['width'] > 0
                        ? ' · '
                            . $row['imagemediadiagnostic']['width']
                            . ' × '
                            . $row['imagemediadiagnostic']['height']
                            . ' px'
                        : ''
                    ),
                'alert alert-success py-2 small mt-3 mb-0'
            );
        }
        echo html_writer::end_div();
    }

    if ($row['type'] === 'video') {
        echo html_writer::start_div(
            'col-12 commerce-storefront-media-config'
        );
        echo html_writer::tag(
            'h4',
            get_string(
                'commerce_storefront_video_settings',
                'local_subscriptions'
            ),
            ['class' => 'h6']
        );
        echo html_writer::start_div('row g-3');
        echo html_writer::div(
            html_writer::tag(
                'label',
                get_string(
                    'commerce_storefront_video_source',
                    'local_subscriptions'
                ),
                ['class' => 'form-label']
            )
            . html_writer::select(
                [
                    'upload' => get_string(
                        'commerce_storefront_video_upload',
                        'local_subscriptions'
                    ),
                    'youtube' => 'YouTube',
                    'vimeo' => 'Vimeo',
                    'url' => get_string('url'),
                ],
                'section_video_source_' . $index,
                $row['videosource'],
                false,
                ['class' => 'form-select']
            ),
            'col-lg-4'
        );
        echo html_writer::div(
            html_writer::tag(
                'label',
                get_string(
                    'commerce_storefront_video_file',
                    'local_subscriptions'
                ),
                ['class' => 'form-label']
            )
            . html_writer::empty_tag('input', [
                'type' => 'file',
                'name' => 'section_video_file_' . $index,
                'accept' => '.mp4,.webm,.ogv',
                'class' => 'form-control',
            ]),
            'col-lg-5'
        );
        echo html_writer::div(
            html_writer::tag(
                'label',
                get_string(
                    'commerce_storefront_video_ratio',
                    'local_subscriptions'
                ),
                ['class' => 'form-label']
            )
            . html_writer::select(
                [
                    '16_9' => '16:9',
                    '4_3' => '4:3',
                    '1_1' => '1:1',
                ],
                'section_video_ratio_' . $index,
                $row['videoratio'],
                false,
                ['class' => 'form-select']
            ),
            'col-lg-3'
        );
        echo html_writer::div(
            html_writer::tag(
                'label',
                get_string(
                    'commerce_storefront_video_poster',
                    'local_subscriptions'
                ),
                ['class' => 'form-label']
            )
            . html_writer::empty_tag('input', [
                'type' => 'file',
                'name' => 'section_video_poster_' . $index,
                'accept' => '.png,.jpg,.jpeg,.webp',
                'class' => 'form-control',
            ]),
            'col-lg-6'
        );
        echo html_writer::end_div();
        echo html_writer::end_div();
    }

    if ($row['type'] === 'h5p') {
        echo html_writer::start_div(
            'col-12 commerce-storefront-media-config'
        );
        echo html_writer::tag(
            'h4',
            get_string(
                'commerce_storefront_h5p_settings',
                'local_subscriptions'
            ),
            ['class' => 'h6']
        );

        echo html_writer::start_div('row g-3');
        echo html_writer::div(
            html_writer::tag(
                'label',
                get_string(
                    'commerce_storefront_h5p_content',
                    'local_subscriptions'
                ),
                ['class' => 'form-label']
            )
            . html_writer::select(
                $h5pservice->options(),
                'section_h5p_contentid_' . $index,
                $row['h5pcontentid'],
                false,
                ['class' => 'form-select']
            ),
            'col-lg-6'
        );

        echo html_writer::div(
            html_writer::tag(
                'label',
                get_string(
                    'commerce_storefront_h5p_upload',
                    'local_subscriptions'
                ),
                ['class' => 'form-label']
            )
            . html_writer::empty_tag('input', [
                'type' => 'file',
                'name' => 'section_h5p_file_' . $index,
                'accept' => '.h5p',
                'class' => 'form-control',
            ]),
            'col-lg-4'
        );

        echo html_writer::div(
            html_writer::tag(
                'label',
                get_string(
                    'commerce_storefront_h5p_height',
                    'local_subscriptions'
                ),
                ['class' => 'form-label']
            )
            . html_writer::empty_tag('input', [
                'type' => 'number',
                'name' => 'section_h5p_height_' . $index,
                'value' => $row['h5pheight'],
                'min' => 240,
                'max' => 1200,
                'class' => 'form-control',
            ]),
            'col-lg-2'
        );
        echo html_writer::end_div();

        if (!$h5pservice->has_options()) {
            echo html_writer::div(
                get_string(
                    'commerce_storefront_h5p_bank_empty',
                    'local_subscriptions'
                ),
                'alert alert-info py-2 small mt-3 mb-0'
            );
        }

        if (is_array($row['h5pmediadiagnostic'])) {
            echo html_writer::div(
                s($row['h5pmediadiagnostic']['filename'])
                    . ' · '
                    . display_size(
                        $row['h5pmediadiagnostic']['filesize']
                    ),
                'alert alert-success py-2 small mt-3 mb-0'
            );
        }

        echo html_writer::start_div(
            'd-flex flex-wrap gap-2 align-items-center mt-3'
        );
        echo html_writer::link(
            new moodle_url('/contentbank/index.php', [
                'contextid' => context_system::instance()->id,
            ]),
            get_string(
                'commerce_storefront_h5p_open_bank',
                'local_subscriptions'
            ),
            [
                'class' => 'btn btn-sm btn-outline-secondary',
                'target' => '_blank',
                'rel' => 'noopener',
            ]
        );
        echo html_writer::tag(
            'span',
            get_string(
                'commerce_storefront_h5p_help',
                'local_subscriptions'
            ),
            ['class' => 'form-text']
        );
        echo html_writer::end_div();

        echo html_writer::end_div();
    }

    echo html_writer::start_div('col-lg-6');
    echo html_writer::tag('label', get_string('commerce_storefront_section_auxiliary', 'local_subscriptions'), ['for' => 'section_auxiliary_' . $index, 'class' => 'form-label']);
    echo html_writer::empty_tag('input', ['id' => 'section_auxiliary_' . $index, 'name' => 'section_auxiliary_' . $index, 'value' => $row['auxiliary'], 'class' => 'form-control']);
    echo html_writer::tag('div', get_string('commerce_storefront_section_auxiliary_help', 'local_subscriptions'), ['class' => 'form-text']);
    echo html_writer::end_div();

    echo html_writer::start_div('col-lg-6');
    echo html_writer::tag('label', get_string('commerce_storefront_section_alt', 'local_subscriptions'), ['for' => 'section_alt_' . $index, 'class' => 'form-label']);
    echo html_writer::empty_tag('input', ['id' => 'section_alt_' . $index, 'name' => 'section_alt_' . $index, 'value' => $row['alt'], 'class' => 'form-control']);
    echo html_writer::end_div();

    echo html_writer::start_div('col-12');
    echo html_writer::tag('label', get_string('commerce_storefront_section_items', 'local_subscriptions'), ['for' => 'section_items_' . $index, 'class' => 'form-label']);
    echo html_writer::tag('textarea', s($row['items']), ['id' => 'section_items_' . $index, 'name' => 'section_items_' . $index, 'class' => 'form-control font-monospace', 'rows' => 4]);
    echo html_writer::tag('div', get_string('commerce_storefront_section_items_help', 'local_subscriptions'), ['class' => 'form-text']);
    echo html_writer::end_div();

    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_tag('details');
}

if ($rows === []) {
    echo html_writer::div(
        get_string(
            'commerce_storefront_builder_empty',
            'local_subscriptions'
        ),
        'alert alert-light'
    );
}

echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('card card-body mb-4');
echo html_writer::tag('h3', get_string('commerce_storefront_recommendations_title', 'local_subscriptions'), ['class' => 'h5']);
echo html_writer::tag('p', get_string('commerce_storefront_recommendations_help', 'local_subscriptions'), ['class' => 'text-muted']);
echo html_writer::tag('textarea', s($definition['recommendations'] ?? ''), ['name' => 'storefront_recommendations', 'class' => 'form-control', 'rows' => 4, 'placeholder' => "COURSE_ACCESS.A2_COMPLETE
DIGITAL_DOWNLOAD.VERBS_PDF"]);
echo html_writer::end_div();

echo html_writer::end_tag('main');
echo html_writer::end_div();

echo CommerceDesignSystemRenderer::form_actions(
    html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-primary', 'value' => get_string('savechanges')]),
    html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/products/view.php', ['sku' => $product->get_sku()]), get_string('cancel'), ['class' => 'btn btn-outline-secondary'])
);
echo html_writer::end_tag('form');
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
