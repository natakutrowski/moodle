<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\catalog\editing\CommerceCatalogCompatibilityEditor;
use local_subscriptions\commerce\catalog\assets\CommerceCatalogDigitalFileManager;
use local_subscriptions\commerce\catalog\assets\CommerceCatalogMediaManager;
use local_subscriptions\commerce\catalog\navigation\CommerceCatalogIdentity;
use local_subscriptions\commerce\catalog\navigation\CommerceCatalogLinkGenerator;
use local_subscriptions\commerce\catalog\presentation\CommerceCatalogPresentation;
use local_subscriptions\commerce\catalog\presentation\CommerceCatalogProductNameResolver;
use local_subscriptions\commerce\catalog\presentation\CommerceCatalogFulfillmentPresentation;
use local_subscriptions\commerce\catalog\validation\CommerceCatalogValidator;
use local_subscriptions\commerce\catalog\validation\CommerceCatalogActivationValidator;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\commerce\catalog\readmodel\CommerceCatalogReadRepository;
use local_subscriptions\commerce\statistics\CommerceStatisticsFilter;
use local_subscriptions\commerce\statistics\CommerceStatisticsPeriod;
use local_subscriptions\commerce\statistics\CommerceStatisticsRepository;
use local_subscriptions\commerce\statistics\CommerceStatisticsSeriesService;
use local_subscriptions\commerce\statistics\CommerceStatisticsPeriodResolver;
use local_subscriptions\commerce\statistics\product\CommerceProductStatisticsDashboardRepository;
use local_subscriptions\crm\commerce\presentation\CommerceDesignSystemRenderer;
use local_subscriptions\crm\commerce\presentation\CommerceProductPageHeaderRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\commerce\statistics\CommerceStatisticsChartRenderer;
use local_subscriptions\crm\commerce\statistics\CommerceProductStatisticsDashboardRenderer;
use local_subscriptions\crm\commerce\statistics\CommerceStatisticsBreakdownRenderer;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$catalogkey = optional_param('catalogkey', '', PARAM_RAW_TRIMMED);
$origin = optional_param('origin', '', PARAM_ALPHANUMEXT);
$id = optional_param('id', 0, PARAM_INT);
$sku = optional_param('sku', '', PARAM_RAW_TRIMMED);
$statisticscurrency = strtoupper(optional_param('statscurrency', '', PARAM_ALPHA));
$statisticsperiodkey = optional_param('statsperiod', '30', PARAM_ALPHANUMEXT);
$statisticsfrom = optional_param('statsfrom', '', PARAM_RAW_TRIMMED);
$statisticsuntil = optional_param('statsuntil', '', PARAM_RAW_TRIMMED);
$statisticschartmode = optional_param('statschartmode', 'instant', PARAM_ALPHA);
if (!in_array($statisticschartmode, ['instant', 'cumulative'], true)) {
    $statisticschartmode = 'instant';
}

$repository = new CommerceCatalogReadRepository($DB);

if ($sku !== '') {
    $details = $repository->find_by_sku($sku) ?? $repository->find_by_purchase_reference($sku);
} else {
    $identity = CommerceCatalogIdentity::from_request($catalogkey, $origin, $id);
    $details = $identity === null
        ? null
        : $repository->find_by_origin_and_id($identity->get_origin(), $identity->get_id());
}
if ($details === null) { throw new moodle_exception('commerce_catalog_product_not_found', 'local_subscriptions'); }
$product = $details->get_summary();
$displayname = CommerceCatalogProductNameResolver::resolve($DB, $product);
$pageurl = CommerceCatalogLinkGenerator::view_url($product);
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $displayname, 'local-subscriptions-commerce-product-view-page');
$PAGE->requires->css(new moodle_url('/local/subscriptions/styles/commerce_product_statistics.css'));
$PAGE->requires->css(new moodle_url('/local/subscriptions/styles/commerce_statistics_breakdowns.css'));

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('commerce_catalog_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/products/index.php')],
    ['label' => $displayname, 'url' => null],
]);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::PRODUCTS);

