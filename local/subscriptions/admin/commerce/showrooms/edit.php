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
use local_subscriptions\commerce\showroom\cms\CommerceShowroomSocialImageService;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomProductLinkOptions;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomOfferConfig;
use local_subscriptions\commerce\showroom\cms\CommerceShowroomStatus;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;

require_login();
require_capability('local/subscriptions:manage_showrooms', context_system::instance());

$showroomsection = $showroomsection ?? 'information';
$id = optional_param('id', 0, PARAM_INT);
$context = context_system::instance();
$pageurl = new moodle_url('/local/subscriptions/admin/commerce/showrooms/edit.php', ['id' => $id]);
$sectionlabels = [
    'information' => 'Informations',
    'seo' => 'Référencement',
    'builder' => 'Builder',
];
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

    $settingsjson = optional_param('settingsjson', (string)($record->settingsjson ?? '{}'), PARAM_RAW);
    if ($showroomsection === 'seo') {
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
        $settingsjson = CommerceShowroomSeoConfig::merge_into_settings_json($settingsjson, $seovalues);
    }
    if ($showroomsection === 'information') {
        $offerconfig = [];
        foreach (CommerceShowroomOfferConfig::ROLES as $role) {
            $offerconfig[$role] = [
                'detailsenabled' => optional_param('offerdetails_' . $role, 0, PARAM_BOOL) === 1,
            ];
        }
        $settingsjson = CommerceShowroomOfferConfig::merge_into_settings_json($settingsjson, $offerconfig);
    }

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
        'slugfr' => $showroomsection === 'seo' ? optional_param('slugfr', '', PARAM_ALPHANUMEXT) : (string)($record->slugfr ?? ''),
        'slugen' => $showroomsection === 'seo' ? optional_param('slugen', '', PARAM_ALPHANUMEXT) : (string)($record->slugen ?? ''),
        'slugru' => $showroomsection === 'seo' ? optional_param('slugru', '', PARAM_ALPHANUMEXT) : (string)($record->slugru ?? ''),
        'titlekey' => optional_param('titlekey', '', PARAM_ALPHANUMEXT),
        'descriptionkey' => optional_param('descriptionkey', '', PARAM_ALPHANUMEXT),
        'productsjson' => $productsjson,
        'settingsjson' => $settingsjson,
    ], (int)$USER->id);

    $socialimageservice = new CommerceShowroomSocialImageService($context);
    if ($showroomsection === 'seo' && optional_param('socialimage_remove', 0, PARAM_BOOL) === 1) {
        $socialimageservice->delete($savedid);
    }
    if (
        $showroomsection === 'seo'
        && isset($_FILES['socialimage'])
        && (int)($_FILES['socialimage']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
    ) {
        $socialimageservice->store_uploaded_image($savedid, 'socialimage');
    }

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
$socialimageservice = new CommerceShowroomSocialImageService($context);
$currentsocialimageurl = $id > 0 ? $socialimageservice->get_url($id) : null;

$currentproducts = json_decode((string)$defaults->productsjson, true);
$currentproducts = is_array($currentproducts) ? $currentproducts : [];
$productoptions = $productlinkoptions->grouped_options($currentproducts);
$currentofferconfig = CommerceShowroomOfferConfig::from_settings_json((string)$defaults->settingsjson);

$blocks = $id > 0 ? $repository->blocks($id) : [];
$types = CommerceShowroomBlockTypeRegistry::definitions();

$builderconfig = null;
if ($id > 0 && $showroomsection === 'builder') {
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
            'commonpresentation' => get_string(
                'commerce_showroom_n95_common_presentation',
                'local_subscriptions'
            ),
            'commonpresentationhelp' => get_string(
                'commerce_showroom_n95_common_presentation_help',
                'local_subscriptions'
            ),
            'commonbackground' => get_string(
                'commerce_showroom_n95_common_background',
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
echo CrmBreadcrumbRenderer::render([
    [
        'label' => get_string('crm_commerce_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php'),
    ],
    [
        'label' => get_string('commerce_showroom_cms_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/showrooms/index.php'),
    ],
    [
        'label' => $record !== null ? format_string((string)$record->name) : $pagetitle,
        'url' => null,
    ],
]);

echo html_writer::start_div(
    'commerce-showroom-admin commerce-showroom-admin--crm'
);

echo html_writer::start_div(
    'commerce-showroom-information-header'
);
echo html_writer::start_div(
    'commerce-showroom-information-header__identity'
);

$showroomdisplayname = $record !== null
    ? format_string((string)$record->name)
    : get_string(
        'commerce_showroom_cms_edit',
        'local_subscriptions'
    );

$sectiontitlekey = match ($showroomsection) {
    'seo' => 'commerce_showroom_n941_seo_page_title',
    'builder' => 'commerce_showroom_n941_builder_page_title',
    default => 'commerce_showroom_n941_information_page_title',
};
$sectionsubtitlekey = match ($showroomsection) {
    'seo' => 'commerce_showroom_n941_seo_page_subtitle',
    'builder' => 'commerce_showroom_n941_builder_page_subtitle',
    default => 'commerce_showroom_n941_information_page_subtitle',
};

echo html_writer::start_div(
    'commerce-showroom-information-header__title-line'
);
echo $OUTPUT->heading(
    get_string(
        $sectiontitlekey,
        'local_subscriptions',
        $showroomdisplayname
    ),
    2,
    'mb-0'
);
if ($record !== null) {
    echo html_writer::span(
        CommerceShowroomStatus::label((string)$record->status),
        'badge rounded-pill bg-'
            . CommerceShowroomStatus::badge_class(
                (string)$record->status
            )
            . ' commerce-showroom-information-header__status'
    );
}
echo html_writer::end_div();

echo html_writer::tag(
    'p',
    get_string(
        $sectionsubtitlekey,
        'local_subscriptions'
    ),
    [
        'class' => 'commerce-showroom-information-header__subtitle '
            . 'text-muted mb-0',
    ]
);

echo html_writer::end_div();

if ($record !== null) {
    echo html_writer::start_div(
        'commerce-showroom-information-header__actions'
    );

    echo html_writer::link(
        new moodle_url(
            '/local/subscriptions/admin/commerce/showrooms/preview.php',
            ['id' => $id]
        ),
        '<i class="fa-solid fa-eye" aria-hidden="true"></i> '
            . get_string(
                'commerce_showroom_builder_preview',
                'local_subscriptions'
            ),
        [
            'class' => 'btn btn-outline-primary',
            'target' => '_blank',
            'rel' => 'noopener',
        ]
    );

    echo html_writer::start_tag('details', [
        'class' => 'commerce-showroom-information-actions-menu',
    ]);
    echo html_writer::tag(
        'summary',
        '<i class="fa-solid fa-ellipsis" aria-hidden="true"></i>',
        [
            'class' => 'btn btn-outline-secondary',
            'aria-label' => get_string('actions'),
            'title' => get_string('actions'),
        ]
    );
    echo html_writer::start_div(
        'commerce-showroom-information-actions-menu__panel'
    );

    echo html_writer::div(
        get_string(
            'commerce_showroom_n931_menu_transfer',
            'local_subscriptions'
        ),
        'commerce-showroom-information-actions-menu__heading'
    );

    echo html_writer::link(
        new moodle_url(
            '/local/subscriptions/admin/commerce/showrooms/export_portable_preflight.php',
            ['id' => $id]
        ),
        '<i class="fa-solid fa-box-archive" aria-hidden="true"></i> '
            . get_string(
                'commerce_showroom_export_portable',
                'local_subscriptions'
            ),
        ['class' => 'commerce-showroom-information-actions-menu__item']
    );
    echo html_writer::link(
        new moodle_url(
            '/local/subscriptions/admin/commerce/showrooms/export.php',
            ['id' => $id, 'sesskey' => sesskey()]
        ),
        '<i class="fa-solid fa-file-code" aria-hidden="true"></i> '
            . get_string(
                'commerce_showroom_export_json',
                'local_subscriptions'
            ),
        ['class' => 'commerce-showroom-information-actions-menu__item']
    );
    echo html_writer::link(
        new moodle_url(
            '/local/subscriptions/admin/commerce/showrooms/import.php'
        ),
        '<i class="fa-solid fa-file-import" aria-hidden="true"></i> '
            . get_string(
                'commerce_showroom_import',
                'local_subscriptions'
            ),
        ['class' => 'commerce-showroom-information-actions-menu__item']
    );

    echo html_writer::end_div();
    echo html_writer::end_tag('details');
    echo html_writer::end_div();
}
echo html_writer::end_div();

echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::SHOWROOMS,
    $context
);

if ($record !== null) {
    $tabs = [
        'information' => ['Informations', 'edit.php', 'fa-circle-info'],
        'seo' => ['Référencement', 'seo.php', 'fa-magnifying-glass'],
        'builder' => ['Builder', 'builder.php', 'fa-layer-group'],
        'history' => [get_string('commerce_showroom_history', 'local_subscriptions'), 'history.php', 'fa-clock-rotate-left'],
    ];
    echo html_writer::start_tag('nav', ['class' => 'commerce-showroom-subnav mb-4', 'aria-label' => 'Navigation du showroom']);
    foreach ($tabs as $key => [$label, $file, $icon]) {
        $active = $showroomsection === $key;
        echo html_writer::link(
            new moodle_url('/local/subscriptions/admin/commerce/showrooms/' . $file, ['id' => $id]),
            '<i class="fa-solid ' . $icon . '" aria-hidden="true"></i> ' . s($label),
            ['class' => 'commerce-showroom-subnav__item' . ($active ? ' is-active' : '')]
        );
    }
    echo html_writer::end_tag('nav');
}

if ($showroomsection !== 'builder') {
echo html_writer::start_tag('form', [
    'method' => 'post',
    'enctype' => 'multipart/form-data',
    'class' => 'card card-body mb-4 commerce-showroom-general-config',
]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

if ($showroomsection === 'information') {
echo html_writer::start_tag('section', [
    'class' => 'commerce-showroom-information-card',
]);
echo html_writer::start_div(
    'commerce-showroom-information-card__header'
);
echo html_writer::div(
    html_writer::tag(
        'i',
        '',
        [
            'class' => 'fa-solid fa-circle-info',
            'aria-hidden' => 'true',
        ]
    ),
    'commerce-showroom-information-card__icon'
);
echo html_writer::div(
    html_writer::tag(
        'h3',
        get_string(
            'commerce_showroom_n931_general_title',
            'local_subscriptions'
        ),
        ['class' => 'h5 mb-1']
    )
    . html_writer::tag(
        'p',
        get_string(
            'commerce_showroom_n931_general_help',
            'local_subscriptions'
        ),
        ['class' => 'text-muted mb-0']
    )
);
echo html_writer::end_div();

echo html_writer::start_div(
    'commerce-showroom-information-card__body'
);
echo html_writer::start_div('row g-3');

echo html_writer::start_div('col-12 col-xl-7');
echo html_writer::tag(
    'label',
    get_string('name'),
    ['for' => 'name', 'class' => 'form-label']
);
echo html_writer::empty_tag('input', [
    'id' => 'name',
    'name' => 'name',
    'value' => $defaults->name,
    'class' => 'form-control',
    'required' => true,
]);
echo html_writer::end_div();

echo html_writer::start_div('col-12 col-xl-5');
echo html_writer::tag(
    'label',
    get_string(
        'commerce_showroom_n931_public_layout',
        'local_subscriptions'
    ),
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
    get_string(
        'commerce_showroom_n931_public_layout_help',
        'local_subscriptions'
    ),
    ['class' => 'form-text']
);
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_tag('section');

}

if ($showroomsection === 'seo') {
$seolanguages = [
    'fr' => ['label' => 'Français', 'flag' => '🇫🇷'],
    'en' => ['label' => 'English', 'flag' => '🇬🇧'],
    'ru' => ['label' => 'Русский', 'flag' => '🇷🇺'],
];

echo html_writer::start_tag('section', [
    'class' => 'commerce-showroom-seo-image-card',
]);
echo html_writer::start_div(
    'commerce-showroom-seo-image-card__header'
);
echo html_writer::div(
    html_writer::tag('i', '', [
        'class' => 'fa-solid fa-image',
        'aria-hidden' => 'true',
    ]),
    'commerce-showroom-information-card__icon'
);
echo html_writer::div(
    html_writer::tag(
        'h4',
        get_string(
            'commerce_showroom_config_social_image',
            'local_subscriptions'
        ),
        ['class' => 'h6 mb-1']
    )
    . html_writer::tag(
        'p',
        get_string(
            'commerce_showroom_n932_social_image_help',
            'local_subscriptions'
        ),
        ['class' => 'text-muted mb-0']
    )
);
echo html_writer::end_div();

echo html_writer::start_div(
    'commerce-showroom-seo-image-card__body'
);
echo html_writer::start_div(
    'commerce-showroom-seo-image-card__preview'
);
if ($currentsocialimageurl !== null) {
    echo html_writer::empty_tag('img', [
        'src' => $currentsocialimageurl->out(false),
        'alt' => '',
    ]);
} else {
    echo html_writer::div(
        html_writer::tag('i', '', [
            'class' => 'fa-regular fa-image',
            'aria-hidden' => 'true',
        ])
        . html_writer::span(
            get_string(
                'commerce_showroom_n932_no_social_image',
                'local_subscriptions'
            )
        ),
        'commerce-showroom-seo-image-card__empty'
    );
}
echo html_writer::end_div();

echo html_writer::start_div(
    'commerce-showroom-seo-image-card__controls'
);
echo html_writer::tag(
    'label',
    get_string(
        'commerce_showroom_config_social_image_choose',
        'local_subscriptions'
    ),
    [
        'for' => 'socialimage',
        'class' => 'form-label',
    ]
);
echo html_writer::empty_tag('input', [
    'type' => 'file',
    'id' => 'socialimage',
    'name' => 'socialimage',
    'accept' => 'image/png,image/jpeg,image/webp',
    'class' => 'form-control',
]);
echo html_writer::tag(
    'div',
    get_string(
        'commerce_showroom_config_social_image_format_help',
        'local_subscriptions'
    ),
    ['class' => 'form-text']
);
if ($currentsocialimageurl !== null) {
    echo html_writer::start_div('form-check mt-3');
    echo html_writer::empty_tag('input', [
        'type' => 'checkbox',
        'id' => 'socialimage_remove',
        'name' => 'socialimage_remove',
        'value' => '1',
        'class' => 'form-check-input',
    ]);
    echo html_writer::tag(
        'label',
        get_string(
            'commerce_showroom_config_social_image_remove',
            'local_subscriptions'
        ),
        [
            'for' => 'socialimage_remove',
            'class' => 'form-check-label',
        ]
    );
    echo html_writer::end_div();
}
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_tag('section');

echo html_writer::start_tag('section', [
    'class' => 'commerce-showroom-seo-locales',
    'data-region' => 'showroom-seo-locales',
]);

echo html_writer::start_div(
    'commerce-showroom-seo-locales__tabs',
    ['role' => 'tablist']
);
foreach ($seolanguages as $language => $languageconfig) {
    echo html_writer::tag(
        'button',
        html_writer::span(
            $languageconfig['flag'],
            'commerce-showroom-seo-locales__flag'
        )
        . html_writer::span($languageconfig['label']),
        [
            'type' => 'button',
            'class' => 'commerce-showroom-seo-locales__tab'
                . ($language === 'fr' ? ' is-active' : ''),
            'data-seo-language' => $language,
            'role' => 'tab',
            'aria-selected' => $language === 'fr'
                ? 'true'
                : 'false',
        ]
    );
}
echo html_writer::end_div();

foreach ($seolanguages as $language => $languageconfig) {
    $slugfield = 'slug' . $language;
    $slugvalue = (string)$defaults->{$slugfield};
    $publicurl = $slugvalue !== ''
        ? (new moodle_url('/' . ltrim($slugvalue, '/')))->out(false)
        : '';

    echo html_writer::start_div(
        'commerce-showroom-seo-locale'
            . ($language === 'fr' ? ' is-active' : ''),
        [
            'data-seo-panel' => $language,
            'role' => 'tabpanel',
        ]
    );

    echo html_writer::start_div(
        'commerce-showroom-seo-locale__heading'
    );
    echo html_writer::tag(
        'h4',
        $languageconfig['flag']
            . ' '
            . $languageconfig['label'],
        ['class' => 'h5 mb-1']
    );
    echo html_writer::tag(
        'p',
        get_string(
            'commerce_showroom_n932_locale_help',
            'local_subscriptions'
        ),
        ['class' => 'text-muted mb-0']
    );
    echo html_writer::end_div();

    echo html_writer::start_div(
        'commerce-showroom-seo-locale__grid'
    );

    echo html_writer::start_div(
        'commerce-showroom-seo-field is-url'
    );
    echo html_writer::tag(
        'label',
        get_string(
            'commerce_showroom_n932_public_url',
            'local_subscriptions'
        ),
        [
            'for' => $slugfield,
            'class' => 'form-label',
        ]
    );
    echo html_writer::start_div('input-group');
    echo html_writer::span(
        '/',
        'input-group-text'
    );
    echo html_writer::empty_tag('input', [
        'id' => $slugfield,
        'name' => $slugfield,
        'value' => $slugvalue,
        'class' => 'form-control',
        'data-seo-slug' => $language,
    ]);
    if ($publicurl !== '') {
        echo html_writer::link(
            $publicurl,
            html_writer::tag('i', '', [
                'class' => 'fa-solid fa-arrow-up-right-from-square',
                'aria-hidden' => 'true',
            ]),
            [
                'class' => 'btn btn-outline-secondary',
                'target' => '_blank',
                'rel' => 'noopener',
                'title' => get_string(
                    'commerce_showroom_n932_open_public_page',
                    'local_subscriptions'
                ),
            ]
        );
    }
    echo html_writer::end_div();
    echo html_writer::tag(
        'div',
        get_string(
            'commerce_showroom_n932_public_url_help',
            'local_subscriptions'
        ),
        ['class' => 'form-text']
    );
    echo html_writer::end_div();

    echo html_writer::start_div(
        'commerce-showroom-seo-field'
    );
    echo html_writer::start_div(
        'commerce-showroom-seo-field__label'
    );
    echo html_writer::tag(
        'label',
        get_string(
            'commerce_showroom_config_seo_title',
            'local_subscriptions'
        ),
        [
            'for' => 'seotitle_' . $language,
            'class' => 'form-label mb-0',
        ]
    );
    echo html_writer::span(
        '',
        'commerce-showroom-seo-counter',
        [
            'data-counter-for' => 'seotitle_' . $language,
            'data-counter-max' => '70',
        ]
    );
    echo html_writer::end_div();
    echo html_writer::empty_tag('input', [
        'id' => 'seotitle_' . $language,
        'name' => 'seotitle_' . $language,
        'value' => $currentseo[$language]['title'],
        'class' => 'form-control',
        'maxlength' => 70,
    ]);
    echo html_writer::tag(
        'div',
        get_string(
            'commerce_showroom_config_seo_title_help',
            'local_subscriptions'
        ),
        ['class' => 'form-text']
    );
    echo html_writer::end_div();

    echo html_writer::start_div(
        'commerce-showroom-seo-field'
    );
    echo html_writer::start_div(
        'commerce-showroom-seo-field__label'
    );
    echo html_writer::tag(
        'label',
        get_string(
            'commerce_showroom_config_seo_description',
            'local_subscriptions'
        ),
        [
            'for' => 'seodescription_' . $language,
            'class' => 'form-label mb-0',
        ]
    );
    echo html_writer::span(
        '',
        'commerce-showroom-seo-counter',
        [
            'data-counter-for' => 'seodescription_' . $language,
            'data-counter-max' => '180',
        ]
    );
    echo html_writer::end_div();
    echo html_writer::tag(
        'textarea',
        s($currentseo[$language]['description']),
        [
            'id' => 'seodescription_' . $language,
            'name' => 'seodescription_' . $language,
            'rows' => 4,
            'maxlength' => 180,
            'class' => 'form-control',
        ]
    );
    echo html_writer::end_div();

    echo html_writer::end_div();

    echo html_writer::start_tag('details', [
        'class' => 'commerce-showroom-seo-social',
    ]);
    echo html_writer::tag(
        'summary',
        '<i class="fa-solid fa-share-nodes" aria-hidden="true"></i> '
            . get_string(
                'commerce_showroom_n932_social_overrides',
                'local_subscriptions'
            )
    );
    echo html_writer::start_div(
        'commerce-showroom-seo-social__body'
    );

    echo html_writer::tag(
        'p',
        get_string(
            'commerce_showroom_n932_social_overrides_help',
            'local_subscriptions'
        ),
        ['class' => 'text-muted']
    );

    echo html_writer::tag(
        'label',
        get_string(
            'commerce_showroom_config_seo_social_title',
            'local_subscriptions'
        ),
        [
            'for' => 'seosocialtitle_' . $language,
            'class' => 'form-label',
        ]
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
        get_string(
            'commerce_showroom_config_seo_social_description',
            'local_subscriptions'
        ),
        [
            'for' => 'seosocialdescription_' . $language,
            'class' => 'form-label mt-3',
        ]
    );
    echo html_writer::tag(
        'textarea',
        s($currentseo[$language]['socialdescription']),
        [
            'id' => 'seosocialdescription_' . $language,
            'name' => 'seosocialdescription_' . $language,
            'rows' => 3,
            'maxlength' => 220,
            'class' => 'form-control',
        ]
    );

    echo html_writer::tag(
        'label',
        get_string(
            'commerce_showroom_config_seo_keywords',
            'local_subscriptions'
        ),
        [
            'for' => 'seokeywords_' . $language,
            'class' => 'form-label mt-3',
        ]
    );
    echo html_writer::empty_tag('input', [
        'id' => 'seokeywords_' . $language,
        'name' => 'seokeywords_' . $language,
        'value' => $currentseo[$language]['keywords'],
        'class' => 'form-control',
    ]);
    echo html_writer::tag(
        'div',
        get_string(
            'commerce_showroom_config_seo_keywords_help',
            'local_subscriptions'
        ),
        ['class' => 'form-text']
    );

    echo html_writer::end_div();
    echo html_writer::end_tag('details');

    echo html_writer::end_div();
}
echo html_writer::end_tag('section');

echo html_writer::start_tag('details', [
    'class' => 'commerce-showroom-seo-advanced',
]);
echo html_writer::tag(
    'summary',
    '<i class="fa-solid fa-code" aria-hidden="true"></i> '
        . get_string(
            'commerce_showroom_n932_technical',
            'local_subscriptions'
        )
);
echo html_writer::start_div(
    'commerce-showroom-seo-advanced__body'
);
echo html_writer::tag(
    'p',
    get_string(
        'commerce_showroom_n932_technical_help',
        'local_subscriptions'
    ),
    ['class' => 'text-muted']
);

foreach ([
    'titlekey' => get_string(
        'commerce_showroom_config_titlekey_legacy',
        'local_subscriptions'
    ),
    'descriptionkey' => get_string(
        'commerce_showroom_config_descriptionkey_legacy',
        'local_subscriptions'
    ),
] as $name => $label) {
    echo html_writer::tag(
        'label',
        $label,
        [
            'for' => $name,
            'class' => 'form-label'
                . ($name === 'descriptionkey' ? ' mt-3' : ''),
        ]
    );
    echo html_writer::empty_tag('input', [
        'id' => $name,
        'name' => $name,
        'value' => $defaults->{$name},
        'class' => 'form-control font-monospace',
    ]);
}

echo html_writer::end_div();
echo html_writer::end_tag('details');

$PAGE->requires->js_init_code(<<<'JS'
(function() {
    var root = document.querySelector(
        '[data-region="showroom-seo-locales"]'
    );
    if (!root) {
        return;
    }

    var tabs = Array.prototype.slice.call(
        root.querySelectorAll('[data-seo-language]')
    );
    var panels = Array.prototype.slice.call(
        root.querySelectorAll('[data-seo-panel]')
    );

    function activate(language) {
        tabs.forEach(function(tab) {
            var active =
                tab.getAttribute('data-seo-language') === language;
            tab.classList.toggle('is-active', active);
            tab.setAttribute(
                'aria-selected',
                active ? 'true' : 'false'
            );
        });
        panels.forEach(function(panel) {
            panel.classList.toggle(
                'is-active',
                panel.getAttribute('data-seo-panel') === language
            );
        });
    }

    tabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            activate(
                tab.getAttribute('data-seo-language')
            );
        });
    });

    document.querySelectorAll('[data-counter-for]').forEach(
        function(counter) {
            var field = document.getElementById(
                counter.getAttribute('data-counter-for')
            );
            if (!field) {
                return;
            }
            var max = parseInt(
                counter.getAttribute('data-counter-max'),
                10
            ) || 0;
            function update() {
                counter.textContent =
                    field.value.length + ' / ' + max;
                counter.classList.toggle(
                    'is-near-limit',
                    max > 0 && field.value.length >= max * .85
                );
            }
            field.addEventListener('input', update);
            update();
        }
    );
})();
JS);

}

