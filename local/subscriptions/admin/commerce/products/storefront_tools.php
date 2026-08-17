<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\catalog\presentation\CommerceCatalogProductNameResolver;
use local_subscriptions\commerce\catalog\rendering\CommerceProductEditorNavigationRenderer;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\commerce\storefront\admin\CommerceStorefrontResetService;
use local_subscriptions\commerce\storefront\content\CommerceStorefrontContentFileService;
use local_subscriptions\commerce\storefront\localisation\CommerceStorefrontLocaleTransferService;
use local_subscriptions\commerce\storefront\transfer\CommerceStorefrontPackageService;
use local_subscriptions\commerce\storefront\translation\CommerceStorefrontAiTranslationService;
use local_subscriptions\crm\commerce\presentation\CommerceDesignSystemRenderer;
use local_subscriptions\crm\commerce\presentation\CommerceProductPageHeaderRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$sku = required_param('sku', PARAM_RAW_TRIMMED);
$editlanguage = optional_param(
    'editlang',
    current_language(),
    PARAM_ALPHANUMEXT
);
$editlanguage = strtolower(
    explode('_', str_replace('-', '_', $editlanguage))[0]
);

$factory = new CommerceCatalogFactory($DB);
$manager = $factory->product_manager();
$editor = $manager->get_editor_data($sku);
$product = $editor->get_product();
$displayname = CommerceCatalogProductNameResolver::resolve_native_id(
    $DB,
    (int)$product->get_id(),
    $product->get_name()
);
$contentfiles = CommerceStorefrontContentFileService::create();

$pageurl = new moodle_url(
    '/local/subscriptions/admin/commerce/products/storefront_tools.php',
    ['sku' => $sku, 'editlang' => $editlanguage]
);
$title = get_string(
    'commerce_storefront_n815_tools_title',
    'local_subscriptions',
    $displayname
);
CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $title,
    'local-subscriptions-commerce-storefront-tools-page'
);

$storefrontaction = optional_param(
    'storefront_action',
    '',
    PARAM_ALPHANUMEXT
);

if ($storefrontaction === 'export' && confirm_sesskey()) {
    $package = new CommerceStorefrontPackageService($context);
    $archivepath = $package->export($product);
    send_temp_file(
        $archivepath,
        clean_filename(
            strtolower($product->get_sku())
            . '-storefront.cfrproduct'
        )
    );
}