$originlabel = $product->get_origin() === 'native'
    ? get_string('commerce_catalog_origin_native_short', 'local_subscriptions')
    : get_string('commerce_catalog_origin_legacy_short', 'local_subscriptions');
$metahtml = CommerceCatalogPresentation::badge(
    'type',
    $product->get_type()
)
. html_writer::span(
    s($originlabel),
    'crm-product-view-origin-badge'
);
$actions = [[
    'label' => get_string('commerce_back_to_products', 'local_subscriptions'),
    'url' => new moodle_url('/local/subscriptions/admin/commerce/products/index.php'),
    'class' => 'btn btn-outline-secondary',
    'icon' => 'fa-arrow-left',
]];
$compatibilityeditor = new CommerceCatalogCompatibilityEditor();
if ($product->get_origin() === 'native') {
    $actions[] = [
        'label' => get_string('edit'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/products/edit.php', ['sku' => $product->get_sku()]),
        'class' => 'btn crm-product-action-primary',
        'icon' => 'fa-pencil',
    ];
    $actions[] = [
        'label' => get_string('commerce_product_assets_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/products/assets.php', ['sku' => $product->get_sku()]),
        'class' => 'btn btn-outline-primary',
        'icon' => 'fa-picture-o',
    ];
    $isactive = $product->get_availability() === 'on_sale';
    $actions[] = [
        'label' => get_string(
            $isactive ? 'commerce_product_deactivate' : 'commerce_product_activate',
            'local_subscriptions'
        ),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/products/status.php', [
            'sku' => $product->get_sku(),
            'action' => $isactive ? 'deactivate' : 'activate',
            'return' => 'view',
            'sesskey' => sesskey(),
        ]),
        'class' => $isactive ? 'btn btn-outline-warning' : 'btn btn-success',
        'icon' => $isactive ? 'fa-toggle-off' : 'fa-toggle-on',
    ];
} else {
    $legacyediturl = $compatibilityeditor->legacy_edit_url($product->get_origin(), (int)$product->get_id());
    if ($legacyediturl !== null) {
        $actions[] = [
            'label' => get_string('commerce_edit_in_source', 'local_subscriptions'),
            'url' => $legacyediturl,
            'class' => 'btn btn-outline-primary',
            'icon' => 'fa-external-link',
        ];
    }

    $isactive = $product->get_availability() === 'on_sale';
    $actions[] = [
        'label' => get_string(
            $isactive ? 'commerce_product_deactivate' : 'commerce_product_activate',
            'local_subscriptions'
        ),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/products/legacy_status.php', [
            'origin' => $product->get_origin(),
            'id' => (int)$product->get_id(),
            'action' => $isactive ? 'deactivate' : 'activate',
            'return' => 'view',
            'sesskey' => sesskey(),
        ]),
        'class' => $isactive ? 'btn btn-outline-warning' : 'btn btn-success',
        'icon' => $isactive ? 'fa-toggle-off' : 'fa-toggle-on',
    ];
}
echo CommerceProductPageHeaderRenderer::render(
    $displayname, $metahtml, CommerceDesignSystemRenderer::action_bar($actions, 'justify-content-end'),
    get_string('commerce_catalog_product_eyebrow', 'local_subscriptions')
);

$languageflags = [
    'fr' => '🇫🇷',
    'en' => '🇬🇧',
    'ru' => '🇷🇺',
];
$pricecurrencies = [];
foreach ($product->get_prices() as $price) {
    $pricecurrency = strtoupper($price->get_currency());
    $pricecurrencies[$pricecurrency] = match ($pricecurrency) {
        'EUR' => '🇪🇺',
        'RUB' => '🇷🇺',
        default => '🌐',
    };
}
$translationflags = [];
foreach ($details->get_translations() as $translation) {
    $language = strtolower((string)$translation['language']);
    $translationflags[$language] = $languageflags[$language] ?? '🌐';
}

