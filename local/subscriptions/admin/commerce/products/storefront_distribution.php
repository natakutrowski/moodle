<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\catalog\presentation\CommerceCatalogProductNameResolver;
use local_subscriptions\commerce\catalog\presentation\CommerceProductDiscoveryUrlResolver;
use local_subscriptions\commerce\catalog\rendering\CommerceProductEditorNavigationRenderer;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\commerce\showroom\CommerceShowroomRegistry;
use local_subscriptions\commerce\storefront\admin\CommerceStorefrontDistributionService;
use local_subscriptions\commerce\storefront\admin\CommerceStorefrontPageEditor;
use local_subscriptions\commerce\storefront\experience\CommerceStorefrontExperienceResolver;
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

$factory = new CommerceCatalogFactory($DB);
$manager = $factory->product_manager();
$editor = $manager->get_editor_data($sku);
$product = $editor->get_product();
$displayname = CommerceCatalogProductNameResolver::resolve_native_id(
    $DB,
    (int)$product->get_id(),
    $product->get_name()
);

$pageurl = new moodle_url(
    '/local/subscriptions/admin/commerce/products/storefront_distribution.php',
    ['sku' => $sku, 'editlang' => $editlanguage]
);
$pagetitle = get_string(
    'commerce_storefront_n814_title',
    'local_subscriptions',
    $displayname
);
CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    'local-subscriptions-commerce-storefront-distribution-page'
);