if (
    $storefrontaction === 'import'
    && data_submitted()
    && confirm_sesskey()
) {
    if (
        !isset($_FILES['storefront_package'])
        || (int)($_FILES['storefront_package']['error']
            ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
        || empty($_FILES['storefront_package']['tmp_name'])
        || !is_uploaded_file(
            (string)$_FILES['storefront_package']['tmp_name']
        )
    ) {
        throw new moodle_exception(
            'commerce_storefront_package_invalid',
            'local_subscriptions'
        );
    }

    $package = new CommerceStorefrontPackageService($context);
    $metadata = $package->import(
        (string)$_FILES['storefront_package']['tmp_name'],
        $product
    );
    $manager->save_metadata($sku, $metadata);
    redirect(
        $pageurl,
        get_string(
            'commerce_storefront_package_import_success',
            'local_subscriptions'
        ),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

if (
    $storefrontaction === 'reset'
    && data_submitted()
    && confirm_sesskey()
) {
    $resetservice = new CommerceStorefrontResetService($contentfiles);
    $manager->save_metadata(
        $sku,
        $resetservice->reset($product->get_metadata())
    );
    redirect(
        $pageurl,
        get_string(
            'commerce_storefront_reset_success',
            'local_subscriptions'
        ),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$localeaction = optional_param(
    'locale_action',
    '',
    PARAM_ALPHANUMEXT
);
if (
    $localeaction !== ''
    && data_submitted()
    && confirm_sesskey()
) {
    $localetransfer = new CommerceStorefrontLocaleTransferService();
    $source = optional_param(
        'locale_source',
        '',
        PARAM_ALPHANUMEXT
    );

    if ($localeaction === 'copy') {
        $metadata = $localetransfer->copy(
            $product->get_metadata(),
            $source,
            $editlanguage
        );
        $manager->save_metadata($sku, $metadata);
        redirect(
            $pageurl,
            get_string(
                'commerce_storefront_locale_copy_success',
                'local_subscriptions'
            ),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }

    if ($localeaction === 'translate_preview') {
        $translationservice =
            CommerceStorefrontAiTranslationService::create();
        $preview = $translationservice->preview(
            $product->get_metadata(),
            $source,
            $editlanguage
        );
        $token = bin2hex(random_bytes(16));
        $SESSION->local_subscriptions_storefront_translation_previews =
            is_array(
                $SESSION
                    ->local_subscriptions_storefront_translation_previews
                    ?? null
            )
                ? $SESSION
                    ->local_subscriptions_storefront_translation_previews
                : [];
        $SESSION
            ->local_subscriptions_storefront_translation_previews[$token] = [
                'sku' => $product->get_sku(),
                'userid' => (int)$USER->id,
                'created' => time(),
                'preview' => $preview,
            ];
        redirect(
            new moodle_url(
                $pageurl,
                ['translation_preview' => $token]
            )
        );
    }

    if (
        in_array(
            $localeaction,
            ['translate_apply', 'translate_cancel'],
            true
        )
    ) {
        $token = required_param(
            'translation_preview',
            PARAM_ALPHANUMEXT
        );
        $previews = is_array(
            $SESSION
                ->local_subscriptions_storefront_translation_previews
                ?? null
        )
            ? $SESSION
                ->local_subscriptions_storefront_translation_previews
            : [];
        $stored = $previews[$token] ?? null;

        if (
            !is_array($stored)
            || (string)($stored['sku'] ?? '')
                !== $product->get_sku()
            || (int)($stored['userid'] ?? 0)
                !== (int)$USER->id
            || (int)($stored['created'] ?? 0)
                < time() - HOURSECS
            || !is_array($stored['preview'] ?? null)
        ) {
            unset(
                $SESSION
                    ->local_subscriptions_storefront_translation_previews[
                        $token
                    ]
            );
            throw new moodle_exception(
                'commerce_storefront_ai_translation_preview_expired',
                'local_subscriptions'
            );
        }

        if ($localeaction === 'translate_cancel') {
            unset(
                $SESSION
                    ->local_subscriptions_storefront_translation_previews[
                        $token
                    ]
            );
            redirect($pageurl);
        }

        $translationservice =
            CommerceStorefrontAiTranslationService::create();
        $metadata = $translationservice->apply_preview(
            $product->get_metadata(),
            $stored['preview']
        );
        $manager->save_metadata($sku, $metadata);
        unset(
            $SESSION
                ->local_subscriptions_storefront_translation_previews[
                    $token
                ]
        );
        redirect(
            $pageurl,
            get_string(
                'commerce_storefront_ai_translation_applied',
                'local_subscriptions'
            ),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }
}

$languageoptions = [];
foreach ($factory->locale_service()->get_languages() as $code => $label) {
    $languageoptions[$code] = $label;
}
$sourceoptions = $languageoptions;
unset($sourceoptions[$editlanguage]);
$defaultsource = isset($sourceoptions['ru'])
    ? 'ru'
    : (string)array_key_first($sourceoptions);

$translationservice =
    CommerceStorefrontAiTranslationService::create();

$translationtoken = optional_param(
    'translation_preview',
    '',
    PARAM_ALPHANUMEXT
);
$storedpreview = null;
if ($translationtoken !== '') {
    $previews = is_array(
        $SESSION->local_subscriptions_storefront_translation_previews
            ?? null
    )
        ? $SESSION->local_subscriptions_storefront_translation_previews
        : [];
    $candidate = $previews[$translationtoken] ?? null;
    if (
        is_array($candidate)
        && (string)($candidate['sku'] ?? '')
            === $product->get_sku()
        && (int)($candidate['userid'] ?? 0)
            === (int)$USER->id
        && (int)($candidate['created'] ?? 0)
            >= time() - HOURSECS
        && is_array($candidate['preview'] ?? null)
    ) {
        $storedpreview = $candidate['preview'];
    }
}

$tabs = [
    'content' => [
        'fa fa-pencil-square-o',
        get_string('commerce_storefront_n812_tab_content', 'local_subscriptions'),
        new moodle_url(
            '/local/subscriptions/admin/commerce/products/storefront.php',
            ['sku' => $sku, 'area' => 'content']
        ),
    ],
    'builder' => [
        'fa fa-cubes',
        get_string(
            'commerce_storefront_n819_tab_builder',
            'local_subscriptions'
        ),
        new moodle_url(
            '/local/subscriptions/admin/commerce/products/storefront_builder.php',
            ['sku' => $sku, 'editlang' => $editlanguage, 'area' => 'content']
        ),
    ],
    'presentation' => [
        'fa fa-desktop',
        get_string('commerce_storefront_n812_tab_presentation', 'local_subscriptions'),
        new moodle_url(
            '/local/subscriptions/admin/commerce/products/storefront_presentation.php',
            ['sku' => $sku]
        ),
    ],
    'distribution' => [
        'fa fa-bullhorn',
        get_string('commerce_storefront_n812_tab_distribution', 'local_subscriptions'),
        new moodle_url(
            '/local/subscriptions/admin/commerce/products/storefront_distribution.php',
            ['sku' => $sku]
        ),
    ],
    'tools' => [
        'fa fa-wrench',
        get_string('commerce_storefront_n812_tab_tools', 'local_subscriptions'),
        $pageurl,
    ],
];

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CommerceProductEditorNavigationRenderer::breadcrumb(
    $displayname,
    get_string('commerce_product_step_storefront', 'local_subscriptions')
);
echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::PRODUCTS
);
echo CommerceProductEditorNavigationRenderer::render(
    $product,
    CommerceProductEditorNavigationRenderer::STOREFRONT
);
echo CommerceProductPageHeaderRenderer::render(
    $title,
    CommerceDesignSystemRenderer::page_intro(
        get_string(
            'commerce_storefront_n815_tools_intro',
            'local_subscriptions'
        )
    ),
    '',
    get_string('commerce_storefront_n812_eyebrow', 'local_subscriptions')
);

echo html_writer::start_div('commerce-storefront-n812-tabs');
foreach ($tabs as $key => [$icon, $label, $url]) {
    echo html_writer::link(
        $url,
        html_writer::tag('i', '', [
            'class' => $icon,
            'aria-hidden' => 'true',
        ]) . html_writer::span($label),
        [
            'class' => 'commerce-storefront-n812-tab'
                . ($key === 'tools' ? ' is-active' : ''),
        ]
    );
}
echo html_writer::end_div();

echo html_writer::start_div('commerce-storefront-n815-tools-top');
echo html_writer::start_tag('form', [
    'method' => 'get',
    'class' => 'commerce-storefront-n815-tools-language',
]);
echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'sku',
    'value' => $sku,
]);
echo html_writer::tag(
    'label',
    get_string(
        'commerce_storefront_n816_tools_target_language',
        'local_subscriptions'
    ),
    ['for' => 'editlang', 'class' => 'form-label']
);
echo html_writer::select(
    $languageoptions,
    'editlang',
    $editlanguage,
    false,
    [
        'id' => 'editlang',
        'class' => 'form-select',
        'onchange' => 'this.form.submit()',
    ]
);
echo html_writer::end_tag('form');
echo html_writer::div(
    get_string(
        'commerce_storefront_n815_tools_scope_help',
        'local_subscriptions'
    ),
    'commerce-storefront-n815-tools-scope'
);
echo html_writer::end_div();

echo html_writer::start_div(
    'commerce-storefront-n815-tools-grid'
);

echo html_writer::start_div(
    'card card-body commerce-storefront-n815-tool-card'
);
echo html_writer::tag(
    'h2',
    html_writer::tag('i', '', [
        'class' => 'fa fa-copy me-2',
        'aria-hidden' => 'true',
    ])
    . get_string(
        'commerce_storefront_locale_copy_title',
        'local_subscriptions'
    ),
    ['class' => 'h5']
);
echo html_writer::tag(
    'p',
    get_string(
        'commerce_storefront_locale_copy_help',
        'local_subscriptions'
    ),
    ['class' => 'text-muted']
);
echo html_writer::start_tag('form', ['method' => 'post']);
echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'sesskey',
    'value' => sesskey(),
]);
echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'locale_action',
    'value' => 'copy',
]);
echo html_writer::tag(
    'label',
    get_string(
        'commerce_storefront_locale_source',
        'local_subscriptions'
    ),
    ['for' => 'copy-source', 'class' => 'form-label']
);
echo html_writer::select(
    $sourceoptions,
    'locale_source',
    $defaultsource,
    false,
    ['id' => 'copy-source', 'class' => 'form-select mb-3']
);
echo html_writer::tag(
    'button',
    html_writer::tag('i', '', [
        'class' => 'fa fa-copy me-1',
        'aria-hidden' => 'true',
    ])
    . get_string(
        'commerce_storefront_locale_copy_button',
        'local_subscriptions'
    ),
    [
        'type' => 'submit',
        'class' => 'btn btn-outline-primary',
        'onclick' => 'return confirm('
            . json_encode(
                get_string(
                    'commerce_storefront_locale_copy_confirm',
                    'local_subscriptions'
                )
            )
            . ');',
    ]
);
echo html_writer::end_tag('form');
echo html_writer::end_div();

