<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockTypeRegistry;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomCmsRepository;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomBlockEditorRegistry;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomExerciseCatalog;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomExercisePreviewMediaManager;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomPageTemplateRegistry;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomPublicationService;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomRenderTemplateRegistry;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomSeoConfig;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomProductLinkOptions;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomOfferConfig;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomStatus;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

require_login();
require_capability('local/subscriptions:manage_showrooms', context_system::instance());

$id = optional_param('id', 0, PARAM_INT);
$context = context_system::instance();
$pageurl = new moodle_url('/local/subscriptions/admin/commerce/showrooms/edit.php', ['id' => $id]);
$pagetitle = get_string('commerce_showroom_cms_edit', 'local_subscriptions');

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    [
        'local-subscriptions-showroom-builder-page',
        'local-subscriptions-showroom-cms-page',
    ]
);
$PAGE->requires->css('/local/subscriptions/styles/showroom_builder.css');

$repository = new CommerceShowroomCmsRepository($DB);
$productlinkoptions = new CommerceShowroomProductLinkOptions($DB);
$record = $id > 0 ? $repository->get($id) : null;
$publication = new CommerceShowroomPublicationService($DB, $repository);
if ($id > 0 && $record === null) {
    throw new moodle_exception('invalidrecord');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $workflowaction = optional_param('workflowaction', '', PARAM_ALPHANUMEXT);
    $workflownote = optional_param('workflownote', '', PARAM_TEXT);
    if ($id > 0 && $workflowaction === 'review') {
        $publication->submit_for_review($id, (int)$USER->id, $workflownote);
        redirect($PAGE->url, get_string('commerce_showroom_submitted_review', 'local_subscriptions'));
    } else if ($id > 0 && $workflowaction === 'publish') {
        $publication->publish($id, (int)$USER->id, $workflownote);
        redirect($PAGE->url, get_string('commerce_showroom_published', 'local_subscriptions'));
    } else if ($id > 0 && $workflowaction === 'draft') {
        $publication->return_to_draft($id, (int)$USER->id, $workflownote);
        redirect($PAGE->url, get_string('commerce_showroom_returned_draft', 'local_subscriptions'));
    }

    $seovalues = [];
    foreach (CommerceShowroomSeoConfig::LANGUAGES as $language) {
        $seovalues[$language] = [
            'title' => optional_param('seotitle_' . $language, '', PARAM_TEXT),
            'description' => optional_param('seodescription_' . $language, '', PARAM_RAW_TRIMMED),
            'socialtitle' => optional_param('seosocialtitle_' . $language, '', PARAM_TEXT),
            'socialdescription' => optional_param('seosocialdescription_' . $language, '', PARAM_RAW_TRIMMED),
            'keywords' => optional_param('seokeywords_' . $language, '', PARAM_TEXT),
        ];
    }
    $settingsjson = CommerceShowroomSeoConfig::merge_into_settings_json(
        optional_param('settingsjson', '{}', PARAM_RAW),
        $seovalues
    );
    $offerconfig = [];
    foreach (CommerceShowroomOfferConfig::ROLES as $role) {
        $offerconfig[$role] = [
            'detailsenabled' => optional_param('offerdetails_' . $role, 0, PARAM_BOOL) === 1,
        ];
    }
    $settingsjson = CommerceShowroomOfferConfig::merge_into_settings_json(
        $settingsjson,
        $offerconfig
    );

    $productsjson = json_encode([
        'course' => $productlinkoptions->normalise_sku(
            optional_param('linkedcourse', '', PARAM_RAW_TRIMMED),
            CommerceProductType::COURSE_ACCESS
        ),
        'pdf' => $productlinkoptions->normalise_sku(
            optional_param('linkedpdf', '', PARAM_RAW_TRIMMED),
            CommerceProductType::DIGITAL_DOWNLOAD
        ),
        'bundle' => $productlinkoptions->normalise_sku(
            optional_param('linkedbundle', '', PARAM_RAW_TRIMMED),
            CommerceProductType::BUNDLE
        ),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

    $savedid = $repository->save([
        'id' => $id,
        'showroomkey' => required_param('showroomkey', PARAM_ALPHANUMEXT),
        // J16S6: workflow status is immutable from the general configuration form.
        // New records always start as draft; existing records preserve their status.
        'status' => $record !== null
            ? (string)$record->status
            : CommerceShowroomStatus::DRAFT,
        'name' => required_param('name', PARAM_TEXT),
        'template' => CommerceShowroomRenderTemplateRegistry::normalise(required_param('template', PARAM_RAW_TRIMMED)),
        'slugfr' => optional_param('slugfr', '', PARAM_ALPHANUMEXT),
        'slugen' => optional_param('slugen', '', PARAM_ALPHANUMEXT),
        'slugru' => optional_param('slugru', '', PARAM_ALPHANUMEXT),
        'titlekey' => optional_param('titlekey', '', PARAM_ALPHANUMEXT),
        'descriptionkey' => optional_param('descriptionkey', '', PARAM_ALPHANUMEXT),
        'productsjson' => $productsjson,
        'settingsjson' => $settingsjson,
    ], (int)$USER->id);
    redirect(new moodle_url($PAGE->url, ['id' => $savedid]), get_string('changessaved'));
}

$defaults = $record ?: (object)[
    'showroomkey' => '', 'status' => 'draft', 'name' => '',
    'template' => 'local_subscriptions/showroom/third_group_verbs',
    'slugfr' => '', 'slugen' => '', 'slugru' => '',
    'titlekey' => '', 'descriptionkey' => '',
    'productsjson' => "{\n  \"course\": \"\",\n  \"pdf\": \"\",\n  \"bundle\": \"\"\n}",
    'settingsjson' => '{}',
];

$currentseo = CommerceShowroomSeoConfig::from_settings_json(
    (string)$defaults->settingsjson
);

$currentproducts = json_decode((string)$defaults->productsjson, true);
$currentproducts = is_array($currentproducts) ? $currentproducts : [];
$productoptions = $productlinkoptions->grouped_options($currentproducts);
$currentofferconfig = CommerceShowroomOfferConfig::from_settings_json((string)$defaults->settingsjson);

$blocks = $id > 0 ? $repository->blocks($id) : [];
$types = CommerceShowroomBlockTypeRegistry::definitions();

$builderconfig = null;
if ($id > 0) {
    $exercisepreviewmanager = new CommerceShowroomExercisePreviewMediaManager(
        context_system::instance()
    );
    $exerciseMedia = [];
    foreach ($blocks as $block) {
        if ((string)$block->blocktype !== 'exercise_explorer') {
            continue;
        }
        $blockid = (int)$block->id;
        $exerciseMedia[(string)$blockid] = [];
        foreach (CommerceShowroomExerciseCatalog::keys() as $exercisekey) {
            $exerciseMedia[(string)$blockid][$exercisekey] = [];
            foreach (CommerceShowroomExercisePreviewMediaManager::LANGUAGES as $language) {
                $url = $exercisepreviewmanager->get_url($blockid, $exercisekey, $language);
                $exerciseMedia[(string)$blockid][$exercisekey][$language] = $url?->out(false) ?? '';
            }
        }
    }

    $builderconfig = [
        'root' => '#commerce-showroom-builder',
        'endpoint' => (new moodle_url('/local/subscriptions/admin/commerce/showrooms/ajax.php'))->out(false),
        'showroomid' => $id,
        'sesskey' => sesskey(),
        'schemas' => CommerceShowroomBlockEditorRegistry::schemas(),
        'languages' => [
            ['code' => 'fr', 'label' => 'Français'],
            ['code' => 'en', 'label' => 'English'],
            ['code' => 'ru', 'label' => 'Русский'],
        ],
        'defaultlanguage' => 'fr',
        'exerciseMedia' => $exerciseMedia,
        'strings' => [
            'confirmdelete' => get_string('commerce_showroom_builder_confirm_delete', 'local_subscriptions'),
            'saved' => get_string('commerce_showroom_builder_saved', 'local_subscriptions'),
            'defaultsinitialised' => get_string(
                'commerce_showroom_builder_defaults_initialised',
                'local_subscriptions'
            ),
            'confirmdefaults' => get_string(
                'commerce_showroom_builder_confirm_defaults',
                'local_subscriptions'
            ),
            'invalidjson' => get_string(
                'commerce_showroom_builder_invalid_json',
                'local_subscriptions'
            ),
            'jsonobjectrequired' => get_string(
                'commerce_showroom_builder_json_object_required',
                'local_subscriptions'
            ),
            'mediachoose' => get_string(
                'commerce_showroom_media_choose',
                'local_subscriptions'
            ),
            'mediachoosevideo' => get_string(
                'commerce_showroom_media_choose_video',
                'local_subscriptions'
            ),
            'mediaremove' => get_string(
                'commerce_showroom_media_remove',
                'local_subscriptions'
            ),
            'mediaremovevideo' => get_string(
                'commerce_showroom_media_remove_video',
                'local_subscriptions'
            ),
            'mediauploading' => get_string(
                'commerce_showroom_media_uploading',
                'local_subscriptions'
            ),
            'mediaempty' => get_string(
                'commerce_showroom_media_empty',
                'local_subscriptions'
            ),
            'mediaemptyvideo' => get_string(
                'commerce_showroom_media_empty_video',
                'local_subscriptions'
            ),
            'mediauploaded' => get_string(
                'commerce_showroom_media_uploaded',
                'local_subscriptions'
            ),
            'mediauploadedvideo' => get_string(
                'commerce_showroom_media_uploaded_video',
                'local_subscriptions'
            ),
            'exerciseeditor' => get_string('commerce_showroom_exercise_builder_title', 'local_subscriptions'),
            'exercisecontent' => get_string('commerce_showroom_exercise_builder_content', 'local_subscriptions'),
            'exercisemedia' => get_string('commerce_showroom_exercise_builder_media', 'local_subscriptions'),
            'exercisedefault' => get_string('commerce_showroom_exercise_builder_default', 'local_subscriptions'),
            'exerciseimport' => get_string('commerce_showroom_exercise_builder_import', 'local_subscriptions'),
            'exerciseimporthelp' => get_string('commerce_showroom_exercise_builder_import_help', 'local_subscriptions'),
            'exerciseimportbutton' => get_string('commerce_showroom_exercise_builder_import_button', 'local_subscriptions'),
            'exerciseimportdone' => get_string('commerce_showroom_exercise_builder_import_done', 'local_subscriptions'),
            'exercisechooseimage' => get_string('commerce_showroom_exercise_builder_choose_image', 'local_subscriptions'),
            'exerciseremoveimage' => get_string('commerce_showroom_exercise_builder_remove_image', 'local_subscriptions'),
            'exerciseimageempty' => get_string('commerce_showroom_exercise_builder_image_empty', 'local_subscriptions'),
            'exerciseimagelocalizedempty' => get_string('commerce_showroom_exercise_builder_localized_empty', 'local_subscriptions'),
            'exerciseimagelocalizedfallback' => get_string('commerce_showroom_exercise_builder_localized_fallback', 'local_subscriptions'),
            'exerciseimagefallbackbadge' => get_string('commerce_showroom_exercise_builder_fallback_badge', 'local_subscriptions'),
            'exerciseimagefallback' => get_string('commerce_showroom_exercise_builder_image_fallback', 'local_subscriptions'),
            'collapseall' => 'Tout replier',
            'expandall' => 'Tout déplier',
            'error' => get_string('error'),
        ],
    ];
    $PAGE->requires->js(
        new moodle_url('/local/subscriptions/js/showroom_builder.js')
    );
}

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::SHOWROOMS, $context);
echo html_writer::start_div('commerce-showroom-admin commerce-showroom-admin--crm');
echo html_writer::start_div('d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4');
echo html_writer::start_div('d-flex align-items-center gap-2');
echo html_writer::link(
    new moodle_url('/local/subscriptions/admin/commerce/showrooms/index.php'),
    '<i class="fa-solid fa-arrow-left" aria-hidden="true"></i><span class="visually-hidden">'
        . get_string('back') . '</span>',
    [
        'class' => 'btn btn-outline-secondary',
        'title' => get_string('commerce_showroom_back_to_list', 'local_subscriptions'),
        'aria-label' => get_string('commerce_showroom_back_to_list', 'local_subscriptions'),
    ]
);
echo $OUTPUT->heading(get_string('commerce_showroom_cms_edit', 'local_subscriptions'), 2, 'mb-0');
echo html_writer::end_div();
if ($record !== null) {
    echo html_writer::link(
        new moodle_url(
            '/local/subscriptions/admin/commerce/showrooms/preview.php',
            ['id' => $id]
        ),
        '<i class="fa-solid fa-eye" aria-hidden="true"></i> '
            . get_string('commerce_showroom_builder_preview', 'local_subscriptions'),
        [
            'class' => 'btn btn-outline-primary',
            'target' => '_blank',
            'rel' => 'noopener',
        ]
    );
    echo ' ' . html_writer::link(
        new moodle_url(
            '/local/subscriptions/admin/commerce/showrooms/export_portable_preflight.php',
            ['id' => $id]
        ),
        '<i class="fa-solid fa-box-archive" aria-hidden="true"></i> '
            . get_string(
                'commerce_showroom_export_portable',
                'local_subscriptions'
            ),
        ['class' => 'btn btn-success']
    );
    echo ' ' . html_writer::link(
        new moodle_url(
            '/local/subscriptions/admin/commerce/showrooms/export.php',
            ['id' => $id, 'sesskey' => sesskey()]
        ),
        '<i class="fa-solid fa-file-code" aria-hidden="true"></i> '
            . get_string('commerce_showroom_export_json', 'local_subscriptions'),
        ['class' => 'btn btn-outline-secondary']
    );
    echo ' ' . html_writer::link(
        new moodle_url('/local/subscriptions/admin/commerce/showrooms/import.php'),
        '<i class="fa-solid fa-file-import" aria-hidden="true"></i> ' . get_string('commerce_showroom_import', 'local_subscriptions'),
        ['class' => 'btn btn-outline-secondary']
    );
    echo ' ' . html_writer::link(
        new moodle_url('/local/subscriptions/admin/commerce/showrooms/history.php', ['id' => $id]),
        '<i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> ' . get_string('commerce_showroom_history', 'local_subscriptions'),
        ['class' => 'btn btn-outline-secondary']
    );
    echo html_writer::start_tag('form', ['method' => 'post', 'class' => 'd-inline-flex gap-2 ms-2']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'workflownote', 'class' => 'form-control form-control-sm',
        'placeholder' => get_string('commerce_showroom_revision_note', 'local_subscriptions')]);
    if ($record->status === 'draft') {
        echo html_writer::tag('button', get_string('commerce_showroom_submit_review', 'local_subscriptions'), [
            'type' => 'submit', 'name' => 'workflowaction', 'value' => 'review', 'class' => 'btn btn-sm btn-warning']);
    } else if ($record->status === 'review') {
        echo html_writer::tag('button', get_string('commerce_showroom_publish', 'local_subscriptions'), [
            'type' => 'submit', 'name' => 'workflowaction', 'value' => 'publish', 'class' => 'btn btn-sm btn-success']);
        echo html_writer::tag('button', get_string('commerce_showroom_return_draft', 'local_subscriptions'), [
            'type' => 'submit', 'name' => 'workflowaction', 'value' => 'draft', 'class' => 'btn btn-sm btn-outline-secondary']);
    } else if ($record->status === 'published') {
        echo html_writer::tag('button', get_string('commerce_showroom_return_draft', 'local_subscriptions'), [
            'type' => 'submit', 'name' => 'workflowaction', 'value' => 'draft', 'class' => 'btn btn-sm btn-outline-secondary']);
    }
    echo html_writer::end_tag('form');
}
echo html_writer::end_div();