if (data_submitted() && confirm_sesskey()) {
    $service = new CommerceStorefrontDistributionService();
    $metadata = $service->apply(
        $product->get_metadata(),
        $editlanguage,
        [
            'featured' => optional_param(
                'featured',
                0,
                PARAM_BOOL
            ),
            'displayorder' => optional_param(
                'displayorder',
                1000,
                PARAM_INT
            ),
            'badges' => optional_param_array(
                'badges',
                [],
                PARAM_ALPHANUMEXT
            ),
            'group' => optional_param(
                'group',
                'auto',
                PARAM_ALPHANUMEXT
            ),
            'trustmode' => optional_param(
                'trustmode',
                'auto',
                PARAM_ALPHA
            ),
            'trust' => optional_param_array(
                'trust',
                [],
                PARAM_ALPHANUMEXT
            ),
            'quickfacts' => optional_param(
                'quickfacts',
                '',
                PARAM_RAW
            ),
            'recommendations' => optional_param_array(
                'recommendations',
                [],
                PARAM_RAW_TRIMMED
            ),
            'seomode' => optional_param(
                'seomode',
                'auto',
                PARAM_ALPHA
            ),
            'seotitle' => optional_param(
                'seotitle',
                '',
                PARAM_TEXT
            ),
            'seodescription' => optional_param(
                'seodescription',
                '',
                PARAM_TEXT
            ),
            'slugfr' => optional_param(
                'slugfr',
                '',
                PARAM_RAW_TRIMMED
            ),
            'slugen' => optional_param(
                'slugen',
                '',
                PARAM_RAW_TRIMMED
            ),
            'slugru' => optional_param(
                'slugru',
                '',
                PARAM_RAW_TRIMMED
            ),
            'showroomkey' => optional_param(
                'showroomkey',
                '',
                PARAM_ALPHANUMEXT
            ),
            'discoverymode' => optional_param(
                'discoverymode',
                'storefront',
                PARAM_ALPHA
            ),
            'showstorefrontcta' => optional_param(
                'showstorefrontcta',
                0,
                PARAM_BOOL
            ),
        ]
    );
    $manager->save_metadata($sku, $metadata);
    redirect(
        $pageurl,
        get_string('changessaved'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$metadata = $product->get_metadata();
$storefront = is_array($metadata['storefront'] ?? null)
    ? $metadata['storefront']
    : [];
$locales = is_array($storefront['locales'] ?? null)
    ? $storefront['locales']
    : [];
$locale = is_array($locales[$editlanguage] ?? null)
    ? $locales[$editlanguage]
    : [];
$merchandising = is_array($storefront['merchandising'] ?? null)
    ? $storefront['merchandising']
    : [];
$experience = is_array($storefront['experience'] ?? null)
    ? $storefront['experience']
    : [];
$localexperience = is_array($locale['experience'] ?? null)
    ? $locale['experience']
    : [];
$seo = is_array($locale['seo'] ?? null)
    ? $locale['seo']
    : [];
$routing = is_array($storefront['routing'] ?? null)
    ? $storefront['routing']
    : [];
$slugs = is_array($routing['slugs'] ?? null)
    ? $routing['slugs']
    : [];
$showroom = is_array($metadata['showroom'] ?? null)
    ? $metadata['showroom']
    : [];

$featured = !empty($merchandising['featured']);
$displayorder = (int)($merchandising['displayorder'] ?? 1000);
$badges = is_array($merchandising['badges'] ?? null)
    ? $merchandising['badges']
    : [];
$group = (string)($experience['group'] ?? 'auto');
$trustmode = array_key_exists('trust', $experience)
    ? 'custom'
    : 'auto';
$trust = is_array($experience['trust'] ?? null)
    ? $experience['trust']
    : [];
$quickfacts = is_array($localexperience['quickfacts'] ?? null)
    ? $localexperience['quickfacts']
    : (
        $editlanguage === 'fr'
            && is_array($experience['quickfacts'] ?? null)
                ? $experience['quickfacts']
                : []
    );
$quickfactstext = implode(
    PHP_EOL,
    array_map(
        static fn(array $fact): string =>
            trim((string)($fact['value'] ?? ''))
            . ' ||| '
            . trim((string)($fact['label'] ?? '')),
        array_filter($quickfacts, 'is_array')
    )
);

$recommendations = is_array($storefront['recommendations'] ?? null)
    ? $storefront['recommendations']
    : [];
$seomode = (
    trim((string)($seo['title'] ?? '')) !== ''
    || trim((string)($seo['description'] ?? '')) !== ''
) ? 'custom' : 'auto';

$showroomkey = strtolower(trim((string)($showroom['key'] ?? '')));
$discoverymode =
    CommerceProductDiscoveryUrlResolver::normalise_mode(
        (string)($showroom['discoverymode'] ?? 'storefront')
    );
$showstorefrontcta = !array_key_exists(
    'showstorefrontcta',
    $showroom
) || !empty($showroom['showstorefrontcta']);

$languageoptions = [];
foreach ($factory->locale_service()->get_languages() as $code => $label) {
    $languageoptions[$code] = $label;
}

$showroomoptions = [
    '' => get_string(
        'commerce_storefront_n814_no_showroom',
        'local_subscriptions'
    ),
];
foreach (CommerceShowroomRegistry::definitions() as $key => $definition) {
    $showroomoptions[$key] = get_string(
        $definition->get_title_key(),
        'local_subscriptions'
    );
}

$productoptions = [];
foreach ($manager->list_products(null, 'active') as $summary) {
    $candidate = $summary->get_product();
    if (
        (int)$candidate->get_id() === (int)$product->get_id()
    ) {
        continue;
    }
    $candidateid = (int)$candidate->get_id();
    $productoptions[$candidate->get_sku()] =
        CommerceCatalogProductNameResolver::resolve_native_id(
            $DB,
            $candidateid,
            $candidate->get_name()
        );
}
asort($productoptions, SORT_NATURAL | SORT_FLAG_CASE);

$groupoptions = [];
foreach (CommerceStorefrontExperienceResolver::GROUPS as $item) {
    $groupoptions[$item] = get_string(
        'commerce_storefront_group_' . $item,
        'local_subscriptions'
    );
}

$defaulttrust = [
    'secure_payment',
    'immediate_access',
    'support',
];
if (
    in_array(
        $product->get_type(),
        ['course_access', 'digital_download', 'bundle'],
        true
    )
) {
    $defaulttrust[] = 'lifetime_access';
}

$autoseotitle = $displayname;
$autoseodescription = trim(strip_tags($product->get_description()));
if ($autoseodescription === '') {
    $autoseodescription = get_string(
        'commerce_storefront_n814_seo_no_description',
        'local_subscriptions'
    );
}
if (\core_text::strlen($autoseodescription) > 160) {
    $autoseodescription = rtrim(
        \core_text::substr($autoseodescription, 0, 159)
    ) . '…';
}

$tabs = [
    'content' => [
        'icon' => 'fa fa-pencil-square-o',
        'label' => get_string(
            'commerce_storefront_n812_tab_content',
            'local_subscriptions'
        ),
        'url' => new moodle_url(
            '/local/subscriptions/admin/commerce/products/storefront.php',
            [
                'sku' => $sku,
                'area' => 'content',
                'editlang' => $editlanguage,
            ]
        ),
    ],
    'builder' => [
        'icon' => 'fa fa-cubes',
        'label' => get_string(
            'commerce_storefront_n819_tab_builder',
            'local_subscriptions'
        ),
        'url' => new moodle_url(
            '/local/subscriptions/admin/commerce/products/storefront_builder.php',
            ['sku' => $sku, 'editlang' => $editlanguage, 'area' => 'content']
        ),
    ],
    'presentation' => [
        'icon' => 'fa fa-desktop',
        'label' => get_string(
            'commerce_storefront_n812_tab_presentation',
            'local_subscriptions'
        ),
        'url' => new moodle_url(
            '/local/subscriptions/admin/commerce/products/storefront_presentation.php',
            ['sku' => $sku]
        ),
    ],
    'distribution' => [
        'icon' => 'fa fa-bullhorn',
        'label' => get_string(
            'commerce_storefront_n812_tab_distribution',
            'local_subscriptions'
        ),
        'url' => $pageurl,
    ],
    'tools' => [
        'icon' => 'fa fa-wrench',
        'label' => get_string(
            'commerce_storefront_n812_tab_tools',
            'local_subscriptions'
        ),
        'url' => new moodle_url(
            '/local/subscriptions/admin/commerce/products/storefront_tools.php',
            ['sku' => $sku, 'editlang' => $editlanguage]
        ),
    ],
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
            'commerce_storefront_n814_intro',
            'local_subscriptions'
        )
    ),
    '',
    get_string(
        'commerce_storefront_n812_eyebrow',
        'local_subscriptions'
    )
);

echo html_writer::start_div('commerce-storefront-n812-tabs');
foreach ($tabs as $key => $tab) {
    echo html_writer::link(
        $tab['url'],
        html_writer::tag('i', '', [
            'class' => $tab['icon'],
            'aria-hidden' => 'true',
        ])
        . html_writer::span($tab['label']),
        [
            'class' => 'commerce-storefront-n812-tab'
                . ($key === 'distribution' ? ' is-active' : ''),
            'aria-current' => $key === 'distribution'
                ? 'page'
                : null,
        ]
    );
}
echo html_writer::end_div();

echo html_writer::start_tag('form', [
    'method' => 'post',
    'class' => 'commerce-storefront-n814-form',
]);
echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'sesskey',
    'value' => sesskey(),
]);