echo html_writer::start_div(
    'card card-body commerce-storefront-n815-tool-card'
);
echo html_writer::tag(
    'h2',
    html_writer::tag('i', '', [
        'class' => 'fa fa-magic me-2',
        'aria-hidden' => 'true',
    ])
    . get_string(
        'commerce_storefront_ai_translation_title',
        'local_subscriptions'
    ),
    ['class' => 'h5']
);
echo html_writer::tag(
    'p',
    get_string(
        'commerce_storefront_ai_translation_help',
        'local_subscriptions'
    ),
    ['class' => 'text-muted']
);
echo html_writer::start_tag('form', ['method' => 'post']);
echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'sesskey',
    'value' => sesskey(),
]);
echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'locale_action',
    'value' => 'translate_preview',
]);
echo html_writer::tag(
    'label',
    get_string(
        'commerce_storefront_locale_source',
        'local_subscriptions'
    ),
    ['for' => 'translate-source', 'class' => 'form-label']
);
echo html_writer::select(
    $sourceoptions,
    'locale_source',
    $defaultsource,
    false,
    ['id' => 'translate-source', 'class' => 'form-select mb-3']
);
if (!$translationservice->available()) {
    echo html_writer::div(
        get_string(
            'commerce_storefront_ai_translation_unavailable_help',
            'local_subscriptions'
        ),
        'alert alert-warning py-2'
    );
}
echo html_writer::tag(
    'button',
    html_writer::tag('i', '', [
        'class' => 'fa fa-magic me-1',
        'aria-hidden' => 'true',
    ])
    . get_string(
        'commerce_storefront_ai_translation_preview_button',
        'local_subscriptions'
    ),
    [
        'type' => 'submit',
        'class' => 'btn btn-primary',
        'disabled' => $translationservice->available()
            ? null
            : 'disabled',
    ]
);
echo html_writer::end_tag('form');
echo html_writer::end_div();

