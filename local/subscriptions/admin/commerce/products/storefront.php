<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\catalog\presentation\CommerceCatalogProductNameResolver;
use local_subscriptions\commerce\catalog\rendering\CommerceProductEditorNavigationRenderer;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\commerce\storefront\admin\CommerceStorefrontPageEditor;
use local_subscriptions\commerce\storefront\admin\CommerceStorefrontSectionStatusService;
use local_subscriptions\commerce\storefront\content\CommerceStorefrontContentFileService;
use local_subscriptions\commerce\showroom\CommerceShowroomRegistry;
use local_subscriptions\crm\commerce\presentation\CommerceDesignSystemRenderer;
use local_subscriptions\crm\commerce\presentation\CommerceProductPageHeaderRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(
    Capabilities::MANAGE_CONFIGURATION
);
$sku = required_param('sku', PARAM_RAW_TRIMMED);
$area = optional_param('area', 'content', PARAM_ALPHA);
$allowedareas = ['content', 'presentation', 'distribution', 'tools'];
if (!in_array($area, $allowedareas, true)) {
    $area = 'content';
}

$factory = new CommerceCatalogFactory($DB);
$manager = $factory->product_manager();
$editor = $manager->get_editor_data($sku);
$product = $editor->get_product();
$displayname = CommerceCatalogProductNameResolver::resolve_native_id(
    $DB,
    (int)$product->get_id(),
    $product->get_name()
);

$editlanguage = optional_param(
    'editlang',
    current_language(),
    PARAM_ALPHANUMEXT
);
$editlanguage = strtolower(
    explode(
        '_',
        str_replace('-', '_', $editlanguage)
    )[0]
);

$pageeditor = new CommerceStorefrontPageEditor();
$definition = $pageeditor->definition_from_product(
    $product,
    $editlanguage
);
$rows = array_values(array_filter(
    $pageeditor->form_rows($product, $editlanguage),
    static fn(array $row): bool =>
        trim((string)($row['type'] ?? '')) !== ''
));

$statusservice = new CommerceStorefrontSectionStatusService(
    CommerceStorefrontContentFileService::create()
);
$readycount = 0;
$attentioncount = 0;
foreach ($rows as $row) {
    $status = $statusservice->status($row);
    if (
        $status
        === CommerceStorefrontSectionStatusService::READY
    ) {
        $readycount++;
    } else if (
        $status
        === CommerceStorefrontSectionStatusService::ATTENTION
    ) {
        $attentioncount++;
    }
}

$languageoptions = [];
foreach ($factory->locale_service()->get_languages() as $code => $label) {
    $languageoptions[$code] = $label;
}

$metadata = $product->get_metadata();
$storefrontmetadata = is_array($metadata['storefront'] ?? null)
    ? $metadata['storefront']
    : [];
$locales = is_array($storefrontmetadata['locales'] ?? null)
    ? $storefrontmetadata['locales']
    : [];
$configuredlocales = [];
foreach ($locales as $code => $locale) {
    if (
        is_array($locale)
        && (
            !empty($locale['sections'])
            || !empty($locale['seo'])
            || !empty($locale['experience'])
        )
    ) {
        $configuredlocales[] = strtoupper((string)$code);
    }
}
if (!in_array('FR', $configuredlocales, true)
    && !empty($storefrontmetadata['sections'])) {
    $configuredlocales[] = 'FR';
}

$showroomkey = trim((string)($definition['showroom_key'] ?? ''));
$showroomlabel = get_string('none');
if ($showroomkey !== '') {
    $showroomdefinitions = CommerceShowroomRegistry::definitions();
    if (isset($showroomdefinitions[$showroomkey])) {
        $showroomlabel = get_string(
            'commerce_storefront_n812_showroom_configured',
            'local_subscriptions'
        );
    } else {
        $showroomlabel = get_string(
            'commerce_storefront_n812_showroom_custom',
            'local_subscriptions'
        );
    }
}

$pagetitle = get_string(
    'commerce_storefront_n812_title',
    'local_subscriptions',
    $displayname
);
$pageurl = new moodle_url(
    '/local/subscriptions/admin/commerce/products/storefront.php',
    [
        'sku' => $sku,
        'area' => $area,
        'editlang' => $editlanguage,
    ]
);
CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    'local-subscriptions-commerce-product-storefront-hub-page'
);