echo html_writer::start_div('commerce-storefront-n814-topbar commerce-storefront-n816-global-hint');
echo html_writer::div(
    html_writer::tag(
        'i',
        '',
        [
            'class' => 'fa fa-magic',
            'aria-hidden' => 'true',
        ]
    )
    . get_string(
        'commerce_storefront_n814_defaults_hint',
        'local_subscriptions'
    ),
    'commerce-storefront-n814-defaults-hint'
);
echo html_writer::end_div();

echo html_writer::start_div(
    'card card-body commerce-storefront-n814-card'
);
echo html_writer::div(
    html_writer::tag('i', '', [
        'class' => 'fa fa-compass',
        'aria-hidden' => 'true',
    ])
    . html_writer::div(
        html_writer::tag(
            'h2',
            get_string(
                'commerce_storefront_n814_discovery_title',
                'local_subscriptions'
            ),
            ['class' => 'h5 mb-1']
        )
        . html_writer::tag(
            'p',
            get_string(
                'commerce_storefront_n814_discovery_help',
                'local_subscriptions'
            ),
            ['class' => 'text-muted mb-0']
        ),
        'commerce-storefront-n814-heading-copy'
    ),
    'commerce-storefront-n814-card-heading'
);

echo html_writer::start_div(
    'commerce-storefront-n814-choice-grid'
);
foreach ([
    'storefront' => [
        'icon' => 'fa fa-file-text-o',
        'title' => get_string(
            'commerce_storefront_n814_destination_storefront',
            'local_subscriptions'
        ),
        'help' => get_string(
            'commerce_storefront_n814_destination_storefront_help',
            'local_subscriptions'
        ),
    ],
    'showroom' => [
        'icon' => 'fa fa-object-group',
        'title' => get_string(
            'commerce_storefront_n814_destination_showroom',
            'local_subscriptions'
        ),
        'help' => get_string(
            'commerce_storefront_n814_destination_showroom_help',
            'local_subscriptions'
        ),
    ],
] as $value => $choice) {
    $id = 'discovery-' . $value;
    echo html_writer::tag(
        'label',
        html_writer::empty_tag('input', [
            'type' => 'radio',
            'name' => 'discoverymode',
            'id' => $id,
            'value' => $value,
            'class' => 'form-check-input',
        ] + (
            $discoverymode === $value
                ? ['checked' => 'checked']
                : []
        ))
        . html_writer::tag('i', '', [
            'class' => $choice['icon'],
            'aria-hidden' => 'true',
        ])
        . html_writer::div(
            html_writer::tag('strong', $choice['title'])
            . html_writer::span($choice['help']),
            'commerce-storefront-n814-choice-copy'
        ),
        [
            'class' => 'commerce-storefront-n814-choice'
                . ($discoverymode === $value ? ' is-selected' : ''),
            'for' => $id,
            'data-n814-discovery-choice' => $value,
        ]
    );
}
echo html_writer::end_div();

