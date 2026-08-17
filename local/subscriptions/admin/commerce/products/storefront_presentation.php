<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\catalog\presentation\CommerceCatalogProductNameResolver;
use local_subscriptions\commerce\catalog\rendering\CommerceProductEditorNavigationRenderer;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\commerce\storefront\admin\CommerceStorefrontPageEditor;
use local_subscriptions\commerce\storefront\admin\CommerceStorefrontPresentationService;
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

$pageeditor = new CommerceStorefrontPageEditor();
$definition = $pageeditor->definition_from_product(
    $product,
    current_language()
);

$pageurl = new moodle_url(
    '/local/subscriptions/admin/commerce/products/storefront_presentation.php',
    ['sku' => $sku]
);
$title = get_string(
    'commerce_storefront_n815_presentation_title',
    'local_subscriptions',
    $displayname
);
CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $title,
    'local-subscriptions-commerce-storefront-presentation-page'
);

if (data_submitted() && confirm_sesskey()) {
    $service = new CommerceStorefrontPresentationService();
    $metadata = $service->apply(
        $product->get_metadata(),
        [
            'template' => optional_param(
                'template',
                'default',
                PARAM_ALPHANUMEXT
            ),
            'commerceposition' => optional_param(
                'commerceposition',
                'sidebar_sticky',
                PARAM_ALPHANUMEXT
            ),
            'shellmode' => optional_param(
                'shellmode',
                'standard',
                PARAM_ALPHA
            ),
            'headermode' => optional_param(
                'headermode',
                'automatic',
                PARAM_ALPHA
            ),
            'showheader' => optional_param(
                'showheader',
                0,
                PARAM_BOOL
            ),
            'showfooter' => optional_param(
                'showfooter',
                0,
                PARAM_BOOL
            ),
            'theme' => optional_param(
                'theme',
                'default',
                PARAM_ALPHANUMEXT
            ),
            'globalzones' => optional_param(
                'globalzones',
                '',
                PARAM_RAW_TRIMMED
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

$template = (string)($definition['template'] ?? 'default');
$position = (string)($definition['commerce_position'] ?? 'sidebar_sticky');
$shellmode = (string)($definition['shell_mode'] ?? 'standard');
$headermode = (string)($definition['product_header_mode'] ?? 'automatic');
$showheader = !empty($definition['show_header']);
$showfooter = !empty($definition['show_footer']);
$theme = (string)($storefront['theme'] ?? 'default');
$globalzones = $storefront['global_zones'] ?? [];
if (is_array($globalzones)) {
    $globalzones = implode(',', $globalzones);
} else {
    $globalzones = (string)$globalzones;
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
        $pageurl,
    ],
    'distribution' => [
        'fa fa-bullhorn',
        get_string('commerce_storefront_n812_tab_distribution', 'local_subscriptions'),
        new moodle_url(
            '/local/subscriptions/admin/commerce/products/storefront_distribution.php',
            ['sku' => $sku, 'editlang' => $editlanguage]
        ),
    ],
    'tools' => [
        'fa fa-wrench',
        get_string('commerce_storefront_n812_tab_tools', 'local_subscriptions'),
        new moodle_url(
            '/local/subscriptions/admin/commerce/products/storefront_tools.php',
            ['sku' => $sku, 'editlang' => $editlanguage]
        ),
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
            'commerce_storefront_n815_presentation_intro',
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
                . ($key === 'presentation' ? ' is-active' : ''),
        ]
    );
}
echo html_writer::end_div();

echo html_writer::start_tag('form', [
    'method' => 'post',
    'class' => 'commerce-storefront-n815-presentation-form',
]);
echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'sesskey',
    'value' => sesskey(),
]);

echo html_writer::start_div(
    'card card-body commerce-storefront-n815-card'
);
echo html_writer::tag(
    'h2',
    get_string(
        'commerce_storefront_n815_layout_title',
        'local_subscriptions'
    ),
    ['class' => 'h5 mb-1']
);
echo html_writer::tag(
    'p',
    get_string(
        'commerce_storefront_n815_layout_help',
        'local_subscriptions'
    ),
    ['class' => 'text-muted mb-3']
);