echo html_writer::start_div(
    'card card-body commerce-storefront-n815-tool-card'
);
echo html_writer::tag(
    'h2',
    html_writer::tag('i', '', [
        'class' => 'fa fa-exchange me-2',
        'aria-hidden' => 'true',
    ])
    . get_string(
        'commerce_storefront_package_title',
        'local_subscriptions'
    ),
    ['class' => 'h5']
);
echo html_writer::tag(
    'p',
    get_string(
        'commerce_storefront_package_help',
        'local_subscriptions'
    ),
    ['class' => 'text-muted']
);
echo html_writer::link(
    new moodle_url(
        $pageurl,
        [
            'storefront_action' => 'export',
            'sesskey' => sesskey(),
        ]
    ),
    html_writer::tag('i', '', [
        'class' => 'fa fa-download me-1',
        'aria-hidden' => 'true',
    ])
    . get_string(
        'commerce_storefront_package_export',
        'local_subscriptions'
    ),
    ['class' => 'btn btn-outline-primary mb-3 align-self-start']
);
echo html_writer::start_tag('form', [
    'method' => 'post',
    'enctype' => 'multipart/form-data',
]);
echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'sesskey',
    'value' => sesskey(),
]);
echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'storefront_action',
    'value' => 'import',
]);
echo html_writer::empty_tag('input', [
    'type' => 'file',
    'name' => 'storefront_package',
    'accept' => '.cfrproduct,application/zip',
    'class' => 'form-control mb-2',
    'required' => 'required',
]);
echo html_writer::tag(
    'button',
    html_writer::tag('i', '', [
        'class' => 'fa fa-upload me-1',
        'aria-hidden' => 'true',
    ])
    . get_string(
        'commerce_storefront_package_import',
        'local_subscriptions'
    ),
    ['type' => 'submit', 'class' => 'btn btn-primary']
);
echo html_writer::end_tag('form');
echo html_writer::end_div();