echo html_writer::start_div(
    'commerce-storefront-n814-showroom-fields',
    ['data-n814-showroom-fields' => '1']
);
echo html_writer::div(
    html_writer::tag(
        'label',
        get_string(
            'commerce_storefront_n814_showroom_label',
            'local_subscriptions'
        ),
        ['for' => 'showroomkey', 'class' => 'form-label']
    )
    . html_writer::select(
        $showroomoptions,
        'showroomkey',
        $showroomkey,
        false,
        ['id' => 'showroomkey', 'class' => 'form-select']
    ),
    'commerce-storefront-n814-field'
);
echo html_writer::div(
    html_writer::empty_tag('input', [
        'type' => 'checkbox',
        'name' => 'showstorefrontcta',
        'id' => 'showstorefrontcta',
        'value' => 1,
        'class' => 'form-check-input',
    ] + (
        $showstorefrontcta
            ? ['checked' => 'checked']
            : []
    ))
    . html_writer::tag(
        'label',
        get_string(
            'commerce_storefront_n814_show_storefront_cta',
            'local_subscriptions'
        ),
        [
            'for' => 'showstorefrontcta',
            'class' => 'form-check-label',
        ]
    )
    . html_writer::div(
        get_string(
            'commerce_storefront_n814_show_storefront_cta_help',
            'local_subscriptions'
        ),
        'form-text'
    ),
    'form-check commerce-storefront-n814-showroom-cta'
);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div(
    'card card-body commerce-storefront-n814-card'
);
echo html_writer::div(
    html_writer::tag('i', '', [
        'class' => 'fa fa-tags',
        'aria-hidden' => 'true',
    ])
    . html_writer::div(
        html_writer::tag(
            'h2',
            get_string(
                'commerce_storefront_n814_merch_title',
                'local_subscriptions'
            ),
            ['class' => 'h5 mb-1']
        )
        . html_writer::tag(
            'p',
            get_string(
                'commerce_storefront_n814_merch_help',
                'local_subscriptions'
            ),
            ['class' => 'text-muted mb-0']
        ),
        'commerce-storefront-n814-heading-copy'
    ),
    'commerce-storefront-n814-card-heading'
);

