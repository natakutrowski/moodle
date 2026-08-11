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
use local_subscriptions\commerce\catalog\presentation\CommerceCatalogFulfillmentPresentation;
use local_subscriptions\commerce\catalog\validation\CommerceCatalogValidator;
use local_subscriptions\commerce\catalog\validation\CommerceCatalogActivationValidator;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\commerce\catalog\readmodel\CommerceCatalogReadRepository;
use local_subscriptions\commerce\statistics\CommerceStatisticsFilter;
use local_subscriptions\commerce\statistics\CommerceStatisticsPeriod;
use local_subscriptions\commerce\statistics\CommerceStatisticsRepository;
use local_subscriptions\commerce\statistics\CommerceStatisticsSeriesService;
use local_subscriptions\crm\commerce\presentation\CommerceDesignSystemRenderer;
use local_subscriptions\crm\commerce\presentation\CommerceProductPageHeaderRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\commerce\statistics\CommerceStatisticsChartRenderer;
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
$statisticsperiodkey = optional_param('statsperiod', '90', PARAM_ALPHANUMEXT);
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
$pageurl = CommerceCatalogLinkGenerator::view_url($product);
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $product->get_name(), 'local-subscriptions-commerce-product-view-page');

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('commerce_catalog_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/products/index.php')],
    ['label' => $product->get_name(), 'url' => null],
]);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::PRODUCTS);

$metahtml = CommerceCatalogPresentation::badge('type', $product->get_type()) . ' ' .
    html_writer::tag('code', s($product->get_sku()), ['class' => 'd-inline-block ms-2']);