echo html_writer::start_div(
    'card card-body commerce-storefront-n815-tool-card '
        . 'commerce-storefront-n815-tool-card--danger'
);
echo html_writer::tag(
    'h2',
    html_writer::tag('i', '', [
        'class' => 'fa fa-trash me-2',
        'aria-hidden' => 'true',
    ])
    . get_string(
        'commerce_storefront_reset_title',
        'local_subscriptions'
    ),
    ['class' => 'h5 text-danger']
);
echo html_writer::tag(
    'p',
    get_string(
        'commerce_storefront_reset_help',
        'local_subscriptions'
    ),
    ['class' => 'text-muted']
);
echo html_writer::tag(
    'button',
    get_string(
        'commerce_storefront_reset_button',
        'local_subscriptions'
    ),
    [
        'type' => 'button',
        'class' => 'btn btn-outline-danger align-self-start',
        'onclick' =>
            "document.getElementById('n815-reset-dialog').showModal()",
    ]
);
echo html_writer::end_div();

echo html_writer::end_div();

if (is_array($storedpreview)) {
    $changes = is_array($storedpreview['changes'] ?? null)
        ? $storedpreview['changes']
        : [];
    $changedcount = count(
        array_filter(
            $changes,
            static fn(array $change): bool =>
                !empty($change['changed'])
        )
    );

    echo html_writer::start_div(
        'card card-body commerce-storefront-n815-translation-preview'
    );
    echo html_writer::tag(
        'h2',
        get_string(
            'commerce_storefront_ai_translation_preview_title',
            'local_subscriptions'
        ),
        ['class' => 'h5']
    );
    echo html_writer::tag(
        'p',
        get_string(
            'commerce_storefront_ai_translation_preview_summary',
            'local_subscriptions',
            (object)[
                'source' => strtoupper(
                    (string)$storedpreview['source']
                ),
                'target' => strtoupper(
                    (string)$storedpreview['target']
                ),
                'count' => $changedcount,
                'model' => (string)($storedpreview['model'] ?? ''),
            ]
        ),
        ['class' => 'text-muted']
    );

    echo html_writer::start_div(
        'commerce-storefront-n815-translation-list'
    );
    foreach ($changes as $change) {
        if (!is_array($change) || empty($change['changed'])) {
            continue;
        }
        echo html_writer::tag(
            'details',
            html_writer::tag(
                'summary',
                s((string)($change['id'] ?? ''))
            )
            . html_writer::div(
                html_writer::div(
                    html_writer::tag(
                        'strong',
                        get_string(
                            'commerce_storefront_ai_translation_source_text',
                            'local_subscriptions'
                        )
                    )
                    . html_writer::div(
                        s(trim(strip_tags((string)(
                            $change['source'] ?? ''
                        )))),
                        'small text-muted mt-1'
                    ),
                    'col-lg-6'
                )
                . html_writer::div(
                    html_writer::tag(
                        'strong',
                        get_string(
                            'commerce_storefront_ai_translation_target_text',
                            'local_subscriptions'
                        )
                    )
                    . html_writer::div(
                        s(trim(strip_tags((string)(
                            $change['translated'] ?? ''
                        )))),
                        'small mt-1'
                    ),
                    'col-lg-6'
                ),
                'row g-3 mt-1'
            ),
            ['class' => 'commerce-storefront-n815-translation-row']
        );
    }
    echo html_writer::end_div();

    echo html_writer::start_div('d-flex gap-2 mt-3');
    foreach ([
        'translate_cancel' => [
            get_string('cancel'),
            'btn btn-outline-secondary',
        ],
        'translate_apply' => [
            get_string(
                'commerce_storefront_ai_translation_apply',
                'local_subscriptions'
            ),
            'btn btn-primary',
        ],
    ] as $action => [$label, $class]) {
        echo html_writer::start_tag('form', ['method' => 'post']);
        echo html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'sesskey',
            'value' => sesskey(),
        ]);
        echo html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'locale_action',
            'value' => $action,
        ]);
        echo html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'translation_preview',
            'value' => $translationtoken,
        ]);
        echo html_writer::tag(
            'button',
            $label,
            ['type' => 'submit', 'class' => $class]
        );
        echo html_writer::end_tag('form');
    }
    echo html_writer::end_div();
    echo html_writer::end_div();
}