echo html_writer::start_div('row g-3');
echo html_writer::div(
    html_writer::div(
        html_writer::empty_tag('input', [
            'type' => 'checkbox',
            'name' => 'featured',
            'id' => 'featured',
            'value' => 1,
            'class' => 'form-check-input',
        ] + ($featured ? ['checked' => 'checked'] : []))
        . html_writer::tag(
            'label',
            get_string(
                'commerce_storefront_featured_product',
                'local_subscriptions'
            ),
            ['for' => 'featured', 'class' => 'form-check-label']
        ),
        'form-check mt-4'
    ),
    'col-lg-4'
);
echo html_writer::div(
    html_writer::tag(
        'label',
        get_string(
            'commerce_storefront_n814_display_order',
            'local_subscriptions'
        ),
        ['for' => 'displayorder', 'class' => 'form-label']
    )
    . html_writer::empty_tag('input', [
        'type' => 'number',
        'name' => 'displayorder',
        'id' => 'displayorder',
        'value' => $displayorder,
        'min' => 0,
        'max' => 999999,
        'class' => 'form-control',
    ])
    . html_writer::div(
        get_string(
            'commerce_storefront_n814_display_order_help',
            'local_subscriptions'
        ),
        'form-text'
    ),
    'col-lg-4'
);
echo html_writer::end_div();

echo html_writer::tag(
    'div',
    get_string(
        'commerce_storefront_badges',
        'local_subscriptions'
    ),
    ['class' => 'form-label mt-3']
);
echo html_writer::start_div(
    'commerce-storefront-n814-badge-grid'
);
foreach (CommerceStorefrontPageEditor::badges() as $badge) {
    $id = 'badge-' . $badge;
    echo html_writer::tag(
        'label',
        html_writer::empty_tag('input', [
            'type' => 'checkbox',
            'name' => 'badges[]',
            'id' => $id,
            'value' => $badge,
            'class' => 'form-check-input',
        ] + (
            in_array($badge, $badges, true)
                ? ['checked' => 'checked']
                : []
        ))
        . html_writer::span(
            get_string(
                'commerce_storefront_badge_' . $badge,
                'local_subscriptions'
            )
        ),
        ['class' => 'commerce-storefront-n814-badge']
    );
}
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div(
    'card card-body commerce-storefront-n814-card'
);
echo html_writer::div(
    html_writer::tag('i', '', [
        'class' => 'fa fa-handshake-o',
        'aria-hidden' => 'true',
    ])
    . html_writer::div(
        html_writer::tag(
            'h2',
            get_string(
                'commerce_storefront_n814_experience_title',
                'local_subscriptions'
            ),
            ['class' => 'h5 mb-1']
        )
        . html_writer::tag(
            'p',
            get_string(
                'commerce_storefront_n814_experience_help',
                'local_subscriptions'
            ),
            ['class' => 'text-muted mb-0']
        ),
        'commerce-storefront-n814-heading-copy'
    ),
    'commerce-storefront-n814-card-heading'
);

echo html_writer::start_div('row g-3');
echo html_writer::div(
    html_writer::tag(
        'label',
        get_string(
            'commerce_storefront_n814_catalog_group',
            'local_subscriptions'
        ),
        ['for' => 'group', 'class' => 'form-label']
    )
    . html_writer::select(
        $groupoptions,
        'group',
        $group,
        false,
        ['id' => 'group', 'class' => 'form-select']
    )
    . html_writer::div(
        get_string(
            'commerce_storefront_n814_catalog_group_help',
            'local_subscriptions'
        ),
        'form-text'
    ),
    'col-lg-5'
);
echo html_writer::end_div();