$topmetrics = [
    [
        'label' => get_string('commerce_prices', 'local_subscriptions'),
        'value' => implode(
            '  ',
            array_map(
                static fn(string $currency, string $flag): string =>
                    $flag . ' ' . $currency,
                array_keys($pricecurrencies),
                array_values($pricecurrencies)
            )
        ),
    ],
    [
        'label' => get_string('commerce_translations', 'local_subscriptions'),
        'value' => $translationflags === []
            ? '—'
            : implode('  ', array_values($translationflags)),
    ],
];
if ($product->get_type() === 'bundle') {
    $topmetrics[] = [
        'label' => get_string('commerce_components', 'local_subscriptions'),
        'value' => count($details->get_components()),
    ];
}
echo CommerceDesignSystemRenderer::metrics($topmetrics);

echo html_writer::start_div('crm-product-view-overview-grid');
echo html_writer::start_div('card card-body crm-product-view-card crm-product-view-summary-card');
echo html_writer::tag(
    'h3',
    html_writer::tag('i', '', [
        'class' => 'fa fa-info-circle me-2',
        'aria-hidden' => 'true',
    ])
    . get_string('commerce_product_summary', 'local_subscriptions'),
    ['class' => 'h5 crm-product-view-card-title']
);

if (trim((string)$product->get_description()) !== '') {
    echo html_writer::tag(
        'p',
        format_text($product->get_description(), FORMAT_PLAIN),
        ['class' => 'crm-product-view-description']
    );
}

$statusdefinitions = [
    [
        'label' => get_string('commerce_product_status_publication', 'local_subscriptions'),
        'help' => get_string('commerce_product_status_publication_help', 'local_subscriptions'),
        'badge' => CommerceCatalogPresentation::badge(
            'editorial',
            $product->get_editorial_status()
        ),
    ],
    [
        'label' => get_string('commerce_product_status_visibility', 'local_subscriptions'),
        'help' => get_string('commerce_product_status_visibility_help', 'local_subscriptions'),
        'badge' => CommerceCatalogPresentation::badge(
            'visibility',
            $product->get_visibility()
        ),
    ],
    [
        'label' => get_string('commerce_product_status_sale', 'local_subscriptions'),
        'help' => get_string('commerce_product_status_sale_help', 'local_subscriptions'),
        'badge' => CommerceCatalogPresentation::badge(
            'availability',
            $product->get_availability()
        ),
    ],
    [
        'label' => get_string('commerce_product_status_validation', 'local_subscriptions'),
        'help' => get_string('commerce_product_status_validation_help', 'local_subscriptions'),
        'badge' => CommerceCatalogPresentation::badge(
            'technical',
            $product->get_technical_state()
        ),
    ],
];
$statushtml = '';
foreach ($statusdefinitions as $statusdefinition) {
    $statushtml .= html_writer::div(
        html_writer::div(
            html_writer::tag(
                'strong',
                s($statusdefinition['label'])
            )
            . html_writer::span(
                s($statusdefinition['help']),
                'crm-product-view-status-help'
            ),
            'crm-product-view-status-copy'
        )
        . html_writer::div(
            $statusdefinition['badge'],
            'crm-product-view-status-badge'
        ),
        'crm-product-view-status-row'
    );
}
echo html_writer::div(
    $statushtml,
    'crm-product-view-status-grid'
);

$commercialhtml = '';
if ($product->get_prices() !== []) {
    $pricehtml = '';
    foreach ($product->get_prices() as $price) {
        $pricecurrency = strtoupper($price->get_currency());
        $priceflag = $pricecurrencies[$pricecurrency] ?? '🌐';
        $pricehtml .= html_writer::div(
            html_writer::span(
                $priceflag,
                'crm-product-view-price-flag',
                [
                    'role' => 'img',
                    'aria-label' => $pricecurrency,
                    'title' => $pricecurrency,
                ]
            )
            . html_writer::span(
                CommerceCatalogPresentation::prices([$price], $product->get_metadata()),
                'crm-product-view-price-value'
            ),
            'crm-product-view-price-row'
        );
    }
    $commercialhtml .= html_writer::div(
        html_writer::div(
            html_writer::tag('i', '', [
                'class' => 'fa fa-credit-card me-2',
                'aria-hidden' => 'true',
            ])
            . html_writer::tag(
                'strong',
                get_string('commerce_prices', 'local_subscriptions')
            ),
            'crm-product-view-commercial-title'
        )
        . $pricehtml,
        'crm-product-view-commercial-block'
    );
}