$previewurl = new moodle_url(
    '/local/subscriptions/storefront_product.php',
    ['sku' => $product->get_sku()]
);
$builderurl = new moodle_url(
    '/local/subscriptions/admin/commerce/products/storefront_builder.php',
    [
        'sku' => $product->get_sku(),
        'editlang' => $editlanguage,
        'area' => 'content',
    ]
);
$arealabels = [
    'content' => [
        'icon' => 'fa fa-pencil-square-o',
        'label' => get_string(
            'commerce_storefront_n812_tab_content',
            'local_subscriptions'
        ),
    ],
    'builder' => [
        'icon' => 'fa fa-cubes',
        'label' => get_string(
            'commerce_storefront_n819_tab_builder',
            'local_subscriptions'
        ),
    ],
    'presentation' => [
        'icon' => 'fa fa-desktop',
        'label' => get_string(
            'commerce_storefront_n812_tab_presentation',
            'local_subscriptions'
        ),
    ],
    'distribution' => [
        'icon' => 'fa fa-bullhorn',
        'label' => get_string(
            'commerce_storefront_n812_tab_distribution',
            'local_subscriptions'
        ),
    ],
    'tools' => [
        'icon' => 'fa fa-wrench',
        'label' => get_string(
            'commerce_storefront_n812_tab_tools',
            'local_subscriptions'
        ),
    ],
];

$typeicons = [
    'hero' => 'fa fa-star',
    'rich_text' => 'fa fa-align-left',
    'image_text' => 'fa fa-picture-o',
    'video' => 'fa fa-play-circle',
    'h5p' => 'fa fa-puzzle-piece',
    'cta' => 'fa fa-mouse-pointer',
    'features' => 'fa fa-th-large',
];

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::COMMERCE,
    $context
);
echo CommerceProductEditorNavigationRenderer::breadcrumb(
    $displayname,
    get_string(
        'commerce_product_step_storefront',
        'local_subscriptions'
    )
);
echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::PRODUCTS
);
echo CommerceProductEditorNavigationRenderer::render(
    $product,
    CommerceProductEditorNavigationRenderer::STOREFRONT
);
echo CommerceProductPageHeaderRenderer::render(
    $pagetitle,
    CommerceDesignSystemRenderer::page_intro(
        get_string(
            'commerce_storefront_n812_intro',
            'local_subscriptions'
        )
    ),
    html_writer::link(
        $previewurl,
        html_writer::tag('i', '', [
            'class' => 'fa fa-eye me-1',
            'aria-hidden' => 'true',
        ])
        . get_string(
            'commerce_storefront_preview',
            'local_subscriptions'
        ),
        [
            'class' => 'btn btn-outline-primary',
            'target' => '_blank',
            'rel' => 'noopener',
        ]
    ),
    get_string(
        'commerce_storefront_n812_eyebrow',
        'local_subscriptions'
    )
);

echo html_writer::start_div(
    'commerce-storefront-n812-tabs'
);
foreach ($arealabels as $key => $item) {
    $taburl = match ($key) {
        'builder' => new moodle_url(
            '/local/subscriptions/admin/commerce/products/storefront_builder.php',
            ['sku' => $sku, 'editlang' => $editlanguage, 'area' => 'content']
        ),
        'presentation' => new moodle_url(
            '/local/subscriptions/admin/commerce/products/storefront_presentation.php',
            ['sku' => $sku]
        ),
        'distribution' => new moodle_url(
            '/local/subscriptions/admin/commerce/products/storefront_distribution.php',
            ['sku' => $sku, 'editlang' => $editlanguage]
        ),
        'tools' => new moodle_url(
            '/local/subscriptions/admin/commerce/products/storefront_tools.php',
            ['sku' => $sku, 'editlang' => $editlanguage]
        ),
        default => new moodle_url($pageurl, ['area' => $key]),
    };
    echo html_writer::link(
        $taburl,
        html_writer::tag('i', '', [
            'class' => $item['icon'],
            'aria-hidden' => 'true',
        ])
        . html_writer::span($item['label']),
        [
            'class' => 'commerce-storefront-n812-tab'
                . ($area === $key ? ' is-active' : ''),
            'aria-current' => $area === $key ? 'page' : null,
        ]
    );
}
echo html_writer::end_div();