if ($showroomsection === 'information') {
$productfields = [
    'linkedcourse' => [
        'label' => get_string(
            'commerce_showroom_config_product_course',
            'local_subscriptions'
        ),
        'key' => 'course',
        'icon' => 'fa-graduation-cap',
    ],
    'linkedpdf' => [
        'label' => get_string(
            'commerce_showroom_config_product_pdf',
            'local_subscriptions'
        ),
        'key' => 'pdf',
        'icon' => 'fa-file-pdf',
    ],
    'linkedbundle' => [
        'label' => get_string(
            'commerce_showroom_config_product_bundle',
            'local_subscriptions'
        ),
        'key' => 'bundle',
        'icon' => 'fa-box-open',
    ],
];

echo html_writer::start_tag('section', [
    'class' => 'commerce-showroom-information-card',
]);
echo html_writer::start_div(
    'commerce-showroom-information-card__header'
);
echo html_writer::div(
    html_writer::tag('i', '', [
        'class' => 'fa-solid fa-link',
        'aria-hidden' => 'true',
    ]),
    'commerce-showroom-information-card__icon'
);
echo html_writer::div(
    html_writer::tag(
        'h3',
        get_string(
            'commerce_showroom_config_products',
            'local_subscriptions'
        ),
        ['class' => 'h5 mb-1']
    )
    . html_writer::tag(
        'p',
        get_string(
            'commerce_showroom_n931_products_help',
            'local_subscriptions'
        ),
        ['class' => 'text-muted mb-0']
    )
);
echo html_writer::end_div();

echo html_writer::start_div(
    'commerce-showroom-information-products'
);

foreach ($productfields as $name => $definition) {
    $key = $definition['key'];
    $selected = (string)($currentproducts[$key] ?? '');
    $selectedlabel = $selected !== ''
        && isset($productoptions[$key][$selected])
            ? (string)$productoptions[$key][$selected]
            : get_string(
                'commerce_showroom_config_product_none',
                'local_subscriptions'
            );

    echo html_writer::start_div(
        'commerce-showroom-information-product'
    );
    echo html_writer::div(
        html_writer::tag('i', '', [
            'class' => 'fa-solid ' . $definition['icon'],
            'aria-hidden' => 'true',
        ]),
        'commerce-showroom-information-product__icon'
    );

    echo html_writer::start_div(
        'commerce-showroom-information-product__main'
    );
    echo html_writer::span(
        $definition['label'],
        'commerce-showroom-information-product__type'
    );
    echo html_writer::tag(
        'strong',
        s($selectedlabel),
        ['class' => 'commerce-showroom-information-product__name']
    );

    echo html_writer::start_div(
        'commerce-showroom-information-product__details'
    );
    echo html_writer::start_div('form-check form-switch');
    echo html_writer::empty_tag('input', [
        'type' => 'checkbox',
        'class' => 'form-check-input',
        'id' => 'offerdetails_' . $key,
        'name' => 'offerdetails_' . $key,
        'value' => '1',
        'checked' => !empty(
            $currentofferconfig[$key]['detailsenabled']
        ) ? 'checked' : null,
    ]);
    echo html_writer::tag(
        'label',
        get_string(
            'commerce_showroom_config_offer_details_enabled',
            'local_subscriptions'
        ),
        [
            'for' => 'offerdetails_' . $key,
            'class' => 'form-check-label',
        ]
    );
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();

    echo html_writer::start_tag('details', [
        'class' => 'commerce-showroom-information-product__change',
    ]);
    echo html_writer::tag(
        'summary',
        '<i class="fa-solid fa-pen" aria-hidden="true"></i> '
            . get_string(
                'commerce_showroom_n931_change_product',
                'local_subscriptions'
            ),
        ['class' => 'btn btn-sm btn-outline-secondary']
    );
    echo html_writer::div(
        html_writer::select(
            $productoptions[$key],
            $name,
            $selected,
            [
                '' => get_string(
                    'commerce_showroom_config_product_none',
                    'local_subscriptions'
                ),
            ],
            [
                'id' => $name,
                'class' => 'form-select',
            ]
        ),
        'commerce-showroom-information-product__selector'
    );
    echo html_writer::end_tag('details');

    echo html_writer::end_div();
}
echo html_writer::end_div();
echo html_writer::end_tag('section');

echo html_writer::start_tag('details', [
    'class' => 'commerce-showroom-information-advanced',
]);
echo html_writer::tag(
    'summary',
    '<i class="fa-solid fa-sliders" aria-hidden="true"></i> '
        . get_string(
            'commerce_showroom_config_advanced',
            'local_subscriptions'
        )
);
echo html_writer::start_div(
    'commerce-showroom-information-advanced__body'
);

echo html_writer::tag(
    'label',
    get_string(
        'commerce_showroom_cms_key',
        'local_subscriptions'
    ),
    [
        'for' => 'showroomkey',
        'class' => 'form-label',
    ]
);
echo html_writer::empty_tag('input', [
    'id' => 'showroomkey',
    'name' => 'showroomkey',
    'value' => $defaults->showroomkey,
    'class' => 'form-control font-monospace',
    'required' => true,
]);
echo html_writer::tag(
    'div',
    get_string(
        'commerce_showroom_config_key_help',
        'local_subscriptions'
    ),
    ['class' => 'form-text mb-3']
);

echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'titlekey',
    'value' => $defaults->titlekey,
]);
echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'descriptionkey',
    'value' => $defaults->descriptionkey,
]);
echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'settingsjson',
    'value' => $defaults->settingsjson,
]);