$from = $product->get_available_from();
$until = $product->get_available_until();
if ($from || $until) {
    $availabilityhtml = html_writer::div(
        html_writer::tag('i', '', [
            'class' => 'fa fa-calendar me-2',
            'aria-hidden' => 'true',
        ])
        . html_writer::tag(
            'strong',
            get_string('commerce_catalog_availability', 'local_subscriptions')
        ),
        'crm-product-view-commercial-title'
    );
    if ($from) {
        $availabilityhtml .= html_writer::div(
            get_string('commerce_catalog_available_from', 'local_subscriptions')
            . ' : '
            . userdate($from),
            'crm-product-view-availability-line'
        );
    }
    if ($until) {
        $availabilityhtml .= html_writer::div(
            get_string('commerce_catalog_available_until', 'local_subscriptions')
            . ' : '
            . userdate($until),
            'crm-product-view-availability-line'
        );
    }
    $commercialhtml .= html_writer::div(
        $availabilityhtml,
        'crm-product-view-commercial-block'
    );
}
if ($commercialhtml !== '') {
    echo html_writer::div(
        $commercialhtml,
        'crm-product-view-commercial-grid'
    );
}

$technicalcontent = html_writer::tag(
    'dl',
    html_writer::tag(
        'dt',
        get_string('commerce_product_technical_sku', 'local_subscriptions')
    )
    . html_writer::tag(
        'dd',
        html_writer::tag('code', s($product->get_sku()))
    )
    . html_writer::tag(
        'dt',
        get_string('commerce_catalog_origin', 'local_subscriptions')
    )
    . html_writer::tag('dd', s($originlabel)),
    ['class' => 'crm-product-view-technical-list mb-0']
);
if ($details->get_legacy_references() !== []) {
    $referenceshtml = '';
    foreach ($details->get_legacy_references() as $reference) {
        $referenceshtml .= html_writer::div(
            s($reference['family'])
            . ' · '
            . html_writer::tag(
                'code',
                s($reference['table'] . '#' . $reference['id'])
            ),
            'small mb-1'
        );
    }
    $technicalcontent .= html_writer::div(
        html_writer::tag(
            'strong',
            get_string('commerce_catalog_compatibility', 'local_subscriptions')
        )
        . $referenceshtml,
        'crm-product-view-legacy-references'
    );
}
echo html_writer::tag(
    'details',
    html_writer::tag(
        'summary',
        html_writer::tag('i', '', [
            'class' => 'fa fa-code me-2',
            'aria-hidden' => 'true',
        ])
        . get_string(
            'commerce_product_technical_information',
            'local_subscriptions'
        ),
        ['class' => 'crm-product-view-technical-summary']
    )
    . html_writer::div(
        $technicalcontent,
        'crm-product-view-technical-body'
    ),
    ['class' => 'crm-product-view-technical-details']
);

echo html_writer::end_div();


if ($product->get_origin() === 'native') {
    $domainproduct = (new CommerceCatalogFactory($DB))
        ->product_manager()
        ->get_editor_data($product->get_sku())
        ->get_product();
    $validation = (new CommerceCatalogActivationValidator($DB))->validate($domainproduct);
} else {
    $validation = (new CommerceCatalogValidator())->validate($product);
}
if ($validation->has_issues()) {
    echo html_writer::start_div('card card-body mb-4');
    echo html_writer::tag('h3', get_string('commerce_product_diagnostic', 'local_subscriptions'), ['class' => 'h5']);
    foreach ($validation->issues as $issue) {
        $class = $issue->severity === 'error' ? 'alert alert-danger' : ($issue->severity === 'warning' ? 'alert alert-warning' : 'alert alert-info');
        echo html_writer::div(s($issue->message), $class . ' py-2 mb-2');
    }
    echo html_writer::end_div();
}