if ($area === 'content') {
    echo html_writer::start_div(
        'commerce-storefront-n812-toolbar'
    );
    echo html_writer::start_tag('form', [
        'method' => 'get',
        'class' => 'commerce-storefront-n812-language',
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'sku',
        'value' => $sku,
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'area',
        'value' => 'content',
    ]);
    echo html_writer::tag(
        'label',
        get_string(
            'commerce_storefront_edit_language',
            'local_subscriptions'
        ),
        [
            'for' => 'editlang',
            'class' => 'form-label',
        ]
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
        html_writer::span(
            count($rows) . ' '
            . get_string(
                'commerce_storefront_n812_sections_short',
                'local_subscriptions'
            ),
            'badge rounded-pill text-bg-light'
        )
        . html_writer::span(
            $readycount . ' '
            . get_string(
                'commerce_storefront_n812_ready_short',
                'local_subscriptions'
            ),
            'badge rounded-pill text-bg-success'
        )
        . (
            $attentioncount > 0
                ? html_writer::span(
                    $attentioncount . ' '
                    . get_string(
                        'commerce_storefront_n812_attention_short',
                        'local_subscriptions'
                    ),
                    'badge rounded-pill text-bg-warning'
                )
                : ''
        ),
        'commerce-storefront-n812-content-status'
    );
    echo html_writer::end_div();

    echo html_writer::div(
        html_writer::div(
            html_writer::tag(
                'h2',
                get_string(
                    'commerce_storefront_n812_content_title',
                    'local_subscriptions'
                ),
                ['class' => 'h5 mb-1']
            )
            . html_writer::tag(
                'p',
                get_string(
                    'commerce_storefront_n812_content_help',
                    'local_subscriptions'
                ),
                ['class' => 'text-muted mb-0']
            ),
            'commerce-storefront-n812-section-copy'
        )
        . html_writer::link(
            $builderurl,
            html_writer::tag('i', '', [
                'class' => 'fa fa-pencil me-1',
                'aria-hidden' => 'true',
            ])
            . get_string(
                'commerce_storefront_n812_open_builder',
                'local_subscriptions'
            ),
            ['class' => 'btn btn-primary']
        ),
        'commerce-storefront-n812-section-head'
    );

    if ($rows === []) {
        echo CommerceDesignSystemRenderer::empty_state(
            get_string(
                'commerce_storefront_n812_empty_title',
                'local_subscriptions'
            ),
            get_string(
                'commerce_storefront_n812_empty_help',
                'local_subscriptions'
            ),
            $builderurl,
            get_string(
                'commerce_storefront_n812_open_builder',
                'local_subscriptions'
            )
        );
    } else {
        echo html_writer::start_div(
            'commerce-storefront-n812-section-list'
        );
        foreach ($rows as $index => $row) {
            $type = (string)($row['type'] ?? '');
            $typename = get_string_manager()->string_exists(
                'commerce_storefront_section_' . $type,
                'local_subscriptions'
            )
                ? get_string(
                    'commerce_storefront_section_' . $type,
                    'local_subscriptions'
                )
                : ucfirst(str_replace('_', ' ', $type));
            $title = trim((string)($row['title'] ?? ''));
            if ($title === '') {
                $title = $typename;
            }
            $status = $statusservice->status($row);
            $statusclass = match ($status) {
                CommerceStorefrontSectionStatusService::READY =>
                    'text-bg-success',
                CommerceStorefrontSectionStatusService::ATTENTION =>
                    'text-bg-warning',
                default => 'text-bg-secondary',
            };
            $statuslabel = match ($status) {
                CommerceStorefrontSectionStatusService::READY =>
                    get_string(
                        'commerce_storefront_n812_status_ready',
                        'local_subscriptions'
                    ),
                CommerceStorefrontSectionStatusService::ATTENTION =>
                    get_string(
                        'commerce_storefront_n812_status_attention',
                        'local_subscriptions'
                    ),
                default =>
                    get_string(
                        'commerce_storefront_n812_status_draft',
                        'local_subscriptions'
                    ),
            };

            echo html_writer::div(
                html_writer::span(
                    html_writer::tag('i', '', [
                        'class' => $typeicons[$type] ?? 'fa fa-square-o',
                        'aria-hidden' => 'true',
                    ]),
                    'commerce-storefront-n812-section-icon'
                )
                . html_writer::div(
                    html_writer::tag(
                        'strong',
                        format_string($title),
                        ['class' => 'commerce-storefront-n812-section-name']
                    )
                    . html_writer::span(
                        $typename,
                        'commerce-storefront-n812-section-type'
                    ),
                    'commerce-storefront-n812-section-main'
                )
                . html_writer::span(
                    $statuslabel,
                    'badge rounded-pill ' . $statusclass
                ),
                'commerce-storefront-n812-section-row'
            );
        }
        echo html_writer::end_div();
    }

    echo html_writer::div(
        html_writer::tag(
            'i',
            '',
            [
                'class' => 'fa fa-lightbulb-o',
                'aria-hidden' => 'true',
            ]
        )
        . html_writer::div(
            html_writer::tag(
                'strong',
                get_string(
                    'commerce_storefront_n812_builder_next_title',
                    'local_subscriptions'
                )
            )
            . html_writer::tag(
                'p',
                get_string(
                    'commerce_storefront_n812_builder_next_help',
                    'local_subscriptions'
                ),
                ['class' => 'mb-0']
            ),
            'commerce-storefront-n812-roadmap-copy'
        ),
        'commerce-storefront-n812-roadmap'
    );
}