echo html_writer::start_div(
    'commerce-storefront-n814-auto-panel mt-3'
);
echo html_writer::div(
    html_writer::div(
        html_writer::tag(
            'strong',
            get_string(
                'commerce_storefront_n814_reassurance_title',
                'local_subscriptions'
            )
        )
        . html_writer::span(
            $trustmode === 'auto'
                ? get_string(
                    'commerce_storefront_n814_automatic',
                    'local_subscriptions'
                )
                : get_string(
                    'commerce_storefront_n814_custom',
                    'local_subscriptions'
                ),
            'badge rounded-pill '
                . ($trustmode === 'auto'
                    ? 'text-bg-success'
                    : 'text-bg-light')
        ),
        'commerce-storefront-n814-auto-title'
    )
    . html_writer::tag(
        'p',
        get_string(
            'commerce_storefront_n814_reassurance_help',
            'local_subscriptions'
        ),
        ['class' => 'text-muted small mb-0']
    ),
    'commerce-storefront-n814-auto-copy'
);
echo html_writer::select(
    [
        'auto' => get_string(
            'commerce_storefront_n814_reassurance_auto',
            'local_subscriptions'
        ),
        'custom' => get_string(
            'commerce_storefront_n814_reassurance_custom',
            'local_subscriptions'
        ),
    ],
    'trustmode',
    $trustmode,
    false,
    [
        'class' => 'form-select commerce-storefront-n814-mode-select',
        'data-n814-trust-mode' => '1',
    ]
);
echo html_writer::end_div();

echo html_writer::div(
    implode(
        '',
        array_map(
            static fn(string $item): string =>
                html_writer::span(
                    html_writer::tag('i', '', [
                        'class' => 'fa fa-check me-1',
                        'aria-hidden' => 'true',
                    ])
                    . get_string(
                        'commerce_storefront_trust_' . $item,
                        'local_subscriptions'
                    ),
                    'commerce-storefront-n814-auto-pill'
                ),
            $defaulttrust
        )
    ),
    'commerce-storefront-n814-auto-values',
    ['data-n814-auto-trust' => '1']
);

echo html_writer::start_div(
    'commerce-storefront-n814-custom-trust',
    ['data-n814-custom-trust' => '1']
);
foreach (CommerceStorefrontPageEditor::trust_items() as $item) {
    $id = 'trust-' . $item;
    echo html_writer::tag(
        'label',
        html_writer::empty_tag('input', [
            'type' => 'checkbox',
            'name' => 'trust[]',
            'id' => $id,
            'value' => $item,
            'class' => 'form-check-input',
        ] + (
            in_array($item, $trust, true)
                ? ['checked' => 'checked']
                : []
        ))
        . html_writer::span(
            get_string(
                'commerce_storefront_trust_' . $item,
                'local_subscriptions'
            )
        ),
        ['class' => 'commerce-storefront-n814-trust-choice']
    );
}
echo html_writer::end_div();

echo html_writer::div(
    html_writer::tag(
        'label',
        get_string(
            'commerce_storefront_quickfacts',
            'local_subscriptions'
        ),
        ['for' => 'quickfacts', 'class' => 'form-label']
    )
    . html_writer::tag(
        'textarea',
        s($quickfactstext),
        [
            'name' => 'quickfacts',
            'id' => 'quickfacts',
            'class' => 'form-control',
            'rows' => 4,
            'placeholder' => '82 h ||| vidéos',
        ]
    )
    . html_writer::div(
        get_string(
            'commerce_storefront_quickfacts_help',
            'local_subscriptions'
        ),
        'form-text'
    ),
    'mt-3'
);
echo html_writer::end_div();

echo html_writer::start_div(
    'card card-body commerce-storefront-n814-card'
);
echo html_writer::div(
    html_writer::tag('i', '', [
        'class' => 'fa fa-thumbs-o-up',
        'aria-hidden' => 'true',
    ])
    . html_writer::div(
        html_writer::tag(
            'h2',
            get_string(
                'commerce_storefront_n814_recommendations_title',
                'local_subscriptions'
            ),
            ['class' => 'h5 mb-1']
        )
        . html_writer::tag(
            'p',
            get_string(
                'commerce_storefront_n814_recommendations_help',
                'local_subscriptions'
            ),
            ['class' => 'text-muted mb-0']
        ),
        'commerce-storefront-n814-heading-copy'
    ),
    'commerce-storefront-n814-card-heading'
);
echo html_writer::select(
    $productoptions,
    'recommendations[]',
    $recommendations,
    false,
    [
        'class' => 'form-select',
        'multiple' => 'multiple',
        'size' => min(8, max(4, count($productoptions))),
    ]
);
echo html_writer::div(
    get_string(
        'commerce_storefront_n814_recommendations_limit',
        'local_subscriptions'
    ),
    'form-text'
);
echo html_writer::end_div();