$metadata = $product->get_metadata();
$legacydigital = null;
foreach ($details->get_legacy_references() as $reference) {
    if ($reference['table'] === 'subscription_digital_product') {
        $legacydigital = $DB->get_record(
            'subscription_digital_product',
            ['id' => (int)$reference['id']],
            '*',
            IGNORE_MISSING
        );
        break;
    }
}

$coverurl = null;
if ($legacydigital && !empty($legacydigital->coverimage)) {
    $legacycoverpath = $CFG->dirroot . '/local/subscriptions/pix/cover/' . basename((string)$legacydigital->coverimage);
    if (is_file($legacycoverpath)) {
        $coverurl = new moodle_url('/local/subscriptions/pix/cover/' . rawurlencode(basename((string)$legacydigital->coverimage)));
    }
}
if ($coverurl === null && $product->get_origin() === 'native' && $product->get_id() !== null) {
    $media = new CommerceCatalogMediaManager(context_system::instance());
    $coverurl = $media->get_url((int)$product->get_id(), CommerceCatalogMediaManager::ROLE_COVER);
}
if ($details->get_translations() !== []) {
    echo html_writer::start_div('card card-body mb-4 crm-product-view-card');
    echo html_writer::tag(
        'h3',
        html_writer::tag('i', '', [
            'class' => 'fa fa-language me-2',
            'aria-hidden' => 'true',
        ])
        . get_string('commerce_product_translations_title', 'local_subscriptions'),
        ['class' => 'h5 crm-product-view-card-title']
    );
    foreach ($details->get_translations() as $translation) {
        $language = strtolower((string)$translation['language']);
        $flag = $languageflags[$language] ?? '🌐';
        $title = html_writer::tag('span', $flag, [
            'class' => 'me-2',
            'lang' => $language,
            'aria-label' => strtoupper($language),
            'role' => 'img',
        ]) . format_string($translation['name']);
        $shortdescription = $translation['shortdescription'] !== ''
            ? html_writer::div(
                format_text($translation['shortdescription'], FORMAT_HTML),
                'fw-semibold mb-2'
            )
            : '';
        echo html_writer::div(
            html_writer::tag('h4', $title, ['class' => 'h6 d-flex align-items-center']) .
            $shortdescription .
            html_writer::div(format_text($translation['description'], FORMAT_HTML), 'text-muted'),
            'border rounded p-3 mb-3'
        );
    }
    echo html_writer::end_div();
}

if ($details->get_components() !== []) {
    echo html_writer::start_div('card card-body mb-4 crm-product-view-card');
    echo html_writer::tag(
        'h3',
        html_writer::tag('i', '', [
            'class' => 'fa fa-cubes me-2',
            'aria-hidden' => 'true',
        ])
        . get_string('commerce_components', 'local_subscriptions'),
        ['class' => 'h5 crm-product-view-card-title']
    );
    foreach ($details->get_components() as $component) {
        $componentname = CommerceCatalogProductNameResolver::resolve_native_id(
            $DB,
            (int)$component['id'],
            (string)$component['name']
        );
        $componenturl = new moodle_url(
            '/local/subscriptions/admin/commerce/products/view.php',
            ['id' => (int)$component['id'], 'origin' => 'native']
        );
        echo html_writer::div(
            CommerceCatalogPresentation::badge(
                'type',
                (string)$component['type']
            )
            . html_writer::link(
                $componenturl,
                html_writer::tag(
                    'strong',
                    format_string($componentname)
                ),
                ['class' => 'crm-product-component-link']
            )
            . html_writer::span(
                '× ' . (int)$component['quantity'],
                'crm-product-component-quantity'
            ),
            'border rounded p-3 mb-2 crm-product-component-row'
        );
    }
    echo html_writer::end_div();
}