if ($area === 'presentation') {
    echo CommerceDesignSystemRenderer::metrics([
        [
            'label' => get_string(
                'commerce_storefront_n812_metric_layout',
                'local_subscriptions'
            ),
            'value' => get_string(
                'commerce_storefront_template_'
                . $definition['template'],
                'local_subscriptions'
            ),
        ],
        [
            'label' => get_string(
                'commerce_storefront_n812_metric_commerce',
                'local_subscriptions'
            ),
            'value' => get_string(
                'commerce_storefront_commerce_position_'
                . match ($definition['commerce_position']) {
                    'hero_integrated' => 'hero',
                    'below_hero' => 'below',
                    'sidebar_sticky' => 'sidebar',
                    'after_intro' => 'intro',
                    'page_bottom' => 'bottom',
                    default => 'none',
                },
                'local_subscriptions'
            ),
        ],
        [
            'label' => get_string(
                'commerce_storefront_n812_metric_header',
                'local_subscriptions'
            ),
            'value' => get_string(
                'commerce_storefront_product_header_'
                . $definition['product_header_mode'],
                'local_subscriptions'
            ),
        ],
    ]);

    echo html_writer::div(
        html_writer::div(
            html_writer::tag(
                'h2',
                get_string(
                    'commerce_storefront_n812_presentation_title',
                    'local_subscriptions'
                ),
                ['class' => 'h5 mb-1']
            )
            . html_writer::tag(
                'p',
                get_string(
                    'commerce_storefront_n812_presentation_help',
                    'local_subscriptions'
                ),
                ['class' => 'text-muted mb-0']
            ),
            'commerce-storefront-n812-section-copy'
        )
        . html_writer::link(
            new moodle_url(
                '/local/subscriptions/admin/commerce/products/storefront_presentation.php',
                ['sku' => $sku]
            ),
            html_writer::tag('i', '', [
                'class' => 'fa fa-sliders me-1',
                'aria-hidden' => 'true',
            ])
            . get_string(
                'commerce_storefront_n812_modify_presentation',
                'local_subscriptions'
            ),
            [
                'class' => 'btn btn-primary',
                'href' => (new moodle_url(
                '/local/subscriptions/admin/commerce/products/storefront_presentation.php',
                ['sku' => $sku]
            ))->out(false),
            ]
        ),
        'commerce-storefront-n812-section-head'
    );

    echo html_writer::start_div(
        'commerce-storefront-n812-setting-grid'
    );
    foreach ([
        [
            'icon' => 'fa fa-columns',
            'title' => get_string(
                'commerce_storefront_n812_card_layout_title',
                'local_subscriptions'
            ),
            'body' => get_string(
                'commerce_storefront_n812_card_layout_help',
                'local_subscriptions'
            ),
        ],
        [
            'icon' => 'fa fa-shopping-cart',
            'title' => get_string(
                'commerce_storefront_n812_card_commerce_title',
                'local_subscriptions'
            ),
            'body' => get_string(
                'commerce_storefront_n812_card_commerce_help',
                'local_subscriptions'
            ),
        ],
        [
            'icon' => 'fa fa-window-maximize',
            'title' => get_string(
                'commerce_storefront_n812_card_shell_title',
                'local_subscriptions'
            ),
            'body' => get_string(
                'commerce_storefront_n812_card_shell_help',
                'local_subscriptions'
            ),
        ],
    ] as $card) {
        echo html_writer::div(
            html_writer::tag('i', '', [
                'class' => $card['icon'],
                'aria-hidden' => 'true',
            ])
            . html_writer::div(
                html_writer::tag('strong', $card['title'])
                . html_writer::tag(
                    'p',
                    $card['body'],
                    ['class' => 'mb-0']
                ),
                'commerce-storefront-n812-setting-copy'
            ),
            'commerce-storefront-n812-setting-card'
        );
    }
    echo html_writer::end_div();

    echo html_writer::tag(
        'details',
        html_writer::tag(
            'summary',
            html_writer::tag('i', '', [
                'class' => 'fa fa-code me-2',
                'aria-hidden' => 'true',
            ])
            . get_string(
                'commerce_storefront_n812_advanced_title',
                'local_subscriptions'
            )
        )
        . html_writer::div(
            get_string(
                'commerce_storefront_n812_advanced_presentation_help',
                'local_subscriptions'
            ),
            'commerce-storefront-n812-advanced-body'
        ),
        ['class' => 'commerce-storefront-n812-advanced']
    );
}