echo html_writer::start_tag('form', [
    'method' => 'post',
    'class' => 'card card-body mb-4 commerce-showroom-general-config',
]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

echo html_writer::tag('h3', get_string('commerce_showroom_config_general', 'local_subscriptions'), [
    'class' => 'h5 mb-1',
]);
echo html_writer::tag(
    'p',
    get_string('commerce_showroom_config_general_help', 'local_subscriptions'),
    ['class' => 'text-muted mb-4']
);

echo html_writer::start_div('row g-3');

echo html_writer::start_div('col-12 col-xl-6');
echo html_writer::tag('label', get_string('name'), ['for' => 'name', 'class' => 'form-label']);
echo html_writer::empty_tag('input', [
    'id' => 'name',
    'name' => 'name',
    'value' => $defaults->name,
    'class' => 'form-control',
    'required' => true,
]);
echo html_writer::end_div();

echo html_writer::start_div('col-12 col-md-6 col-xl-3');
echo html_writer::tag(
    'label',
    get_string('commerce_showroom_cms_key', 'local_subscriptions'),
    ['for' => 'showroomkey', 'class' => 'form-label']
);
echo html_writer::empty_tag('input', [
    'id' => 'showroomkey',
    'name' => 'showroomkey',
    'value' => $defaults->showroomkey,
    'class' => 'form-control',
    'required' => true,
]);
echo html_writer::tag(
    'div',
    get_string('commerce_showroom_config_key_help', 'local_subscriptions'),
    ['class' => 'form-text']
);
echo html_writer::end_div();

echo html_writer::start_div('col-12 col-md-6 col-xl-3');
echo html_writer::tag(
    'label',
    get_string('status'),
    ['class' => 'form-label d-block']
);
echo html_writer::tag(
    'span',
    CommerceShowroomStatus::label((string)$defaults->status),
    [
        'class' => 'badge bg-'
            . CommerceShowroomStatus::badge_class((string)$defaults->status),
    ]
);
echo html_writer::tag(
    'div',
    get_string('commerce_showroom_status_workflow_only', 'local_subscriptions'),
    ['class' => 'form-text']
);
echo html_writer::end_div();

echo html_writer::start_div('col-12 col-xl-6');
echo html_writer::tag(
    'label',
    get_string('commerce_showroom_config_render_template', 'local_subscriptions'),
    ['for' => 'template', 'class' => 'form-label']
);
echo html_writer::select(
    CommerceShowroomRenderTemplateRegistry::options(),
    'template',
    $defaults->template,
    false,
    ['id' => 'template', 'class' => 'form-select']
);
echo html_writer::tag(
    'div',
    get_string('commerce_showroom_config_render_template_help', 'local_subscriptions'),
    ['class' => 'form-text']
);
echo html_writer::end_div();

echo html_writer::end_div();

echo html_writer::tag('hr', '', ['class' => 'my-4']);
echo html_writer::tag(
    'h3',
    get_string('commerce_showroom_config_seo', 'local_subscriptions'),
    ['class' => 'h5 mb-1']
);
echo html_writer::tag(
    'p',
    get_string('commerce_showroom_config_seo_help', 'local_subscriptions'),
    ['class' => 'text-muted mb-3']
);

$seolanguages = [
    'fr' => ['label' => 'Français', 'flag' => '🇫🇷'],
    'en' => ['label' => 'English', 'flag' => '🇬🇧'],
    'ru' => ['label' => 'Русский', 'flag' => '🇷🇺'],
];

echo html_writer::start_div('commerce-showroom-seo-grid');
foreach ($seolanguages as $language => $languageconfig) {
    $slugfield = 'slug' . $language;
    echo html_writer::start_tag('section', [
        'class' => 'commerce-showroom-seo-card',
        'aria-labelledby' => 'seo-heading-' . $language,
    ]);

    echo html_writer::start_div('commerce-showroom-seo-card__header');
    echo html_writer::tag('span', $languageconfig['flag'], [
        'class' => 'commerce-showroom-seo-card__flag',
        'aria-hidden' => 'true',
    ]);
    echo html_writer::tag(
        'h4',
        $languageconfig['label'],
        ['id' => 'seo-heading-' . $language, 'class' => 'h6 mb-0']
    );
    echo html_writer::end_div();

    echo html_writer::tag(
        'label',
        get_string('commerce_showroom_config_seo_slug', 'local_subscriptions'),
        ['for' => $slugfield, 'class' => 'form-label']
    );
    echo html_writer::empty_tag('input', [
        'id' => $slugfield,
        'name' => $slugfield,
        'value' => $defaults->{$slugfield},
        'class' => 'form-control',
    ]);

    echo html_writer::tag(
        'label',
        get_string('commerce_showroom_config_seo_title', 'local_subscriptions'),
        ['for' => 'seotitle_' . $language, 'class' => 'form-label mt-3']
    );
    echo html_writer::empty_tag('input', [
        'id' => 'seotitle_' . $language,
        'name' => 'seotitle_' . $language,
        'value' => $currentseo[$language]['title'],
        'class' => 'form-control',
        'maxlength' => 70,
    ]);
    echo html_writer::tag(
        'div',
        get_string('commerce_showroom_config_seo_title_help', 'local_subscriptions'),
        ['class' => 'form-text']
    );

    echo html_writer::tag(
        'label',
        get_string('commerce_showroom_config_seo_description', 'local_subscriptions'),
        ['for' => 'seodescription_' . $language, 'class' => 'form-label mt-3']
    );
    echo html_writer::tag('textarea', s($currentseo[$language]['description']), [
        'id' => 'seodescription_' . $language,
        'name' => 'seodescription_' . $language,
        'rows' => 3,
        'maxlength' => 180,
        'class' => 'form-control',
    ]);

    echo html_writer::start_tag('details', [
        'class' => 'commerce-showroom-seo-card__social mt-3',
    ]);
    echo html_writer::tag(
        'summary',
        get_string('commerce_showroom_config_seo_social', 'local_subscriptions')
    );

    echo html_writer::tag(
        'label',
        get_string('commerce_showroom_config_seo_social_title', 'local_subscriptions'),
        ['for' => 'seosocialtitle_' . $language, 'class' => 'form-label mt-3']
    );
    echo html_writer::empty_tag('input', [
        'id' => 'seosocialtitle_' . $language,
        'name' => 'seosocialtitle_' . $language,
        'value' => $currentseo[$language]['socialtitle'],
        'class' => 'form-control',
        'maxlength' => 90,
    ]);

    echo html_writer::tag(
        'label',
        get_string('commerce_showroom_config_seo_social_description', 'local_subscriptions'),
        ['for' => 'seosocialdescription_' . $language, 'class' => 'form-label mt-3']
    );
    echo html_writer::tag('textarea', s($currentseo[$language]['socialdescription']), [
        'id' => 'seosocialdescription_' . $language,
        'name' => 'seosocialdescription_' . $language,
        'rows' => 3,
        'maxlength' => 220,
        'class' => 'form-control',
    ]);

    echo html_writer::tag(
        'label',
        get_string('commerce_showroom_config_seo_keywords', 'local_subscriptions'),
        ['for' => 'seokeywords_' . $language, 'class' => 'form-label mt-3']
    );
    echo html_writer::empty_tag('input', [
        'id' => 'seokeywords_' . $language,
        'name' => 'seokeywords_' . $language,
        'value' => $currentseo[$language]['keywords'],
        'class' => 'form-control',
    ]);
    echo html_writer::tag(
        'div',
        get_string('commerce_showroom_config_seo_keywords_help', 'local_subscriptions'),
        ['class' => 'form-text']
    );

    echo html_writer::end_tag('details');
    echo html_writer::end_tag('section');
}
echo html_writer::end_div();

echo html_writer::tag('hr', '', ['class' => 'my-4']);
echo html_writer::tag(
    'h3',
    get_string('commerce_showroom_config_products', 'local_subscriptions'),
    ['class' => 'h5 mb-1']
);
echo html_writer::tag(
    'p',
    get_string('commerce_showroom_config_products_help', 'local_subscriptions'),
    ['class' => 'text-muted mb-3']
);

$productfields = [
    'linkedcourse' => [
        'label' => get_string('commerce_showroom_config_product_course', 'local_subscriptions'),
        'key' => 'course',
    ],
    'linkedpdf' => [
        'label' => get_string('commerce_showroom_config_product_pdf', 'local_subscriptions'),
        'key' => 'pdf',
    ],
    'linkedbundle' => [
        'label' => get_string('commerce_showroom_config_product_bundle', 'local_subscriptions'),
        'key' => 'bundle',
    ],
];

echo html_writer::start_div('row g-3');
foreach ($productfields as $name => $definition) {
    $key = $definition['key'];
    echo html_writer::start_div('col-12 col-xl-4');
    echo html_writer::tag('label', $definition['label'], [
        'for' => $name,
        'class' => 'form-label',
    ]);
    echo html_writer::select(
        $productoptions[$key],
        $name,
        (string)($currentproducts[$key] ?? ''),
        ['' => get_string('commerce_showroom_config_product_none', 'local_subscriptions')],
        ['id' => $name, 'class' => 'form-select']
    );
    echo html_writer::start_div('form-check form-switch mt-2');
    echo html_writer::empty_tag('input', [
        'type' => 'checkbox',
        'class' => 'form-check-input',
        'id' => 'offerdetails_' . $key,
        'name' => 'offerdetails_' . $key,
        'value' => '1',
        'checked' => !empty($currentofferconfig[$key]['detailsenabled']) ? 'checked' : null,
    ]);
    echo html_writer::tag(
        'label',
        get_string('commerce_showroom_config_offer_details_enabled', 'local_subscriptions'),
        ['for' => 'offerdetails_' . $key, 'class' => 'form-check-label']
    );
    echo html_writer::end_div();
    echo html_writer::end_div();
}
echo html_writer::end_div();

echo html_writer::start_tag('details', [
    'class' => 'commerce-showroom-general-config__advanced mt-4',
]);
echo html_writer::tag(
    'summary',
    '<i class="fa-solid fa-sliders" aria-hidden="true"></i> '
        . get_string('commerce_showroom_config_advanced', 'local_subscriptions')
);
echo html_writer::start_div('commerce-showroom-general-config__advanced-body');

foreach ([
    'titlekey' => get_string('commerce_showroom_config_titlekey_legacy', 'local_subscriptions'),
    'descriptionkey' => get_string('commerce_showroom_config_descriptionkey_legacy', 'local_subscriptions'),
] as $name => $label) {
    echo html_writer::tag('label', $label, ['for' => $name, 'class' => 'form-label mt-3']);
    echo html_writer::empty_tag('input', [
        'id' => $name,
        'name' => $name,
        'value' => $defaults->{$name},
        'class' => 'form-control font-monospace',
    ]);
}

echo html_writer::tag(
    'p',
    get_string('commerce_showroom_config_seo_legacy_help', 'local_subscriptions'),
    ['class' => 'form-text mb-3']
);

echo html_writer::tag(
    'label',
    get_string('commerce_showroom_config_settings_json', 'local_subscriptions'),
    ['for' => 'settingsjson', 'class' => 'form-label']
);
echo html_writer::tag('textarea', s($defaults->settingsjson), [
    'id' => 'settingsjson',
    'name' => 'settingsjson',
    'rows' => 6,
    'class' => 'form-control font-monospace',
]);
echo html_writer::tag(
    'div',
    get_string('commerce_showroom_config_settings_json_help', 'local_subscriptions'),
    ['class' => 'form-text']
);

echo html_writer::end_div();
echo html_writer::end_tag('details');

echo html_writer::tag('button', get_string('savechanges'), [
    'type' => 'submit',
    'class' => 'btn btn-primary align-self-start mt-4',
]);
echo html_writer::end_tag('form');

if ($id > 0) {
    echo html_writer::tag(
        'script',
        json_encode($builderconfig, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR),
        [
            'id' => 'commerce-showroom-builder-config',
            'type' => 'application/json',
        ]
    );
    echo html_writer::start_tag('section', [
        'id' => 'commerce-showroom-builder',
        'data-config-id' => 'commerce-showroom-builder-config',
        'class' => 'commerce-showroom-builder card',
        'data-showroom-id' => $id,
    ]);
    echo html_writer::start_div('commerce-showroom-builder__header');
    echo html_writer::start_div('commerce-showroom-builder__heading');
    echo html_writer::tag('h3', get_string('commerce_showroom_cms_blocks', 'local_subscriptions'), ['class' => 'mb-1']);
    echo html_writer::tag('p', get_string('commerce_showroom_builder_help', 'local_subscriptions'), ['class' => 'text-muted mb-0']);
    echo html_writer::end_div();
    echo html_writer::start_div('commerce-showroom-builder__add');
    $templateoptions = [];
    foreach (CommerceShowroomPageTemplateRegistry::definitions() as $templatekey => $templatedefinition) {
        $templateoptions[$templatekey] = $templatedefinition['label'];
    }
    echo html_writer::select($templateoptions, 'pagetemplate', '', [
        '' => get_string('commerce_showroom_choose_template', 'local_subscriptions'),
    ], ['class' => 'form-select', 'data-role' => 'page-template']);
    echo html_writer::tag('button', '<i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i> '
        . get_string('commerce_showroom_apply_template', 'local_subscriptions'), [
        'type' => 'button', 'class' => 'btn btn-outline-primary', 'data-action' => 'apply-template',
    ]);
    $options = [];
    foreach ($types as $type => $definition) {
        if (array_key_exists('addable', $definition) && !$definition['addable']) {
            continue;
        }
        $options[$type] = $definition['label'];
    }
    echo html_writer::select($options, 'blocktype', '', ['' => get_string('commerce_showroom_builder_choose_block', 'local_subscriptions')], [
        'class' => 'form-select',
        'data-role' => 'block-type',
    ]);
    echo html_writer::tag('button', '<i class="fa-solid fa-plus" aria-hidden="true"></i> '
        . get_string('add'), [
        'type' => 'button',
        'class' => 'btn btn-primary',
        'data-action' => 'add-block',
    ]);
    echo html_writer::end_div();
    echo html_writer::tag(
        'button',
        '<i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i> '
            . get_string('commerce_showroom_builder_initialise_defaults', 'local_subscriptions'),
        [
            'type' => 'button',
            'class' => 'btn btn-outline-primary',
            'data-action' => 'initialise-defaults',
        ]
    );
    echo html_writer::start_div('commerce-showroom-builder__view-tools');
    echo html_writer::tag('button', '<i class="fa-solid fa-compress" aria-hidden="true"></i> Tout replier', [
        'type' => 'button', 'class' => 'btn btn-sm btn-outline-secondary', 'data-action' => 'collapse-all']);
    echo html_writer::tag('button', '<i class="fa-solid fa-expand" aria-hidden="true"></i> Tout déplier', [
        'type' => 'button', 'class' => 'btn btn-sm btn-outline-secondary', 'data-action' => 'expand-all']);
    echo html_writer::end_div();
    echo html_writer::end_div();

    echo html_writer::start_tag('ol', [
        'class' => 'commerce-showroom-builder__list',
        'data-role' => 'block-list',
        'aria-label' => get_string('commerce_showroom_cms_blocks', 'local_subscriptions'),
    ]);
    foreach ($blocks as $block) {
        $definition = $types[$block->blocktype] ?? ['label' => $block->blocktype, 'icon' => 'fa-cube'];
        $config = json_decode((string)$block->configjson, true);
        $summary = is_array($config) && isset($config['title']) ? (string)$config['title'] : '';
        echo html_writer::start_tag('li', [
            'class' => 'commerce-showroom-block' . ($block->enabled ? '' : ' is-disabled'),
            'data-block-id' => (int)$block->id,
            'data-block-key' => $block->blockkey,
            'data-block-type' => $block->blocktype,
            'data-enabled' => (int)$block->enabled,
            'data-config' => (string)$block->configjson,
            'draggable' => 'true',
        ]);
        echo html_writer::start_div('commerce-showroom-block__move');
        echo html_writer::tag('button', '<i class="fa-solid fa-grip-vertical" aria-hidden="true"></i>', [
            'type' => 'button',
            'class' => 'commerce-showroom-block__handle',
            'data-role' => 'drag-handle',
            'title' => get_string('move'),
            'aria-label' => get_string('move'),
        ]);
        echo html_writer::start_div('commerce-showroom-block__move-arrows');
        echo html_writer::tag('button', '<i class="fa-solid fa-chevron-up" aria-hidden="true"></i>', [
            'type' => 'button',
            'class' => 'commerce-showroom-block__move-button',
            'data-action' => 'move-block-up',
            'title' => get_string('moveup'),
            'aria-label' => get_string('moveup'),
        ]);
        echo html_writer::tag('button', '<i class="fa-solid fa-chevron-down" aria-hidden="true"></i>', [
            'type' => 'button',
            'class' => 'commerce-showroom-block__move-button',
            'data-action' => 'move-block-down',
            'title' => get_string('movedown'),
            'aria-label' => get_string('movedown'),
        ]);
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::start_div('commerce-showroom-block__identity');
        echo html_writer::tag('span', '<i class="fa-solid ' . s($definition['icon']) . '" aria-hidden="true"></i>', [
            'class' => 'commerce-showroom-block__icon',
        ]);
        echo html_writer::start_div('commerce-showroom-block__text');
        echo html_writer::tag('strong', s($definition['label']), ['class' => 'commerce-showroom-block__label']);
        echo html_writer::tag('span', s($block->blockkey), ['class' => 'commerce-showroom-block__key']);
        echo html_writer::tag('span', s($summary), ['class' => 'commerce-showroom-block__summary']);
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::start_div('commerce-showroom-block__actions');
        echo html_writer::tag('button', '<i class="fa-solid fa-chevron-down" aria-hidden="true"></i><span class="sr-only">Afficher ou masquer</span>', [
            'type' => 'button', 'class' => 'btn btn-sm btn-outline-secondary', 'data-action' => 'collapse-block', 'aria-expanded' => 'true']);
        echo html_writer::tag('button', '<i class="fa-solid fa-pen" aria-hidden="true"></i><span class="sr-only">'
            . get_string('edit') . '</span>', [
            'type' => 'button', 'class' => 'btn btn-sm btn-outline-secondary', 'data-action' => 'edit-block',
            'title' => get_string('edit'),
        ]);
        echo html_writer::tag('button', '<i class="fa-solid fa-copy" aria-hidden="true"></i><span class="sr-only">'
            . get_string('duplicate') . '</span>', [
            'type' => 'button', 'class' => 'btn btn-sm btn-outline-secondary', 'data-action' => 'duplicate-block',
            'title' => get_string('duplicate'),
        ]);
        echo html_writer::tag('button', '<i class="fa-solid ' . ($block->enabled ? 'fa-eye' : 'fa-eye-slash')
            . '" aria-hidden="true"></i><span class="sr-only">'
            . get_string('commerce_showroom_builder_toggle', 'local_subscriptions') . '</span>', [
            'type' => 'button', 'class' => 'btn btn-sm btn-outline-secondary', 'data-action' => 'toggle-block',
            'title' => get_string('commerce_showroom_builder_toggle', 'local_subscriptions'),
        ]);
        echo html_writer::tag('button', '<i class="fa-solid fa-trash" aria-hidden="true"></i><span class="sr-only">'
            . get_string('delete') . '</span>', [
            'type' => 'button', 'class' => 'btn btn-sm btn-outline-danger', 'data-action' => 'delete-block',
            'title' => get_string('delete'),
        ]);
        echo html_writer::end_div();
        echo html_writer::end_tag('li');
    }
    echo html_writer::end_tag('ol');
    echo html_writer::tag('div', '', ['class' => 'commerce-showroom-builder__status', 'data-role' => 'status', 'aria-live' => 'polite']);
    echo html_writer::end_tag('section');

    echo html_writer::start_tag('dialog', ['class' => 'commerce-showroom-dialog', 'data-role' => 'block-dialog']);
    echo html_writer::start_tag('form', ['method' => 'dialog', 'class' => 'commerce-showroom-dialog__panel', 'data-role' => 'block-form']);
    echo html_writer::start_div('commerce-showroom-dialog__header');
    echo html_writer::tag('h3', get_string('commerce_showroom_builder_edit_block', 'local_subscriptions'), ['class' => 'mb-0']);
    echo html_writer::tag('button', '<i class="fa-solid fa-xmark" aria-hidden="true"></i>', [
        'type' => 'button', 'class' => 'btn btn-sm btn-light', 'data-action' => 'close-dialog',
        'aria-label' => get_string('closebuttontitle'),
    ]);
    echo html_writer::end_div();
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'blockid', 'data-field' => 'blockid']);
    echo html_writer::start_div('mb-3');
    echo html_writer::tag('label', get_string('commerce_showroom_builder_block_key', 'local_subscriptions'), [
        'for' => 'showroom-block-key', 'class' => 'form-label',
    ]);
    echo html_writer::empty_tag('input', [
        'id' => 'showroom-block-key', 'name' => 'blockkey', 'class' => 'form-control', 'data-field' => 'blockkey', 'required' => true,
    ]);
    echo html_writer::end_div();
    echo html_writer::start_div('form-check form-switch mb-3');
    echo html_writer::empty_tag('input', [
        'id' => 'showroom-block-enabled', 'type' => 'checkbox', 'class' => 'form-check-input', 'data-field' => 'enabled',
    ]);
    echo html_writer::tag('label', get_string('enable'), ['for' => 'showroom-block-enabled', 'class' => 'form-check-label']);
    echo html_writer::end_div();
    echo html_writer::tag('div', '', [
        'class' => 'commerce-showroom-dialog__fields',
        'data-role' => 'business-fields',
    ]);
    echo html_writer::start_tag('details', [
        'class' => 'commerce-showroom-dialog__advanced mb-3',
    ]);
    echo html_writer::tag(
        'summary',
        get_string('commerce_showroom_builder_advanced_json', 'local_subscriptions')
    );
    echo html_writer::div(
        get_string('commerce_showroom_builder_advanced_json_help', 'local_subscriptions'),
        'commerce-showroom-dialog__advanced-help'
    );
    echo html_writer::tag('textarea', '', [
        'id' => 'showroom-block-config',
        'name' => 'configjson',
        'rows' => 12,
        'class' => 'form-control font-monospace mt-2',
        'data-field' => 'configjson',
        'spellcheck' => 'false',
    ]);
    echo html_writer::tag('div', '', [
        'class' => 'commerce-showroom-dialog__json-error',
        'data-role' => 'json-error',
        'aria-live' => 'polite',
    ]);
    echo html_writer::start_div('commerce-showroom-dialog__advanced-actions');
    echo html_writer::tag(
        'button',
        get_string('commerce_showroom_builder_apply_json', 'local_subscriptions'),
        [
            'type' => 'button',
            'class' => 'btn btn-sm btn-outline-primary',
            'data-action' => 'apply-json',
        ]
    );
    echo html_writer::tag(
        'button',
        get_string('commerce_showroom_builder_sync_json', 'local_subscriptions'),
        [
            'type' => 'button',
            'class' => 'btn btn-sm btn-outline-secondary',
            'data-action' => 'sync-json',
        ]
    );
    echo html_writer::end_div();
    echo html_writer::end_tag('details');
    echo html_writer::start_div('commerce-showroom-dialog__actions');
    echo html_writer::tag('button', get_string('cancel'), [
        'type' => 'button', 'class' => 'btn btn-outline-secondary', 'data-action' => 'close-dialog',
    ]);
    echo html_writer::tag('button', '<i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> '
        . get_string('savechanges'), [
        'type' => 'submit', 'class' => 'btn btn-primary', 'data-action' => 'save-block',
    ]);
    echo html_writer::end_div();
    echo html_writer::end_tag('form');
    echo html_writer::end_tag('dialog');

}

echo html_writer::end_div();
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