echo html_writer::start_div(
    'card card-body commerce-storefront-n814-card'
);
echo html_writer::div(
    html_writer::tag('i', '', [
        'class' => 'fa fa-search',
        'aria-hidden' => 'true',
    ])
    . html_writer::div(
        html_writer::tag(
            'h2',
            get_string(
                'commerce_storefront_n814_seo_title',
                'local_subscriptions'
            ),
            ['class' => 'h5 mb-1']
        )
        . html_writer::tag(
            'p',
            get_string(
                'commerce_storefront_n814_seo_help',
                'local_subscriptions'
            ),
            ['class' => 'text-muted mb-0']
        ),
        'commerce-storefront-n814-heading-copy'
    ),
    'commerce-storefront-n814-card-heading'
);
echo html_writer::start_div('commerce-storefront-n816-seo-language mb-3');
echo html_writer::tag(
    'label',
    get_string('commerce_storefront_n816_seo_language', 'local_subscriptions'),
    ['for' => 'editlang-switch', 'class' => 'form-label']
);
echo html_writer::select(
    $languageoptions,
    'editlang-switch',
    $editlanguage,
    false,
    [
        'id' => 'editlang-switch',
        'class' => 'form-select',
        'data-n814-language-switch' => '1',
    ]
);
echo html_writer::div(
    get_string('commerce_storefront_n816_seo_language_help', 'local_subscriptions'),
    'form-text'
);
echo html_writer::end_div();

echo html_writer::div(
    html_writer::div(
        html_writer::span(
            get_string(
                'commerce_storefront_n814_auto_title_label',
                'local_subscriptions'
            ),
            'commerce-storefront-n814-seo-label'
        )
        . html_writer::tag(
            'strong',
            s($autoseotitle)
        )
        . html_writer::span(
            get_string(
                'commerce_storefront_n814_auto_description_label',
                'local_subscriptions'
            ),
            'commerce-storefront-n814-seo-label mt-2'
        )
        . html_writer::span(
            s($autoseodescription)
        ),
        'commerce-storefront-n814-seo-preview-copy'
    )
    . html_writer::span(
        get_string(
            'commerce_storefront_n814_automatic',
            'local_subscriptions'
        ),
        'badge rounded-pill text-bg-success'
    ),
    'commerce-storefront-n814-seo-preview'
);

echo html_writer::select(
    [
        'auto' => get_string(
            'commerce_storefront_n814_seo_auto',
            'local_subscriptions'
        ),
        'custom' => get_string(
            'commerce_storefront_n814_seo_custom',
            'local_subscriptions'
        ),
    ],
    'seomode',
    $seomode,
    false,
    [
        'class' => 'form-select commerce-storefront-n814-mode-select mt-3',
        'data-n814-seo-mode' => '1',
    ]
);

echo html_writer::start_div(
    'commerce-storefront-n814-seo-custom',
    ['data-n814-seo-custom' => '1']
);
echo html_writer::div(
    html_writer::tag(
        'label',
        get_string(
            'commerce_storefront_seo_page_title',
            'local_subscriptions'
        ),
        ['for' => 'seotitle', 'class' => 'form-label']
    )
    . html_writer::empty_tag('input', [
        'type' => 'text',
        'name' => 'seotitle',
        'id' => 'seotitle',
        'value' => (string)($seo['title'] ?? ''),
        'class' => 'form-control',
        'maxlength' => 120,
    ]),
    'mt-3'
);
echo html_writer::div(
    html_writer::tag(
        'label',
        get_string(
            'commerce_storefront_seo_description',
            'local_subscriptions'
        ),
        ['for' => 'seodescription', 'class' => 'form-label']
    )
    . html_writer::tag(
        'textarea',
        s((string)($seo['description'] ?? '')),
        [
            'name' => 'seodescription',
            'id' => 'seodescription',
            'class' => 'form-control',
            'rows' => 3,
            'maxlength' => 320,
        ]
    ),
    'mt-3'
);
echo html_writer::end_div();