$actions = [[
    'label' => get_string('commerce_back_to_products', 'local_subscriptions'),
    'url' => new moodle_url('/local/subscriptions/admin/commerce/products/index.php'),
    'class' => 'btn btn-outline-secondary',
]];
$compatibilityeditor = new CommerceCatalogCompatibilityEditor();
if ($product->get_origin() === 'native') {
    $actions[] = [
        'label' => get_string('edit'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/products/edit.php', ['sku' => $product->get_sku()]),
        'class' => 'btn btn-primary',
    ];
    $actions[] = [
        'label' => get_string('commerce_product_assets_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/products/assets.php', ['sku' => $product->get_sku()]),
        'class' => 'btn btn-outline-primary',
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
    ];
} else {
    $legacyediturl = $compatibilityeditor->legacy_edit_url($product->get_origin(), (int)$product->get_id());
    if ($legacyediturl !== null) {
        $actions[] = [
            'label' => get_string('commerce_edit_in_source', 'local_subscriptions'),
            'url' => $legacyediturl,
            'class' => 'btn btn-outline-primary',
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
    ];
}
echo CommerceProductPageHeaderRenderer::render(
    $product->get_name(), $metahtml, CommerceDesignSystemRenderer::action_bar($actions, 'justify-content-end'),
    get_string('commerce_catalog_product_eyebrow', 'local_subscriptions')
);

echo CommerceDesignSystemRenderer::metrics([
    ['label' => get_string('commerce_prices', 'local_subscriptions'), 'value' => count($product->get_prices())],
    ['label' => get_string('commerce_translations', 'local_subscriptions'), 'value' => count($details->get_translations())],
    ['label' => get_string('commerce_components', 'local_subscriptions'), 'value' => count($details->get_components())],
]);

echo html_writer::start_div('row g-4');
echo html_writer::start_div('col-xl-8');
echo html_writer::start_div('card card-body mb-4');
echo html_writer::tag('h3', get_string('commerce_product_summary', 'local_subscriptions'), ['class' => 'h5']);
echo html_writer::tag('p', format_text($product->get_description(), FORMAT_PLAIN), ['class' => 'mb-3 text-muted']);
echo html_writer::div(
    CommerceCatalogPresentation::badge('editorial', $product->get_editorial_status()) . ' ' .
    CommerceCatalogPresentation::badge('visibility', $product->get_visibility()) . ' ' .
    CommerceCatalogPresentation::badge('availability', $product->get_availability()) . ' ' .
    CommerceCatalogPresentation::badge('technical', $product->get_technical_state())
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
if ($coverurl !== null) {
    echo html_writer::start_div('card card-body mb-4');
    echo html_writer::tag('h3', get_string('commerce_cover_image', 'local_subscriptions'), ['class' => 'h5']);
    echo html_writer::empty_tag('img', [
        'src' => $coverurl,
        'alt' => get_string('commerce_cover_image', 'local_subscriptions'),
        'class' => 'img-fluid rounded border',
        'style' => 'width:240px;max-width:100%;height:auto',
    ]);
    echo html_writer::end_div();
}


if ($details->get_translations() !== []) {
    echo html_writer::start_div('card card-body mb-4');
    echo html_writer::tag('h3', get_string('commerce_product_translations_title', 'local_subscriptions'), ['class' => 'h5']);
    $languageflags = [
        'fr' => '🇫🇷',
        'en' => '🇬🇧',
        'ru' => '🇷🇺',
    ];
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
    echo html_writer::start_div('card card-body mb-4');
    echo html_writer::tag('h3', get_string('commerce_components', 'local_subscriptions'), ['class' => 'h5']);
    foreach ($details->get_components() as $component) {
        echo html_writer::div(
            html_writer::tag('strong', format_string($component['name'])) . ' × ' . (int)$component['quantity'] . ' ' .
            CommerceCatalogPresentation::badge('type', $component['type']),
            'border rounded p-3 mb-2'
        );
    }
    echo html_writer::end_div();
}

echo html_writer::end_div();
echo html_writer::start_div('col-xl-4');
echo html_writer::start_div('card card-body mb-4');
echo html_writer::tag('h3', get_string('commerce_prices', 'local_subscriptions'), ['class' => 'h5']);
echo CommerceCatalogPresentation::prices($product->get_prices());
echo html_writer::end_div();

echo html_writer::start_div('card card-body mb-4');
echo html_writer::tag('h3', get_string('commerce_catalog_availability', 'local_subscriptions'), ['class' => 'h5']);
$from = $product->get_available_from(); $until = $product->get_available_until();
echo html_writer::div(get_string('commerce_catalog_available_from', 'local_subscriptions') . ': ' . ($from ? userdate($from) : get_string('none')), 'mb-2');
echo html_writer::div(get_string('commerce_catalog_available_until', 'local_subscriptions') . ': ' . ($until ? userdate($until) : get_string('none')));
echo html_writer::end_div();

if ($product->get_type() === 'digital_download') {
    $nativefiles = new CommerceCatalogDigitalFileManager(context_system::instance());
    $desktopfile = $nativefiles->get_file(
        (int)$product->get_id(),
        CommerceCatalogDigitalFileManager::ROLE_DESKTOP
    );
    $mobilefile = $nativefiles->get_file(
        (int)$product->get_id(),
        CommerceCatalogDigitalFileManager::ROLE_MOBILE
    );

    if ($desktopfile || $mobilefile || $legacydigital) {
        echo html_writer::start_div('card card-body mb-4');
        echo html_writer::tag('h3', get_string('commerce_digital_files', 'local_subscriptions'), ['class' => 'h5']);

        $files = [
            'main' => [$desktopfile, $legacydigital?->filename ?? '', 'commerce_desktop_file'],
            'mobile' => [$mobilefile, $legacydigital?->mobile_filename ?? '', 'commerce_mobile_file'],
        ];
        foreach ($files as $version => [$nativefile, $legacyfilename, $labelkey]) {
            $filename = $nativefile instanceof stored_file
                ? $nativefile->get_filename()
                : (string)$legacyfilename;
            if ($filename === '') {
                continue;
            }

            $downloadurl = new moodle_url('/local/subscriptions/admin/commerce/products/download.php', [
                'sku' => $product->get_sku(),
                'version' => $version,
            ]);
            echo html_writer::div(
                get_string($labelkey, 'local_subscriptions') . ': ' .
                html_writer::link($downloadurl, s($filename)),
                'mb-2'
            );
        }

        echo html_writer::end_div();
    }
}

if ($details->get_legacy_references() !== []) {
    echo html_writer::start_div('card card-body mb-4');
    echo html_writer::tag('h3', get_string('commerce_catalog_compatibility', 'local_subscriptions'), ['class' => 'h5']);
    foreach ($details->get_legacy_references() as $reference) {
        echo html_writer::div(
            s($reference['family']) . ' · ' . html_writer::tag('code', s($reference['table'] . '#' . $reference['id'])),
            'small mb-2'
        );
    }
    echo html_writer::end_div();
}

echo html_writer::end_div();
echo html_writer::end_div();

// Product performance spans the complete workspace width.
$statisticsperiodoptions = [
    '30' => get_string('commerce_statistics_period_30_days', 'local_subscriptions'),
    '90' => get_string('commerce_statistics_period_90_days', 'local_subscriptions'),
    '180' => get_string('commerce_statistics_period_180_days', 'local_subscriptions'),
    '365' => get_string('commerce_statistics_period_365_days', 'local_subscriptions'),
    'all' => get_string('commerce_statistics_period_all_time', 'local_subscriptions'),
];
if (!array_key_exists($statisticsperiodkey, $statisticsperiodoptions)) {
    $statisticsperiodkey = '90';
}
$statisticsperiod = $statisticsperiodkey === 'all'
    ? CommerceStatisticsPeriod::custom(0, time() + 1)
    : CommerceStatisticsPeriod::last_days((int)$statisticsperiodkey);
$statisticsrepository = new CommerceStatisticsRepository($DB);
$productreferences = [$product->get_sku()];
foreach ($details->get_legacy_references() as $reference) {
    if ($reference['table'] === 'subscription_plan') {
        $productreferences[] = 'subscription-plan:' . (int)$reference['id'];
    } else if ($reference['table'] === 'subscription_digital_product') {
        $productreferences[] = 'digital-product:' . (int)$reference['id'];
        $legacyrecord = $DB->get_record(
            'subscription_digital_product',
            ['id' => (int)$reference['id']],
            'slug',
            IGNORE_MISSING
        );
        if ($legacyrecord && trim((string)$legacyrecord->slug) !== '') {
            $productreferences[] = 'digital-product:' . trim((string)$legacyrecord->slug);
        }
    }
}
$productreferences = array_values(array_unique($productreferences));
$availablecurrencies = [];
foreach ($statisticsrepository->product_statistics_for_references($statisticsperiod, $productreferences) as $row) {
    $availablecurrencies[$row->currency] = $row->currency;
}
ksort($availablecurrencies);
if ($statisticscurrency !== '' && !isset($availablecurrencies[$statisticscurrency])) {
    $statisticscurrency = '';
}
$productstatistics = $statisticsrepository->product_statistics_for_references(
    $statisticsperiod,
    $productreferences,
    100,
    $statisticscurrency !== '' ? $statisticscurrency : null
);
$productperformance = (new CommerceStatisticsSeriesService($statisticsrepository))
    ->product($statisticsperiod, $productreferences, $statisticscurrency !== '' ? $statisticscurrency : null);
$productorderseries = $statisticsrepository->product_order_series_for_references(
    $statisticsperiod,
    $productreferences,
    $statisticscurrency !== '' ? $statisticscurrency : null
);
$productfailedpayments = $statisticsrepository->product_failed_payments_for_references(
    $statisticsperiod,
    $productreferences,
    $statisticscurrency !== '' ? $statisticscurrency : null
);

$formatmoney = static function(int $minor, string $currency): string {
    $major = $minor / 100;
    if (class_exists('NumberFormatter')) {
        $formatter = new \NumberFormatter(current_language(), \NumberFormatter::CURRENCY);
        $formatted = $formatter->formatCurrency($major, $currency);
        if ($formatted !== false) {
            return $formatted;
        }
    }
    return format_float($major, 2) . ' ' . $currency;
};

echo html_writer::start_div('card card-body mb-4');
echo html_writer::tag('h3', get_string('commerce_product_statistics_title', 'local_subscriptions'), ['class' => 'h5']);

$filterurl = CommerceCatalogLinkGenerator::view_url($product);
echo html_writer::start_tag('form', ['method' => 'get', 'action' => $filterurl->out_omit_querystring(), 'class' => 'row g-3 align-items-end mb-4']);
foreach ($filterurl->params() as $name => $value) {
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $name, 'value' => $value]);
}
$currencyoptions = ['' => get_string('commerce_statistics_all_currencies', 'local_subscriptions')] + $availablecurrencies;
echo html_writer::div(
    html_writer::tag('label', get_string('currency'), ['for' => 'statscurrency', 'class' => 'form-label']) .
    html_writer::select($currencyoptions, 'statscurrency', $statisticscurrency, false, ['id' => 'statscurrency', 'class' => 'form-select']),
    'col-md-4'
);
echo html_writer::div(
    html_writer::tag('label', get_string('commerce_statistics_period_label', 'local_subscriptions'), ['for' => 'statsperiod', 'class' => 'form-label']) .
    html_writer::select($statisticsperiodoptions, 'statsperiod', $statisticsperiodkey, false, ['id' => 'statsperiod', 'class' => 'form-select']),
    'col-md-4'
);
echo html_writer::div(
    html_writer::tag('label', get_string('commerce_statistics_chart_mode', 'local_subscriptions'), ['for' => 'statschartmode', 'class' => 'form-label']) .
    html_writer::select([
        'instant' => get_string('commerce_statistics_chart_mode_instant', 'local_subscriptions'),
        'cumulative' => get_string('commerce_statistics_chart_mode_cumulative', 'local_subscriptions'),
    ], 'statschartmode', $statisticschartmode, false, ['id' => 'statschartmode', 'class' => 'form-select']),
    'col-md-4'
);
echo html_writer::div(
    html_writer::tag('button', get_string('filter'), ['type' => 'submit', 'class' => 'btn btn-primary']),
    'col-12'
);
echo html_writer::end_tag('form');

if ($productstatistics === []) {
    echo html_writer::div(
        get_string('commerce_product_statistics_empty', 'local_subscriptions'),
        'alert alert-info mb-0'
    );
} else {
    $statisticsbycurrency = [];
    foreach ($productstatistics as $row) {
        $statisticsbycurrency[$row->currency] = $row;
    }
    foreach ($statisticsbycurrency as $currency => $row) {
        echo html_writer::start_tag('section', ['class' => 'border rounded p-3 mb-4']);
        echo html_writer::tag('h4', s($currency), ['class' => 'h5 mb-3']);
        echo CommerceDesignSystemRenderer::metrics([
            ['label' => get_string('commerce_statistics_product_orders', 'local_subscriptions'), 'value' => $row->orders],
            ['label' => get_string('commerce_statistics_product_paid_orders', 'local_subscriptions'), 'value' => $row->paidorders],
            ['label' => get_string('commerce_statistics_product_free_orders', 'local_subscriptions'), 'value' => $row->freeorders],
            ['label' => get_string('commerce_statistics_product_quantity', 'local_subscriptions'), 'value' => $row->quantity],
            ['label' => get_string('commerce_statistics_product_revenue', 'local_subscriptions'), 'value' => $formatmoney($row->revenueminor, $currency)],
            ['label' => get_string('commerce_statistics_product_failed_payments', 'local_subscriptions'), 'value' => $productfailedpayments[$currency] ?? 0],
        ]);
        $series = $productperformance->series_by_currency();
        if (isset($series[$currency])) {
            echo CommerceStatisticsChartRenderer::product(
                $OUTPUT,
                [$currency => $series[$currency]],
                isset($productorderseries[$currency]) ? [$currency => $productorderseries[$currency]] : [],
                $statisticschartmode === 'cumulative'
            );
        }
        echo html_writer::end_tag('section');
    }
}
echo html_writer::end_div();

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