echo html_writer::start_tag('dialog', [
    'id' => 'n815-reset-dialog',
    'class' => 'commerce-storefront-reset-dialog',
]);
echo html_writer::start_div(
    'commerce-storefront-reset-dialog__content'
);
echo html_writer::tag(
    'h2',
    get_string(
        'commerce_storefront_reset_confirm_title',
        'local_subscriptions'
    ),
    ['class' => 'h5']
);
echo html_writer::tag(
    'p',
    get_string(
        'commerce_storefront_reset_confirm_help',
        'local_subscriptions'
    )
);
echo html_writer::start_tag('form', ['method' => 'post']);
echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'sesskey',
    'value' => sesskey(),
]);
echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'storefront_action',
    'value' => 'reset',
]);
echo html_writer::start_div('d-flex justify-content-end gap-2');
echo html_writer::tag(
    'button',
    get_string('cancel'),
    [
        'type' => 'button',
        'class' => 'btn btn-outline-secondary',
        'onclick' =>
            "document.getElementById('n815-reset-dialog').close()",
    ]
);
echo html_writer::tag(
    'button',
    get_string(
        'commerce_storefront_reset_confirm_button',
        'local_subscriptions'
    ),
    ['type' => 'submit', 'class' => 'btn btn-danger']
);
echo html_writer::end_div();
echo html_writer::end_tag('form');
echo html_writer::end_div();
echo html_writer::end_tag('dialog');

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