echo html_writer::start_div(
    'commerce-storefront-n815-layout-grid'
);
foreach ([
    'default' => [
        'commerce_storefront_template_default',
        'commerce_storefront_n815_template_default_help',
        'standard',
    ],
    'editorial' => [
        'commerce_storefront_template_editorial',
        'commerce_storefront_n815_template_editorial_help',
        'editorial',
    ],
    'immersive' => [
        'commerce_storefront_template_immersive',
        'commerce_storefront_n815_template_immersive_help',
        'immersive',
    ],
] as $value => [$labelkey, $helpkey, $wire]) {
    echo html_writer::tag(
        'label',
        html_writer::empty_tag('input', [
            'type' => 'radio',
            'name' => 'template',
            'value' => $value,
            'class' => 'form-check-input',
        ] + ($template === $value ? ['checked' => 'checked'] : []))
        . html_writer::div(
            '',
            'commerce-storefront-n815-template-wire '
                . 'commerce-storefront-n815-template-wire--' . $wire,
            ['aria-hidden' => 'true']
        )
        . html_writer::tag(
            'strong',
            get_string($labelkey, 'local_subscriptions')
        )
        . html_writer::span(
            get_string($helpkey, 'local_subscriptions')
        ),
        [
            'class' => 'commerce-storefront-n815-layout-choice'
                . ($template === $value ? ' is-selected' : ''),
            'data-n815-choice' => 'template',
        ]
    );
}
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div(
    'card card-body commerce-storefront-n815-card'
);
echo html_writer::tag(
    'h2',
    get_string(
        'commerce_storefront_n815_commerce_title',
        'local_subscriptions'
    ),
    ['class' => 'h5 mb-1']
);
echo html_writer::tag(
    'p',
    get_string(
        'commerce_storefront_n815_commerce_help',
        'local_subscriptions'
    ),
    ['class' => 'text-muted mb-3']
);

echo html_writer::start_div(
    'commerce-storefront-n815-position-grid'
);
foreach ([
    'hero_integrated' => [
        'commerce_storefront_commerce_position_hero',
        'commerce_storefront_n815_position_hero_help',
        'hero',
    ],
    'sidebar_sticky' => [
        'commerce_storefront_commerce_position_sidebar',
        'commerce_storefront_n815_position_sidebar_help',
        'sidebar',
    ],
    'none' => [
        'commerce_storefront_commerce_position_none',
        'commerce_storefront_n815_position_none_help',
        'none',
    ],
] as $value => [$labelkey, $helpkey, $wire]) {
    echo html_writer::tag(
        'label',
        html_writer::empty_tag('input', [
            'type' => 'radio',
            'name' => 'commerceposition',
            'value' => $value,
            'class' => 'form-check-input',
        ] + ($position === $value ? ['checked' => 'checked'] : []))
        . html_writer::div(
            '',
            'commerce-storefront-n815-position-wire '
                . 'commerce-storefront-n815-position-wire--' . $wire,
            ['aria-hidden' => 'true']
        )
        . html_writer::tag(
            'strong',
            get_string($labelkey, 'local_subscriptions')
        )
        . html_writer::span(
            get_string($helpkey, 'local_subscriptions')
        ),
        [
            'class' => 'commerce-storefront-n815-position-choice'
                . ($position === $value ? ' is-selected' : ''),
            'data-n815-choice' => 'commerceposition',
        ]
    );
}
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div(
    'card card-body commerce-storefront-n815-card'
);
echo html_writer::tag(
    'h2',
    get_string(
        'commerce_storefront_n815_shell_title',
        'local_subscriptions'
    ),
    ['class' => 'h5 mb-1']
);
echo html_writer::tag(
    'p',
    get_string(
        'commerce_storefront_n815_shell_help',
        'local_subscriptions'
    ),
    ['class' => 'text-muted mb-3']
);