echo html_writer::end_div();
if ($product->get_type() === 'digital_download') {
    $nativefiles = new CommerceCatalogDigitalFileManager(
        context_system::instance()
    );
    $desktopfile = $nativefiles->get_file(
        (int)$product->get_id(),
        CommerceCatalogDigitalFileManager::ROLE_DESKTOP
    );
    $mobilefile = $nativefiles->get_file(
        (int)$product->get_id(),
        CommerceCatalogDigitalFileManager::ROLE_MOBILE
    );

    if ($desktopfile || $mobilefile || $legacydigital) {
        $downloadtotal = (int)$DB->get_field_sql(
            'SELECT COALESCE(SUM(downloadcount), 0)
               FROM {local_subs_commerce_dig_access}
              WHERE productsku = :productsku',
            ['productsku' => $product->get_sku()]
        );

        echo html_writer::start_div(
            'card card-body mb-4 crm-product-view-card '
            . 'crm-product-view-digital-files'
        );

        echo html_writer::div(
            html_writer::div(
                html_writer::tag('i', '', [
                    'class' => 'fa fa-file-pdf-o me-2',
                    'aria-hidden' => 'true',
                ])
                . html_writer::tag(
                    'h3',
                    get_string(
                        'commerce_digital_files',
                        'local_subscriptions'
                    ),
                    ['class' => 'h5 mb-0']
                ),
                'crm-product-view-digital-files-title'
            )
            . html_writer::div(
                html_writer::tag(
                    'strong',
                    format_float($downloadtotal, 0)
                )
                . html_writer::span(
                    get_string(
                        'commerce_product_downloads_total',
                        'local_subscriptions'
                    ),
                    'crm-product-view-download-stat-label'
                ),
                'crm-product-view-download-stat'
            ),
            'crm-product-view-digital-files-header'
        );

        $files = [
            'main' => [
                $desktopfile,
                $legacydigital?->filename ?? '',
                'commerce_desktop_file',
                'fa-desktop',
            ],
            'mobile' => [
                $mobilefile,
                $legacydigital?->mobile_filename ?? '',
                'commerce_mobile_file',
                'fa-mobile',
            ],
        ];

        echo html_writer::start_div('crm-product-view-digital-file-list');
        foreach (
            $files as $version => [
                $nativefile,
                $legacyfilename,
                $labelkey,
                $icon,
            ]
        ) {
            $filename = $nativefile instanceof stored_file
                ? $nativefile->get_filename()
                : (string)$legacyfilename;
            if ($filename === '') {
                continue;
            }

            $downloadurl = new moodle_url(
                '/local/subscriptions/admin/commerce/products/download.php',
                [
                    'sku' => $product->get_sku(),
                    'version' => $version,
                ]
            );

            echo html_writer::div(
                html_writer::div(
                    html_writer::tag('i', '', [
                        'class' => 'fa ' . $icon,
                        'aria-hidden' => 'true',
                    ]),
                    'crm-product-view-digital-file-icon'
                )
                . html_writer::div(
                    html_writer::tag(
                        'strong',
                        get_string(
                            $labelkey,
                            'local_subscriptions'
                        ),
                        ['class' => 'crm-product-view-digital-file-label']
                    )
                    . html_writer::div(
                        html_writer::link(
                            $downloadurl,
                            s($filename)
                        ),
                        'crm-product-view-digital-file-name'
                    ),
                    'crm-product-view-digital-file-copy'
                ),
                'crm-product-view-digital-file-row'
            );
        }
        echo html_writer::end_div();
        echo html_writer::end_div();
    }
}

echo html_writer::end_div();