echo html_writer::tag(
    'details',
    html_writer::tag(
        'summary',
        html_writer::tag('i', '', [
            'class' => 'fa fa-link me-2',
            'aria-hidden' => 'true',
        ])
        . get_string(
            'commerce_storefront_n814_public_urls',
            'local_subscriptions'
        )
    )
    . html_writer::div(
        implode('', array_map(
            static function(string $lang) use ($slugs): string {
                return html_writer::div(
                    html_writer::tag(
                        'label',
                        strtoupper($lang),
                        [
                            'for' => 'slug' . $lang,
                            'class' => 'form-label',
                        ]
                    )
                    . html_writer::empty_tag('input', [
                        'type' => 'text',
                        'name' => 'slug' . $lang,
                        'id' => 'slug' . $lang,
                        'value' => (string)($slugs[$lang] ?? ''),
                        'class' => 'form-control',
                        'maxlength' => 120,
                    ]),
                    'commerce-storefront-n814-slug-field'
                );
            },
            ['fr', 'en', 'ru']
        )),
        'commerce-storefront-n814-slug-grid'
    ),
    ['class' => 'commerce-storefront-n814-advanced mt-3']
);
echo html_writer::end_div();

echo html_writer::div(
    html_writer::link(
        new moodle_url(
            '/local/subscriptions/admin/commerce/products/storefront.php',
            [
                'sku' => $sku,
                'area' => 'distribution',
                'editlang' => $editlanguage,
            ]
        ),
        get_string('cancel'),
        ['class' => 'btn btn-outline-secondary']
    )
    . html_writer::tag(
        'button',
        html_writer::tag('i', '', [
            'class' => 'fa fa-save me-1',
            'aria-hidden' => 'true',
        ])
        . get_string('savechanges'),
        [
            'type' => 'submit',
            'class' => 'btn btn-primary',
        ]
    ),
    'commerce-storefront-n814-actions'
);
echo html_writer::end_tag('form');

$baseurl = new moodle_url(
    '/local/subscriptions/admin/commerce/products/storefront_distribution.php',
    ['sku' => $sku]
);
$baseurljs = json_encode($baseurl->out(false));
$PAGE->requires->js_init_code(<<<JS
(function() {
    var language = document.querySelector(
        '[data-n814-language-switch]'
    );
    if (language) {
        language.addEventListener('change', function() {
            window.location.href = {$baseurljs}
                + '&editlang='
                + encodeURIComponent(language.value);
        });
    }

    function syncDiscovery() {
        var checked = document.querySelector(
            'input[name="discoverymode"]:checked'
        );
        var mode = checked ? checked.value : 'storefront';
        document.querySelectorAll(
            '[data-n814-discovery-choice]'
        ).forEach(function(choice) {
            choice.classList.toggle(
                'is-selected',
                choice.getAttribute(
                    'data-n814-discovery-choice'
                ) === mode
            );
        });
        var fields = document.querySelector(
            '[data-n814-showroom-fields]'
        );
        if (fields) {
            fields.classList.toggle(
                'is-secondary',
                mode !== 'showroom'
            );
        }
    }
    document.querySelectorAll(
        'input[name="discoverymode"]'
    ).forEach(function(input) {
        input.addEventListener('change', syncDiscovery);
    });

    function syncMode(selector, target, customValue) {
        var control = document.querySelector(selector);
        var panel = document.querySelector(target);
        if (!control || !panel) {
            return;
        }
        var update = function() {
            panel.classList.toggle(
                'is-hidden',
                control.value !== customValue
            );
        };
        control.addEventListener('change', update);
        update();
    }

    syncMode(
        '[data-n814-trust-mode]',
        '[data-n814-custom-trust]',
        'custom'
    );
    syncMode(
        '[data-n814-seo-mode]',
        '[data-n814-seo-custom]',
        'custom'
    );
    syncDiscovery();
})();
JS);

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