echo html_writer::start_div('row g-3');
echo html_writer::div(
    html_writer::tag(
        'label',
        get_string('commerce_storefront_shell_mode', 'local_subscriptions'),
        ['for' => 'shellmode', 'class' => 'form-label']
    )
    . html_writer::select(
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
        'shellmode',
        $shellmode,
        false,
        ['id' => 'shellmode', 'class' => 'form-select']
    ),
    'col-lg-6'
);
echo html_writer::div(
    html_writer::tag(
        'label',
        get_string(
            'commerce_storefront_product_header_mode',
            'local_subscriptions'
        ),
        ['for' => 'headermode', 'class' => 'form-label']
    )
    . html_writer::select(
        [
            'automatic' => get_string(
                'commerce_storefront_product_header_automatic',
                'local_subscriptions'
            ),
            'builder' => get_string(
                'commerce_storefront_product_header_builder',
                'local_subscriptions'
            ),
            'hidden' => get_string(
                'commerce_storefront_product_header_hidden',
                'local_subscriptions'
            ),
        ],
        'headermode',
        $headermode,
        false,
        ['id' => 'headermode', 'class' => 'form-select']
    ),
    'col-lg-6'
);
echo html_writer::end_div();

echo html_writer::start_div(
    'commerce-storefront-n815-switches mt-3'
);
foreach ([
    'showheader' => [
        $showheader,
        'commerce_storefront_show_header',
    ],
    'showfooter' => [
        $showfooter,
        'commerce_storefront_show_footer',
    ],
] as $name => [$checked, $stringkey]) {
    echo html_writer::div(
        html_writer::empty_tag('input', [
            'type' => 'checkbox',
            'name' => $name,
            'id' => $name,
            'value' => 1,
            'class' => 'form-check-input',
        ] + ($checked ? ['checked' => 'checked'] : []))
        . html_writer::tag(
            'label',
            get_string($stringkey, 'local_subscriptions'),
            ['for' => $name, 'class' => 'form-check-label']
        ),
        'form-check'
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
            'commerce_storefront_n815_advanced_title',
            'local_subscriptions'
        )
    )
    . html_writer::div(
        html_writer::div(
            html_writer::tag(
                'label',
                get_string(
                    'commerce_storefront_theme',
                    'local_subscriptions'
                ),
                ['for' => 'theme', 'class' => 'form-label']
            )
            . html_writer::empty_tag('input', [
                'type' => 'text',
                'name' => 'theme',
                'id' => 'theme',
                'value' => $theme,
                'class' => 'form-control',
            ]),
            'mb-3'
        )
        . html_writer::div(
            html_writer::tag(
                'label',
                get_string(
                    'commerce_storefront_global_zones_title',
                    'local_subscriptions'
                ),
                ['for' => 'globalzones', 'class' => 'form-label']
            )
            . html_writer::empty_tag('input', [
                'type' => 'text',
                'name' => 'globalzones',
                'id' => 'globalzones',
                'value' => $globalzones,
                'class' => 'form-control',
            ])
            . html_writer::div(
                get_string(
                    'commerce_storefront_n815_advanced_help',
                    'local_subscriptions'
                ),
                'form-text'
            ),
            ''
        ),
        'commerce-storefront-n815-advanced-body'
    ),
    ['class' => 'commerce-storefront-n815-advanced mt-3']
);
echo html_writer::end_div();

echo html_writer::div(
    html_writer::link(
        new moodle_url(
            '/local/subscriptions/admin/commerce/products/storefront.php',
            ['sku' => $sku]
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
    'commerce-storefront-n815-actions'
);

echo html_writer::end_tag('form');

$PAGE->requires->js_init_code(<<<JS
(function() {
    document.querySelectorAll('[data-n815-choice]').forEach(function(card) {
        var input = card.querySelector('input[type="radio"]');
        if (!input) {
            return;
        }
        input.addEventListener('change', function() {
            document.querySelectorAll(
                '[data-n815-choice="' + card.getAttribute('data-n815-choice') + '"]'
            ).forEach(function(other) {
                var otherInput = other.querySelector('input[type="radio"]');
                other.classList.toggle(
                    'is-selected',
                    !!otherInput && otherInput.checked
                );
            });
        });
    });
})();
JS);

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