echo html_writer::end_div();
echo html_writer::end_tag('details');

}

// Preserve fields owned by the other section when saving a split form.
if ($showroomsection === 'seo') {
    foreach (['showroomkey', 'name', 'template', 'productsjson', 'settingsjson'] as $field) {
        $value = $field === 'productsjson' ? (string)$defaults->productsjson : (string)($defaults->{$field} ?? '');
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $field, 'value' => $value]);
    }
    foreach (['linkedcourse' => 'course', 'linkedpdf' => 'pdf', 'linkedbundle' => 'bundle'] as $field => $key) {
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $field, 'value' => (string)($currentproducts[$key] ?? '')]);
    }
}

echo html_writer::div(
    html_writer::tag(
        'button',
        get_string('savechanges'),
        [
            'type' => 'submit',
            'class' => 'btn btn-primary',
        ]
    ),
    'commerce-showroom-information-save'
);
echo html_writer::end_tag('form');

if ($showroomsection === 'information' && $record !== null) {
    echo html_writer::start_tag('section', [
        'class' => 'commerce-showroom-information-card '
            . 'commerce-showroom-information-publication',
    ]);
    echo html_writer::start_div(
        'commerce-showroom-information-card__header'
    );
    echo html_writer::div(
        html_writer::tag('i', '', [
            'class' => 'fa-solid fa-bullhorn',
            'aria-hidden' => 'true',
        ]),
        'commerce-showroom-information-card__icon'
    );
    echo html_writer::div(
        html_writer::tag(
            'h3',
            get_string(
                'commerce_showroom_n931_publication_title',
                'local_subscriptions'
            ),
            ['class' => 'h5 mb-1']
        )
        . html_writer::tag(
            'p',
            get_string(
                'commerce_showroom_n931_publication_help',
                'local_subscriptions'
            ),
            ['class' => 'text-muted mb-0']
        )
    );
    echo html_writer::end_div();

    echo html_writer::start_tag('form', [
        'method' => 'post',
        'class' => 'commerce-showroom-information-publication__form',
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'sesskey',
        'value' => sesskey(),
    ]);

    echo html_writer::start_div(
        'commerce-showroom-information-publication__status'
    );
    echo html_writer::span(
        get_string(
            'commerce_showroom_n931_publication_status',
            'local_subscriptions'
        ),
        'commerce-showroom-information-publication__label'
    );
    echo html_writer::span(
        CommerceShowroomStatus::label((string)$record->status),
        'badge rounded-pill bg-'
            . CommerceShowroomStatus::badge_class(
                (string)$record->status
            )
    );
    echo html_writer::span(
        get_string(
            'commerce_showroom_n931_publication_status_'
                . (string)$record->status,
            'local_subscriptions'
        ),
        'commerce-showroom-information-publication__description'
    );
    echo html_writer::end_div();

    echo html_writer::start_div(
        'commerce-showroom-information-publication__workflow'
    );
    echo html_writer::div(
        html_writer::tag(
            'label',
            get_string(
                'commerce_showroom_revision_note',
                'local_subscriptions'
            ),
            [
                'for' => 'workflownote',
                'class' => 'form-label',
            ]
        )
        . html_writer::empty_tag('input', [
            'type' => 'text',
            'id' => 'workflownote',
            'name' => 'workflownote',
            'class' => 'form-control',
            'placeholder' => get_string(
                'commerce_showroom_n931_publication_note_placeholder',
                'local_subscriptions'
            ),
        ]),
        'commerce-showroom-information-publication__note'
    );

    echo html_writer::start_div(
        'commerce-showroom-information-publication__actions'
    );
    if ($record->status === 'draft') {
        echo html_writer::tag(
            'button',
            get_string(
                'commerce_showroom_submit_review',
                'local_subscriptions'
            ),
            [
                'type' => 'submit',
                'name' => 'workflowaction',
                'value' => 'review',
                'class' => 'btn btn-warning',
            ]
        );
    } else if ($record->status === 'review') {
        echo html_writer::tag(
            'button',
            get_string(
                'commerce_showroom_publish',
                'local_subscriptions'
            ),
            [
                'type' => 'submit',
                'name' => 'workflowaction',
                'value' => 'publish',
                'class' => 'btn btn-success',
            ]
        );
        echo html_writer::tag(
            'button',
            get_string(
                'commerce_showroom_return_draft',
                'local_subscriptions'
            ),
            [
                'type' => 'submit',
                'name' => 'workflowaction',
                'value' => 'draft',
                'class' => 'btn btn-outline-secondary',
            ]
        );
    } else if ($record->status === 'published') {
        echo html_writer::tag(
            'button',
            get_string(
                'commerce_showroom_return_draft',
                'local_subscriptions'
            ),
            [
                'type' => 'submit',
                'name' => 'workflowaction',
                'value' => 'draft',
                'class' => 'btn btn-outline-secondary',
            ]
        );
    }
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_tag('form');
    echo html_writer::end_tag('section');
}
}