// M5.1 Product Statistics 2.0 — precise, payment-aware and reusable.
$statisticsperiodoptions = CommerceStatisticsPeriodResolver::options();
if (!array_key_exists($statisticsperiodkey, $statisticsperiodoptions)) { $statisticsperiodkey = '30'; }
$statisticsperiod = CommerceStatisticsPeriodResolver::resolve($statisticsperiodkey, $statisticsfrom, $statisticsuntil);
$productreferences = [$product->get_sku()];
foreach ($details->get_legacy_references() as $reference) {
    if ($reference['table'] === 'subscription_plan') {
        $productreferences[] = 'subscription-plan:' . (int)$reference['id'];
    } else if ($reference['table'] === 'subscription_digital_product') {
        $productreferences[] = 'digital-product:' . (int)$reference['id'];
        $legacyrecord = $DB->get_record('subscription_digital_product', ['id' => (int)$reference['id']], 'slug', IGNORE_MISSING);
        if ($legacyrecord && trim((string)$legacyrecord->slug) !== '') { $productreferences[] = 'digital-product:' . trim((string)$legacyrecord->slug); }
    }
}
$productreferences = array_values(array_unique($productreferences));
$m51repository = new CommerceProductStatisticsDashboardRepository($DB);
$allstats = $m51repository->snapshot($statisticsperiod, $productreferences, $product->get_sku(), null);
$availablecurrencies = array_combine(array_keys($allstats['currencies']), array_keys($allstats['currencies'])) ?: [];
if ($statisticscurrency !== '' && !isset($availablecurrencies[$statisticscurrency])) { $statisticscurrency = ''; }
$dashboard = $m51repository->snapshot(
    $statisticsperiod,
    $productreferences,
    $product->get_sku(),
    $statisticscurrency !== '' ? $statisticscurrency : null
);
$previousperiod = CommerceStatisticsPeriodResolver::previous($statisticsperiodkey, $statisticsperiod);
$previousdashboard = $previousperiod !== null
    ? $m51repository->snapshot(
        $previousperiod,
        $productreferences,
        $product->get_sku(),
        $statisticscurrency !== '' ? $statisticscurrency : null
    )
    : null;

$comparisondescription = null;
if ($previousperiod !== null) {
    $comparisonrange = (object)[
        'from' => userdate($previousperiod->start(), get_string('strftimedatetimeshort')),
        'until' => userdate($previousperiod->end() - 1, get_string('strftimedatetimeshort')),
    ];
    $comparisondescription = $statisticsperiodkey === 'today'
        ? get_string('commerce_m51_comparison_today', 'local_subscriptions', $comparisonrange)
        : get_string('commerce_m51_comparison_previous', 'local_subscriptions', $comparisonrange);
}

$revenueseries = $m51repository->revenue_series($statisticsperiod, $productreferences, $statisticscurrency !== '' ? $statisticscurrency : null);
$deliveryseries = $m51repository->delivery_series($statisticsperiod, $productreferences, $product->get_sku(), $statisticscurrency !== '' ? $statisticscurrency : null);
$formatmoney = static function(int $minor, string $currency): string { $major=$minor/100; if(class_exists('NumberFormatter')){$f=new \NumberFormatter(current_language(),\NumberFormatter::CURRENCY);$v=$f->formatCurrency($major,$currency);if($v!==false)return$v;}return format_float($major,2).' '.$currency; };

