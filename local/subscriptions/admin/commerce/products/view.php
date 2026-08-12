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
$pageurl = CommerceCatalogLinkGenerator::view_url($product);
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $product->get_name(), 'local-subscriptions-commerce-product-view-page');
$PAGE->requires->css(new moodle_url('/local/subscriptions/styles/commerce_product_statistics.css'));
$PAGE->requires->css(new moodle_url('/local/subscriptions/styles/commerce_statistics_breakdowns.css'));

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
echo html_writer::div(html_writer::tag('button',get_string('filter'),['type'=>'submit','class'=>'btn btn-primary w-100']),'form-group');
$exportparams=['sku'=>$product->get_sku(),'statsperiod'=>$statisticsperiodkey,'statsfrom'=>$statisticsfrom,'statsuntil'=>$statisticsuntil,'statscurrency'=>$statisticscurrency];
echo html_writer::div(html_writer::link(new moodle_url('/local/subscriptions/admin/commerce/products/statistics_export.php',$exportparams),get_string('commerce_m51_export_excel','local_subscriptions'),['class'=>'btn btn-outline-success w-100']),'form-group');
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