if ($area === 'distribution') {
    echo CommerceDesignSystemRenderer::metrics([
        [
            'label' => get_string(
                'commerce_storefront_n812_metric_featured',
                'local_subscriptions'
            ),
            'value' => !empty($definition['featured'])
                ? get_string('yes')
                : get_string('no'),
        ],
        [
            'label' => get_string(
                'commerce_storefront_n812_metric_showroom',
                'local_subscriptions'
            ),
            'value' => $showroomlabel,
        ],
        [
            'label' => get_string(
                'commerce_storefront_n812_metric_languages',
                'local_subscriptions'
            ),
            'value' => $configuredlocales === []
                ? '—'
                : implode(' · ', $configuredlocales),
        ],
    ]);

    echo html_writer::div(
        html_writer::div(
            html_writer::tag(
                'h2',
                get_string(
                    'commerce_storefront_n812_distribution_title',
                    'local_subscriptions'
                ),
                ['class' => 'h5 mb-1']
            )
            . html_writer::tag(
                'p',
                get_string(
                    'commerce_storefront_n812_distribution_help',
                    'local_subscriptions'
                ),
                ['class' => 'text-muted mb-0']
            ),
            'commerce-storefront-n812-section-copy'
        )
        . html_writer::link(
            new moodle_url(
                '/local/subscriptions/admin/commerce/products/storefront_distribution.php',
                ['sku' => $sku, 'editlang' => $editlanguage]
            ),
            html_writer::tag('i', '', [
                'class' => 'fa fa-bullhorn me-1',
                'aria-hidden' => 'true',
            ])
            . get_string(
                'commerce_storefront_n812_modify_distribution',
                'local_subscriptions'
            ),
            ['class' => 'btn btn-primary']
        ),
        'commerce-storefront-n812-section-head'
    );

    $distributioncards = [
        [
            'icon' => 'fa fa-tags',
            'title' => get_string(
                'commerce_storefront_n812_merch_title',
                'local_subscriptions'
            ),
            'body' => get_string(
                'commerce_storefront_n812_merch_help',
                'local_subscriptions'
            ),
            'status' => count($definition['badges'] ?? []) . ' '
                . get_string(
                    'commerce_storefront_n812_badges_short',
                    'local_subscriptions'
                ),
        ],
        [
            'icon' => 'fa fa-handshake-o',
            'title' => get_string(
                'commerce_storefront_n812_experience_title',
                'local_subscriptions'
            ),
            'body' => get_string(
                'commerce_storefront_n812_experience_help',
                'local_subscriptions'
            ),
            'status' => count($definition['trust'] ?? []) . ' '
                . get_string(
                    'commerce_storefront_n812_items_short',
                    'local_subscriptions'
                ),
        ],
        [
            'icon' => 'fa fa-search',
            'title' => get_string(
                'commerce_storefront_n812_seo_title',
                'local_subscriptions'
            ),
            'body' => get_string(
                'commerce_storefront_n812_seo_help',
                'local_subscriptions'
            ),
            'status' => trim((string)$definition['seo_title']) !== ''
                || trim((string)$definition['seo_description']) !== ''
                ? get_string(
                    'commerce_storefront_n812_customised',
                    'local_subscriptions'
                )
                : get_string(
                    'commerce_storefront_n812_automatic',
                    'local_subscriptions'
                ),
        ],
        [
            'icon' => 'fa fa-external-link',
            'title' => get_string(
                'commerce_storefront_n812_discovery_title',
                'local_subscriptions'
            ),
            'body' => get_string(
                'commerce_storefront_n812_discovery_help',
                'local_subscriptions'
            ),
            'status' => $definition['showroom_discoverymode'] === 'showroom'
                ? get_string(
                    'commerce_product_discovery_showroom',
                    'local_subscriptions'
                )
                : get_string(
                    'commerce_product_discovery_storefront',
                    'local_subscriptions'
                ),
        ],
    ];

    echo html_writer::start_div(
        'commerce-storefront-n812-setting-grid'
    );
    foreach ($distributioncards as $card) {
        echo html_writer::div(
            html_writer::tag('i', '', [
                'class' => $card['icon'],
                'aria-hidden' => 'true',
            ])
            . html_writer::div(
                html_writer::div(
                    html_writer::tag('strong', $card['title'])
                    . html_writer::span(
                        $card['status'],
                        'badge rounded-pill text-bg-light'
                    ),
                    'commerce-storefront-n812-setting-title'
                )
                . html_writer::tag(
                    'p',
                    $card['body'],
                    ['class' => 'mb-0']
                ),
                'commerce-storefront-n812-setting-copy'
            ),
            'commerce-storefront-n812-setting-card'
        );
    }
    echo html_writer::end_div();

    echo html_writer::div(
        html_writer::tag('i', '', [
            'class' => 'fa fa-magic',
            'aria-hidden' => 'true',
        ])
        . html_writer::div(
            html_writer::tag(
                'strong',
                get_string(
                    'commerce_storefront_n812_defaults_title',
                    'local_subscriptions'
                )
            )
            . html_writer::tag(
                'p',
                get_string(
                    'commerce_storefront_n812_defaults_help',
                    'local_subscriptions'
                ),
                ['class' => 'mb-0']
            ),
            'commerce-storefront-n812-roadmap-copy'
        ),
        'commerce-storefront-n812-roadmap'
    );
}