if ($id > 0 && $showroomsection === 'builder') {
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
    echo html_writer::start_div(
        'commerce-showroom-builder__header'
    );
    echo html_writer::start_div(
        'commerce-showroom-builder__heading'
    );
    echo html_writer::tag(
        'h3',
        get_string(
            'commerce_showroom_cms_blocks',
            'local_subscriptions'
        ),
        ['class' => 'mb-0']
    );
    echo html_writer::end_div();

    echo html_writer::div(
        get_string(
            'commerce_showroom_n94_block_count',
            'local_subscriptions',
            count($blocks)
        ),
        'commerce-showroom-builder__count'
    );
    echo html_writer::end_div();

    echo html_writer::start_div(
        'commerce-showroom-builder__toolbar'
    );

    $options = [];
    foreach ($types as $type => $definition) {
        if (
            array_key_exists('addable', $definition)
            && !$definition['addable']
        ) {
            continue;
        }
        $options[$type] = $definition['label'];
    }

    echo html_writer::start_div(
        'commerce-showroom-builder__toolbar-primary'
    );
    echo html_writer::start_div(
        'commerce-showroom-builder__tool-group is-primary'
    );
    echo html_writer::div(
        html_writer::tag('i', '', [
            'class' => 'fa-solid fa-plus',
            'aria-hidden' => 'true',
        ]),
        'commerce-showroom-builder__tool-icon'
    );
    echo html_writer::start_div(
        'commerce-showroom-builder__tool-content'
    );
    echo html_writer::span(
        get_string(
            'commerce_showroom_n94_add_block_title',
            'local_subscriptions'
        ),
        'commerce-showroom-builder__tool-title'
    );
    echo html_writer::start_div(
        'commerce-showroom-builder__tool-controls'
    );
    echo html_writer::select(
        $options,
        'blocktype',
        '',
        [
            '' => get_string(
                'commerce_showroom_builder_choose_block',
                'local_subscriptions'
            ),
        ],
        [
            'class' => 'form-select',
            'data-role' => 'block-type',
        ]
    );
    echo html_writer::tag(
        'button',
        '<i class="fa-solid fa-plus" aria-hidden="true"></i> '
            . get_string('add'),
        [
            'type' => 'button',
            'class' => 'btn btn-primary',
            'data-action' => 'add-block',
        ]
    );
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();

    $templateoptions = [];
    foreach (
        CommerceShowroomPageTemplateRegistry::definitions()
        as $templatekey => $templatedefinition
    ) {
        $templateoptions[$templatekey] =
            $templatedefinition['label'];
    }

    echo html_writer::start_div(
        'commerce-showroom-builder__tool-group'
    );
    echo html_writer::div(
        html_writer::tag('i', '', [
            'class' => 'fa-solid fa-wand-magic-sparkles',
            'aria-hidden' => 'true',
        ]),
        'commerce-showroom-builder__tool-icon'
    );
    echo html_writer::start_div(
        'commerce-showroom-builder__tool-content'
    );
    echo html_writer::span(
        get_string(
            'commerce_showroom_n94_template_title',
            'local_subscriptions'
        ),
        'commerce-showroom-builder__tool-title'
    );
    echo html_writer::start_div(
        'commerce-showroom-builder__tool-controls'
    );
    echo html_writer::select(
        $templateoptions,
        'pagetemplate',
        '',
        [
            '' => get_string(
                'commerce_showroom_choose_template',
                'local_subscriptions'
            ),
        ],
        [
            'class' => 'form-select',
            'data-role' => 'page-template',
        ]
    );
    echo html_writer::tag(
        'button',
        get_string(
            'commerce_showroom_apply_template',
            'local_subscriptions'
        ),
        [
            'type' => 'button',
            'class' => 'btn btn-outline-primary',
            'data-action' => 'apply-template',
        ]
    );
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();

    echo html_writer::start_div(
        'commerce-showroom-builder__toolbar-secondary'
    );

    echo html_writer::start_tag('details', [
        'class' => 'commerce-showroom-builder__more-menu',
    ]);
    echo html_writer::tag(
        'summary',
        '<i class="fa-solid fa-ellipsis" aria-hidden="true"></i> '
            . get_string(
                'commerce_showroom_n94_more_actions',
                'local_subscriptions'
            ),
        ['class' => 'btn btn-outline-secondary']
    );
    echo html_writer::start_div(
        'commerce-showroom-builder__more-menu-panel'
    );
    echo html_writer::tag(
        'button',
        '<i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i> '
            . get_string(
                'commerce_showroom_builder_initialise_defaults',
                'local_subscriptions'
            ),
        [
            'type' => 'button',
            'class' => 'commerce-showroom-builder__menu-action',
            'data-action' => 'initialise-defaults',
        ]
    );
    echo html_writer::tag(
        'button',
        '<i class="fa-solid fa-compress" aria-hidden="true"></i> '
            . get_string(
                'commerce_showroom_n94_collapse_all',
                'local_subscriptions'
            ),
        [
            'type' => 'button',
            'class' => 'commerce-showroom-builder__menu-action',
            'data-action' => 'collapse-all',
        ]
    );
    echo html_writer::tag(
        'button',
        '<i class="fa-solid fa-expand" aria-hidden="true"></i> '
            . get_string(
                'commerce_showroom_n94_expand_all',
                'local_subscriptions'
            ),
        [
            'type' => 'button',
            'class' => 'commerce-showroom-builder__menu-action',
            'data-action' => 'expand-all',
        ]
    );
    echo html_writer::end_div();
    echo html_writer::end_tag('details');

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
        echo html_writer::start_div(
            'commerce-showroom-block__text'
        );
        echo html_writer::start_div(
            'commerce-showroom-block__title-line'
        );
        echo html_writer::tag(
            'strong',
            s($definition['label']),
            ['class' => 'commerce-showroom-block__label']
        );
        echo html_writer::span(
            $block->enabled
                ? get_string(
                    'commerce_showroom_n94_visible',
                    'local_subscriptions'
                )
                : get_string(
                    'commerce_showroom_n94_hidden',
                    'local_subscriptions'
                ),
            'badge rounded-pill '
                . ($block->enabled
                    ? 'bg-success-subtle text-success'
                    : 'bg-secondary-subtle text-secondary')
                . ' commerce-showroom-block__state'
        );
        echo html_writer::end_div();
        if ($summary !== '') {
            echo html_writer::tag(
                'span',
                s($summary),
                ['class' => 'commerce-showroom-block__summary']
            );
        }
        echo html_writer::end_div();
        echo html_writer::end_div();

        $presentationbits = [];
        $presentationlabels = [
            'sectionwidth' => [
                'label' => get_string(
                    'commerce_showroom_n95_width',
                    'local_subscriptions'
                ),
                'values' => [
                    'contained' => get_string(
                        'commerce_showroom_n95_width_contained',
                        'local_subscriptions'
                    ),
                    'wide' => get_string(
                        'commerce_showroom_n95_width_wide',
                        'local_subscriptions'
                    ),
                    'full' => get_string(
                        'commerce_showroom_n95_width_full',
                        'local_subscriptions'
                    ),
                ],
            ],
            'sectionbackground' => [
                'label' => get_string(
                    'commerce_showroom_n95_background',
                    'local_subscriptions'
                ),
                'values' => [
                    'default' => get_string(
                        'commerce_showroom_n95_background_default',
                        'local_subscriptions'
                    ),
                    'white' => get_string(
                        'commerce_showroom_n95_background_white',
                        'local_subscriptions'
                    ),
                    'light' => get_string(
                        'commerce_showroom_n95_background_light',
                        'local_subscriptions'
                    ),
                    'soft' => get_string(
                        'commerce_showroom_n95_background_soft',
                        'local_subscriptions'
                    ),
                    'campuspink' => get_string(
                        'commerce_showroom_n95_background_pink',
                        'local_subscriptions'
                    ),
                    'dark' => get_string(
                        'commerce_showroom_n95_background_dark',
                        'local_subscriptions'
                    ),
                    'gradient' => get_string(
                        'commerce_showroom_n95_background_gradient',
                        'local_subscriptions'
                    ),
                    'custom' => get_string(
                        'commerce_showroom_n95_background_custom',
                        'local_subscriptions'
                    ),
                    'image' => get_string(
                        'commerce_showroom_n95_background_image',
                        'local_subscriptions'
                    ),
                ],
            ],
            'sectionspacing' => [
                'label' => get_string(
                    'commerce_showroom_n95_spacing',
                    'local_subscriptions'
                ),
                'values' => [
                    'compact' => get_string(
                        'commerce_showroom_n95_spacing_compact',
                        'local_subscriptions'
                    ),
                    'normal' => get_string(
                        'commerce_showroom_n95_spacing_normal',
                        'local_subscriptions'
                    ),
                    'large' => get_string(
                        'commerce_showroom_n95_spacing_large',
                        'local_subscriptions'
                    ),
                ],
            ],
        ];
        foreach ($presentationlabels as $field => $meta) {
            $value = (string)($config[$field] ?? '');
            if ($value === '') {
                continue;
            }
            $presentationbits[] = html_writer::span(
                html_writer::span(
                    $meta['label'],
                    'commerce-showroom-block__expanded-label'
                )
                . html_writer::span(
                    $meta['values'][$value] ?? $value,
                    'commerce-showroom-block__expanded-value'
                ),
                'commerce-showroom-block__expanded-chip'
            );
        }

        echo html_writer::div(
            ($summary !== ''
                ? html_writer::div(
                    s($summary),
                    'commerce-showroom-block__expanded-summary'
                )
                : '')
            . html_writer::div(
                implode('', $presentationbits),
                'commerce-showroom-block__expanded-chips'
            ),
            'commerce-showroom-block__expanded-info'
        );

        echo html_writer::start_div(
            'commerce-showroom-block__actions'
        );
        echo html_writer::tag(
            'button',
            '<i class="fa-solid fa-pen" aria-hidden="true"></i> '
                . get_string('edit'),
            [
                'type' => 'button',
                'class' => 'btn btn-sm btn-outline-primary',
                'data-action' => 'edit-block',
                'title' => get_string('edit'),
            ]
        );

        echo html_writer::tag(
            'button',
            '<i class="fa-solid fa-chevron-down" aria-hidden="true"></i>',
            [
                'type' => 'button',
                'class' => 'btn btn-sm btn-outline-secondary',
                'data-action' => 'collapse-block',
                'aria-expanded' => 'true',
                'title' => get_string(
                    'commerce_showroom_n94_collapse_block',
                    'local_subscriptions'
                ),
            ]
        );

        echo html_writer::start_tag('details', [
            'class' => 'commerce-showroom-block__more',
        ]);
        echo html_writer::tag(
            'summary',
            '<i class="fa-solid fa-ellipsis" aria-hidden="true"></i>',
            [
                'class' => 'btn btn-sm btn-outline-secondary',
                'aria-label' => get_string('actions'),
                'title' => get_string('actions'),
            ]
        );
        echo html_writer::start_div(
            'commerce-showroom-block__more-panel'
        );

        echo html_writer::tag(
            'button',
            '<i class="fa-solid fa-copy" aria-hidden="true"></i> '
                . get_string('duplicate'),
            [
                'type' => 'button',
                'class' => 'commerce-showroom-block__menu-action',
                'data-action' => 'duplicate-block',
            ]
        );
        echo html_writer::tag(
            'button',
            '<i class="fa-solid '
                . ($block->enabled ? 'fa-eye-slash' : 'fa-eye')
                . '" aria-hidden="true"></i> '
                . (
                    $block->enabled
                        ? get_string(
                            'commerce_showroom_n94_hide_block',
                            'local_subscriptions'
                        )
                        : get_string(
                            'commerce_showroom_n94_show_block',
                            'local_subscriptions'
                        )
                ),
            [
                'type' => 'button',
                'class' => 'commerce-showroom-block__menu-action',
                'data-action' => 'toggle-block',
            ]
        );

        echo html_writer::start_tag('details', [
            'class' => 'commerce-showroom-block__technical',
        ]);
        echo html_writer::tag(
            'summary',
            '<i class="fa-solid fa-code" aria-hidden="true"></i> '
                . get_string(
                    'commerce_showroom_n94_technical_info',
                    'local_subscriptions'
                )
        );
        echo html_writer::div(
            html_writer::span(
                get_string(
                    'commerce_showroom_n94_block_key',
                    'local_subscriptions'
                ),
                'commerce-showroom-block__technical-label'
            )
            . html_writer::tag(
                'code',
                s($block->blockkey)
            ),
            'commerce-showroom-block__technical-body'
        );
        echo html_writer::end_tag('details');

        echo html_writer::tag(
            'button',
            '<i class="fa-solid fa-trash" aria-hidden="true"></i> '
                . get_string('delete'),
            [
                'type' => 'button',
                'class' =>
                    'commerce-showroom-block__menu-action text-danger',
                'data-action' => 'delete-block',
            ]
        );

        echo html_writer::end_div();
        echo html_writer::end_tag('details');
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
$PAGE->requires->js_init_code(<<<'JS'
(function() {
    var menuSelector =
        '.commerce-showroom-builder__more-menu, '
        + '.commerce-showroom-block__more';

    function panelFor(menu) {
        return menu.querySelector(
            ':scope > .commerce-showroom-builder__more-menu-panel, '
            + ':scope > .commerce-showroom-block__more-panel'
        );
    }

    function positionMenu(menu) {
        if (!menu.open) {
            return;
        }

        var summary = menu.querySelector(':scope > summary');
        var panel = panelFor(menu);
        if (!summary || !panel) {
            return;
        }

        panel.style.visibility = 'hidden';
        panel.style.display = 'block';
        panel.style.position = 'fixed';
        panel.style.left = '0px';
        panel.style.top = '0px';

        var trigger = summary.getBoundingClientRect();
        var panelRect = panel.getBoundingClientRect();
        var gap = 6;
        var edge = 12;

        var left = trigger.right - panelRect.width;
        left = Math.max(
            edge,
            Math.min(
                left,
                window.innerWidth - panelRect.width - edge
            )
        );

        var top = trigger.bottom + gap;
        if (
            top + panelRect.height > window.innerHeight - edge
            && trigger.top - panelRect.height - gap >= edge
        ) {
            top = trigger.top - panelRect.height - gap;
        }
        top = Math.max(
            edge,
            Math.min(
                top,
                window.innerHeight - panelRect.height - edge
            )
        );

        panel.style.left = Math.round(left) + 'px';
        panel.style.top = Math.round(top) + 'px';
        panel.style.visibility = 'visible';
    }

    function closeOtherMenus(current) {
        document.querySelectorAll(menuSelector + '[open]').forEach(
            function(menu) {
                if (menu !== current) {
                    menu.open = false;
                }
            }
        );
    }

    document.querySelectorAll(menuSelector).forEach(function(menu) {
        menu.addEventListener('toggle', function() {
            var panel = panelFor(menu);
            if (!menu.open) {
                if (panel) {
                    panel.removeAttribute('style');
                }
                return;
            }
            closeOtherMenus(menu);
            window.requestAnimationFrame(function() {
                positionMenu(menu);
            });
        });
    });

    document.addEventListener('click', function(event) {
        document.querySelectorAll(menuSelector + '[open]').forEach(
            function(menu) {
                if (!menu.contains(event.target)) {
                    menu.open = false;
                }
            }
        );
    });

    window.addEventListener('resize', function() {
        document.querySelectorAll(menuSelector + '[open]').forEach(
            positionMenu
        );
    });

    window.addEventListener(
        'scroll',
        function() {
            document.querySelectorAll(menuSelector + '[open]').forEach(
                positionMenu
            );
        },
        true
    );
})();
JS);

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