echo html_writer::start_div('m51-statistics-shell mb-4');
echo html_writer::div(html_writer::tag('h3', get_string('commerce_m51_title','local_subscriptions'), ['class'=>'h4 mb-1']) . html_writer::div(get_string('commerce_m51_subtitle','local_subscriptions'),'text-muted'), 'mb-3');
$filterurl = CommerceCatalogLinkGenerator::view_url($product);
echo html_writer::start_tag('form',['method'=>'get','action'=>$filterurl->out_omit_querystring(),'class'=>'m51-stat-toolbar']);foreach($filterurl->params() as $name=>$value){echo html_writer::empty_tag('input',['type'=>'hidden','name'=>$name,'value'=>$value]);}
$currencyoptions=[''=>get_string('commerce_statistics_all_currencies','local_subscriptions')]+$availablecurrencies;
echo html_writer::div(html_writer::tag('label',get_string('currency'),['for'=>'statscurrency','class'=>'form-label']).html_writer::select($currencyoptions,'statscurrency',$statisticscurrency,false,['id'=>'statscurrency','class'=>'form-select']),'form-group');
echo html_writer::div(html_writer::tag('label',get_string('commerce_statistics_period_label','local_subscriptions'),['for'=>'statsperiod','class'=>'form-label']).html_writer::select($statisticsperiodoptions,'statsperiod',$statisticsperiodkey,false,['id'=>'statsperiod','class'=>'form-select']),'form-group');
$customstyle=$statisticsperiodkey==='custom'?'':'style="display:none"';
echo '<div class="form-group m51-custom-range" '.$customstyle.'><div><label class="form-label">'.s(get_string('commerce_m51_from','local_subscriptions')).'</label><input class="form-control" type="date" name="statsfrom" value="'.s($statisticsfrom).'" /></div><div><label class="form-label">'.s(get_string('commerce_m51_until','local_subscriptions')).'</label><input class="form-control" type="date" name="statsuntil" value="'.s($statisticsuntil).'" /></div></div>';
$exportparams=['sku'=>$product->get_sku(),'statsperiod'=>$statisticsperiodkey,'statsfrom'=>$statisticsfrom,'statsuntil'=>$statisticsuntil,'statscurrency'=>$statisticscurrency];
echo html_writer::div(
    html_writer::tag(
        'button',
        html_writer::tag('i', '', [
            'class' => 'fa fa-filter me-1',
            'aria-hidden' => 'true',
        ])
        . get_string('commerce_filters_apply', 'local_subscriptions'),
        [
            'type' => 'submit',
            'class' => 'btn crm-product-action-primary btn-sm',
        ]
    )
    . html_writer::link(
        new moodle_url(
            '/local/subscriptions/admin/commerce/products/statistics_export.php',
            $exportparams
        ),
        html_writer::tag('i', '', [
            'class' => 'fa fa-file-excel-o me-1',
            'aria-hidden' => 'true',
        ])
        . get_string('commerce_m51_export_excel','local_subscriptions'),
        ['class'=>'btn btn-outline-success btn-sm']
    ),
    'form-group m51-stat-toolbar-actions'
);
echo html_writer::end_tag('form');
echo '<script>document.addEventListener("DOMContentLoaded",function(){var s=document.getElementById("statsperiod"),r=document.querySelector(".m51-custom-range");if(s&&r){s.addEventListener("change",function(){r.style.display=this.value==="custom"?"flex":"none";});}document.querySelectorAll(".m52-revenue-mode").forEach(function(select){select.addEventListener("change",function(){var card=this.closest(".m52-revenue-card");if(!card)return;card.querySelector(".m52-revenue-chart--period").classList.toggle("d-none",this.value!=="period");card.querySelector(".m52-revenue-chart--cumulative").classList.toggle("d-none",this.value!=="cumulative");});});});</script>';
echo CommerceProductStatisticsDashboardRenderer::comparison_note($comparisondescription);
echo CommerceProductStatisticsDashboardRenderer::kpis(
    $dashboard,
    $formatmoney,
    $previousdashboard
);
echo CommerceProductStatisticsDashboardRenderer::insights($dashboard, $previousdashboard, $formatmoney);
echo CommerceProductStatisticsDashboardRenderer::payment_journey($dashboard);
echo CommerceStatisticsBreakdownRenderer::render($dashboard, $formatmoney);
$revenuehtml = CommerceProductStatisticsDashboardRenderer::revenue($OUTPUT, $revenueseries);
$deliverieshtml = CommerceProductStatisticsDashboardRenderer::deliveries($OUTPUT, $deliveryseries);
$piehtml = CommerceProductStatisticsDashboardRenderer::payment_pies($OUTPUT, $dashboard['payments']);

if ($revenuehtml !== '' || $deliverieshtml !== '' || $piehtml !== '') {
    echo html_writer::start_div('m51-chart-dashboard');

    if ($revenuehtml !== '') {
        echo html_writer::div($revenuehtml, 'm51-chart-section m51-chart-section--revenue');
    }

    if ($deliverieshtml !== '') {
        echo html_writer::div(
            $deliverieshtml,
            'm51-chart-section m51-chart-section--deliveries'
        );
    }

    if ($piehtml !== '') {
        echo html_writer::div(
            $piehtml,
            'm51-chart-section m51-chart-section--payments'
        );
    }

    echo html_writer::end_div();
} else {
    echo html_writer::div(
        get_string('commerce_product_statistics_empty', 'local_subscriptions'),
        'alert alert-info mb-0'
    );
}
echo html_writer::end_div();

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