if ($area === 'tools') {
    echo html_writer::div(
        html_writer::div(
            html_writer::tag(
                'h2',
                get_string(
                    'commerce_storefront_n812_tools_title',
                    'local_subscriptions'
                ),
                ['class' => 'h5 mb-1']
            )
            . html_writer::tag(
                'p',
                get_string(
                    'commerce_storefront_n812_tools_help',
                    'local_subscriptions'
                ),
                ['class' => 'text-muted mb-0']
            ),
            'commerce-storefront-n812-section-copy'
        )
        . html_writer::link(
            (new moodle_url(
                '/local/subscriptions/admin/commerce/products/storefront_tools.php',
                ['sku' => $sku, 'editlang' => $editlanguage]
            ))->out(false),
            html_writer::tag('i', '', [
                'class' => 'fa fa-wrench me-1',
                'aria-hidden' => 'true',
            ])
            . get_string(
                'commerce_storefront_n812_open_tools',
                'local_subscriptions'
            ),
            ['class' => 'btn btn-primary']
        ),
        'commerce-storefront-n812-section-head'
    );

    echo html_writer::start_div(
        'commerce-storefront-n812-setting-grid'
    );
    foreach ([
        [
            'icon' => 'fa fa-language',
            'title' => get_string(
                'commerce_storefront_n812_tool_translation_title',
                'local_subscriptions'
            ),
            'body' => get_string(
                'commerce_storefront_n812_tool_translation_help',
                'local_subscriptions'
            ),
        ],
        [
            'icon' => 'fa fa-download',
            'title' => get_string(
                'commerce_storefront_n812_tool_transfer_title',
                'local_subscriptions'
            ),
            'body' => get_string(
                'commerce_storefront_n812_tool_transfer_help',
                'local_subscriptions'
            ),
        ],
        [
            'icon' => 'fa fa-trash-o',
            'title' => get_string(
                'commerce_storefront_n812_tool_reset_title',
                'local_subscriptions'
            ),
            'body' => get_string(
                'commerce_storefront_n812_tool_reset_help',
                'local_subscriptions'
            ),
        ],
    ] as $card) {
        echo html_writer::div(
            html_writer::tag('i', '', [
                'class' => $card['icon'],
                'aria-hidden' => 'true',
            ])
            . html_writer::div(
                html_writer::tag('strong', $card['title'])
                . html_writer::tag(
                    'p',
                    $card['body'],
                    ['class' => 'mb-0']
                ),
                'commerce-storefront-n812-setting-copy'
            ),
            'commerce-storefront-n812-setting-card'
        );
    }
    echo html_writer::end_div();

    echo html_writer::div(
        get_string(
            'commerce_storefront_n812_tools_safety_help',
            'local_subscriptions'
        ),
        'alert alert-light border mt-3 mb-0'
    );
}

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
