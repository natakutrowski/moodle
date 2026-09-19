<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\catalog\currency\CommerceCurrencyRegistry;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\commerce\currency\CommerceCurrencyAmount;
use local_subscriptions\commerce\currency\CommerceEcbFxRateSource;
use local_subscriptions\commerce\currency\CommerceFxDiagnosticsService;
use local_subscriptions\commerce\currency\CommerceFxPriceSuggestionService;
use local_subscriptions\commerce\currency\CommerceFxRefreshCoordinator;
use local_subscriptions\commerce\currency\CommerceOpenAiFxRateSource;
use local_subscriptions\commerce\currency\CommerceFxRateBook;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\currency\CommerceCurrencyLabelFormatter;
use local_subscriptions\currency\Currency;
use local_subscriptions\currency\CurrencyFormatter;

$context = AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
$pageurl = new moodle_url('/local/subscriptions/admin/commerce/configuration/currencies.php');
$title = get_string('commerce_fx_title', 'local_subscriptions');
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $title, 'local-subscriptions-commerce-fx-page');

$registry = new CommerceCurrencyRegistry();
$ratebook = new CommerceFxRateBook($registry);
$catalogfactory = new CommerceCatalogFactory($DB);
$fxdiagnostics = new CommerceFxDiagnosticsService(
    $registry,
    $ratebook,
    $catalogfactory->product_manager()
);
$enabled = $registry->enabled();
$previewkey = 'commerce_fx_refresh_preview';
$refreshpreview = $SESSION->{$previewkey} ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    $action = optional_param('action', '', PARAM_ALPHA);
    if ($action === 'refresh') {
        try {
            $base = $registry->require_enabled(strtoupper(required_param('basecurrency', PARAM_ALPHA)));
            $targets = array_values(array_filter(
                $enabled,
                static fn(string $code): bool => $code !== $base
            ));
            $batch = (new CommerceFxRefreshCoordinator())->refresh($base, $targets);
            $SESSION->{$previewkey} = [
                'basecurrency'=>$batch->basecurrency,
                'entries'=>$batch->entries,
                'unavailable'=>$batch->unavailable,
                'fetchedat'=>time(),
            ];
            redirect($pageurl, get_string('commerce_fx_refresh_ready', 'local_subscriptions'));
        } catch (\Throwable $e) {
            unset($SESSION->{$previewkey});
            redirect($pageurl, get_string('commerce_fx_refresh_failed', 'local_subscriptions', $e->getMessage()), null, \core\output\notification::NOTIFY_ERROR);
        }
    } else if ($action === 'openai_refresh') {
        $preview = $SESSION->{$previewkey} ?? null;
        if (!is_array($preview) || empty($preview['basecurrency']) || empty($preview['unavailable'])) {
            redirect($pageurl, get_string('commerce_fx_openai_nothing_to_refresh', 'local_subscriptions'), null, \core\output\notification::NOTIFY_ERROR);
        }
        try {
            $source = new CommerceOpenAiFxRateSource();
            if (!$source->available()) { throw new \runtime_exception(get_string('commerce_fx_openai_unavailable', 'local_subscriptions')); }
            $result = $source->refresh((string)$preview['basecurrency'], (array)$preview['unavailable']);
            $entries = (array)($preview['entries'] ?? []);
            foreach ($result->rates as $code => $rate) {
                $entries[$code] = ['rate'=>(string)$rate,'source'=>'OpenAI','referencedate'=>$result->referencedate];
            }
            $preview['entries']=$entries;
            $preview['unavailable']=$result->unavailable;
            $preview['fetchedat']=time();
            $SESSION->{$previewkey}=$preview;
            redirect($pageurl, get_string('commerce_fx_openai_refresh_ready', 'local_subscriptions'));
        } catch (\Throwable $e) {
            redirect($pageurl, get_string('commerce_fx_openai_refresh_failed', 'local_subscriptions', $e->getMessage()), null, \core\output\notification::NOTIFY_ERROR);
        }
    } else if ($action === 'applyrefresh') {
        $preview = $SESSION->{$previewkey} ?? null;
        if (!is_array($preview) || empty($preview['entries']) || empty($preview['basecurrency'])) {
            redirect($pageurl, get_string('commerce_fx_refresh_missing', 'local_subscriptions'), null, \core\output\notification::NOTIFY_ERROR);
        }
        $selected = array_map(
            'strtoupper',
            optional_param_array('applycurrencies', [], PARAM_ALPHA)
        );
        $entries = array_filter(
            (array)($preview['entries'] ?? []),
            static fn(string $code): bool => in_array(strtoupper($code), $selected, true),
            ARRAY_FILTER_USE_KEY
        );
        if ($entries === []) {
            redirect(
                $pageurl,
                get_string('commerce_fx_apply_selection_required', 'local_subscriptions'),
                null,
                \core\output\notification::NOTIFY_ERROR
            );
        }
        $ratebook->apply_refresh_entries(
            (string)$preview['basecurrency'],
            $entries,
            (int)($preview['fetchedat'] ?? time())
        );
        unset($SESSION->{$previewkey});
        redirect($pageurl, get_string('commerce_fx_refresh_applied', 'local_subscriptions'), null, \core\output\notification::NOTIFY_SUCCESS);
    } else if ($action === 'discardrefresh') {
        unset($SESSION->{$previewkey});
        redirect($pageurl, get_string('commerce_fx_refresh_discarded', 'local_subscriptions'));
    } else if ($action === 'save') {
        $base = strtoupper(required_param('basecurrency', PARAM_ALPHA));
        $rates = [];
        foreach ($enabled as $code) {
            if ($code === $base) {
                continue;
            }
            $rates[$code] = optional_param('rate_' . strtolower($code), '', PARAM_RAW_TRIMMED);
        }
        $source = optional_param('source', 'manual', PARAM_RAW_TRIMMED);
        $ratebook->save($base, $rates, $source);
        redirect($pageurl, get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

$base = $ratebook->base_currency();
$rates = $ratebook->rates();
$diagnostics = $fxdiagnostics->snapshot();
$suggestion = null;
$suggestionerror = '';
if (optional_param('suggest', 0, PARAM_BOOL)) {
    try {
        $sourceamount = required_param('sourceamount', PARAM_RAW_TRIMMED);
        $target = strtoupper(required_param('targetcurrency', PARAM_ALPHA));
        $rounding = optional_param('rounding', 'none', PARAM_ALPHA);
        $sourceamountminor = CommerceCurrencyAmount::from_major_input($sourceamount, $base)->get_amount_minor();
        $suggestion = (new CommerceFxPriceSuggestionService($ratebook))->suggest(
            $sourceamountminor,
            $base,
            $target,
            $rounding
        );
        $suggestion['target'] = $target;
        $suggestion['sourceamountminor'] = $sourceamountminor;
    } catch (\Throwable $e) {
        $suggestionerror = $e->getMessage();
    }
}

$baseoptions = [];
foreach ($enabled as $code) {
    $baseoptions[$code] = CommerceCurrencyLabelFormatter::format($code);
}

$targetoptions = [];
foreach ($enabled as $code) {
    if ($code !== $base) {
        $targetoptions[$code] = CommerceCurrencyLabelFormatter::format($code);
    }
}

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => get_string('commerce_configuration_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/configuration/index.php')],
    ['label' => get_string('commerce_configuration_localisation_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/configuration/section.php', ['section' => 'localisation'])],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render($title, get_string('commerce_fx_description', 'local_subscriptions'), HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::CONFIGURATION, $context);

echo html_writer::div(
    html_writer::link(
        new moodle_url(
            '/local/subscriptions/admin/commerce/configuration/section.php',
            ['section' => 'localisation']
        ),
        '← ' . get_string('commerce_fx_back_to_localisation', 'local_subscriptions'),
        ['class' => 'btn btn-outline-secondary btn-sm']
    ),
    'mb-3'
);

echo html_writer::div(get_string('commerce_fx_non_authoritative_notice', 'local_subscriptions'), 'alert alert-info');

echo html_writer::div(
    html_writer::link(
        new moodle_url(
            '/local/subscriptions/admin/commerce/configuration/currency_prices.php'
        ),
        '💱 ' . get_string('commerce_fx_bulk_open', 'local_subscriptions'),
        ['class' => 'btn btn-outline-primary']
    )
    . html_writer::span(
        get_string('commerce_fx_bulk_open_help', 'local_subscriptions'),
        'text-muted ms-3'
    ),
    'mb-4'
);


echo html_writer::start_div('card mb-4');
echo html_writer::start_div('card-body');
echo html_writer::start_div('d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3');
echo html_writer::div(
    html_writer::tag(
        'h2',
        '🩺 ' . get_string('commerce_fx_diagnostics_title', 'local_subscriptions'),
        ['class' => 'h5 mb-1']
    )
    . html_writer::tag(
        'p',
        get_string('commerce_fx_diagnostics_help', 'local_subscriptions'),
        ['class' => 'text-muted mb-0']
    )
);
echo html_writer::span(
    get_string(
        $diagnostics['healthy']
            ? 'commerce_fx_diagnostics_ready'
            : 'commerce_fx_diagnostics_attention',
        'local_subscriptions'
    ),
    'badge rounded-pill '
        . ($diagnostics['healthy'] ? 'text-bg-success' : 'text-bg-warning')
);
echo html_writer::end_div();

$diagtable = new html_table();
$diagtable->attributes['class'] = 'table table-sm align-middle mb-3';
$diagtable->data = [
    [
        get_string('commerce_fx_diagnostics_base', 'local_subscriptions'),
        CommerceCurrencyLabelFormatter::format($diagnostics['basecurrency']),
    ],
    [
        get_string('commerce_fx_diagnostics_enabled', 'local_subscriptions'),
        (string)count($diagnostics['enabled']),
    ],
    [
        get_string('commerce_fx_diagnostics_rates', 'local_subscriptions'),
        (string)$diagnostics['ratecount'],
    ],
    [
        get_string('commerce_fx_diagnostics_active_products', 'local_subscriptions'),
        (string)$diagnostics['activeproducts'],
    ],
    [
        get_string('commerce_fx_diagnostics_automatic', 'local_subscriptions'),
        get_string('commerce_fx_diagnostics_manual_only', 'local_subscriptions'),
    ],
];
echo html_writer::table($diagtable);

if ($diagnostics['sourcecounts'] !== []) {
    $sourceparts = [];
    foreach ($diagnostics['sourcecounts'] as $source => $count) {
        $sourceparts[] = s($source) . ' × ' . (int)$count;
    }
    echo html_writer::div(
        get_string(
            'commerce_fx_diagnostics_sources',
            'local_subscriptions',
            implode(' · ', $sourceparts)
        ),
        'small text-muted mb-2'
    );
}

if ($diagnostics['missingrates'] !== []) {
    $labels = array_map(
        static fn(string $code): string =>
            CommerceCurrencyLabelFormatter::format($code),
        $diagnostics['missingrates']
    );
    echo html_writer::div(
        get_string(
            'commerce_fx_diagnostics_missing_rates',
            'local_subscriptions',
            implode(', ', $labels)
        ),
        'alert alert-warning py-2'
    );
}

if ($diagnostics['stalerates'] !== []) {
    $labels = array_map(
        static fn(string $code): string =>
            CommerceCurrencyLabelFormatter::format($code),
        $diagnostics['stalerates']
    );
    echo html_writer::div(
        get_string(
            'commerce_fx_diagnostics_stale_rates',
            'local_subscriptions',
            (object)[
                'days' => CommerceFxDiagnosticsService::DEFAULT_STALE_DAYS,
                'currencies' => implode(', ', $labels),
            ]
        ),
        'alert alert-secondary py-2'
    );
}

if ($diagnostics['productsmissingbase'] !== []) {
    $items = [];
    foreach (array_slice($diagnostics['productsmissingbase'], 0, 10) as $missingproduct) {
        $items[] = s((string)$missingproduct['sku']);
    }
    $suffix = count($diagnostics['productsmissingbase']) > 10
        ? ' …'
        : '';
    echo html_writer::div(
        get_string(
            'commerce_fx_diagnostics_missing_base_prices',
            'local_subscriptions',
            (object)[
                'count' => count($diagnostics['productsmissingbase']),
                'products' => implode(', ', $items) . $suffix,
            ]
        ),
        'alert alert-warning py-2 mb-0'
    );
}

echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('card mb-4 border-primary');
echo html_writer::start_div('card-body');
echo html_writer::tag('h2', get_string('commerce_fx_refresh_title_d3', 'local_subscriptions'), ['class'=>'h5 mb-1']);
echo html_writer::tag('p', get_string('commerce_fx_refresh_help_d3', 'local_subscriptions'), ['class'=>'text-muted']);
echo html_writer::tag('div', get_string('commerce_fx_refresh_source_chain', 'local_subscriptions'), ['class'=>'small text-muted mb-3']);

$refreshtargets = array_values(array_filter($enabled, static fn(string $code): bool => $code !== $base));
echo html_writer::start_tag('form', ['method'=>'post','action'=>$pageurl->out(false),'class'=>'m-0']);
echo html_writer::empty_tag('input', ['type'=>'hidden','name'=>'sesskey','value'=>sesskey()]);
echo html_writer::empty_tag('input', ['type'=>'hidden','name'=>'action','value'=>'refresh']);
echo html_writer::empty_tag('input', ['type'=>'hidden','name'=>'basecurrency','value'=>$base]);
echo html_writer::tag(
    'button',
    get_string('commerce_fx_refresh_free_button_all','local_subscriptions'),
    ['type'=>'submit','class'=>'btn btn-primary']
);
echo html_writer::end_tag('form');

if (is_array($refreshpreview) && (string)($refreshpreview['basecurrency'] ?? '') === $base) {
    echo html_writer::start_div('mt-4');
    echo html_writer::start_div('d-flex justify-content-between align-items-center mb-2');
    echo html_writer::tag(
        'h3',
        get_string('commerce_fx_refresh_preview_title','local_subscriptions'),
        ['class'=>'h6 mb-0']
    );
    echo html_writer::div(
        html_writer::tag(
            'button',
            get_string('commerce_select_all','local_subscriptions'),
            ['type'=>'button','class'=>'btn btn-link btn-sm p-0 me-3','data-fx-apply-select-all'=>'1']
        )
        . html_writer::tag(
            'button',
            get_string('commerce_deselect_all','local_subscriptions'),
            ['type'=>'button','class'=>'btn btn-link btn-sm p-0','data-fx-apply-deselect-all'=>'1']
        )
    );
    echo html_writer::end_div();
    $previewtable=new html_table();
    $previewtable->attributes['class']='table table-sm align-middle';
    $previewtable->head=[
        get_string('commerce_fx_apply_column','local_subscriptions'),
        get_string('currency'),
        get_string('commerce_fx_current_rate','local_subscriptions'),
        get_string('commerce_fx_proposed_rate','local_subscriptions'),
        get_string('commerce_fx_variation','local_subscriptions'),
        get_string('commerce_fx_source','local_subscriptions')
    ];
    foreach ((array)($refreshpreview['entries'] ?? []) as $code=>$entry) {
        $proposed=(string)($entry['rate'] ?? '');
        $current=$rates[$code]['rate'] ?? null;
        $variation='—';
        if ($current !== null && (float)$current > 0 && (float)$proposed > 0) {
            $variation=number_format((((float)$proposed/(float)$current)-1)*100,2,',',' ') . ' %';
        }
        $source=trim((string)($entry['source'] ?? ''));
        $refdate=trim((string)($entry['referencedate'] ?? ''));
        $previewtable->data[]=[
            html_writer::checkbox(
                'applycurrencies[]',
                (string)$code,
                true,
                '',
                [
                    'class'=>'form-check-input',
                    'form'=>'commerce-fx-apply-form',
                ]
            ),
            CommerceCurrencyLabelFormatter::format((string)$code),
            $current ?? '—',
            html_writer::tag('strong',s($proposed)),
            $variation,
            s($source . ($refdate !== '' ? ' · ' . $refdate : ''))
        ];
    }
    echo html_writer::table($previewtable);
    $unavailable=(array)($refreshpreview['unavailable'] ?? []);
    if ($unavailable !== []) {
        $labels=array_map(static fn(string $code): string => CommerceCurrencyLabelFormatter::format($code),$unavailable);
        echo html_writer::div(get_string('commerce_fx_refresh_still_unavailable','local_subscriptions',implode(', ',$labels)),'alert alert-warning py-2');
        $openaisource=new CommerceOpenAiFxRateSource();
        if ($openaisource->available()) {
            echo html_writer::start_tag('form',['method'=>'post','action'=>$pageurl->out(false),'class'=>'mb-3']);
            echo html_writer::empty_tag('input',['type'=>'hidden','name'=>'sesskey','value'=>sesskey()]);
            echo html_writer::empty_tag('input',['type'=>'hidden','name'=>'action','value'=>'openai_refresh']);
            echo html_writer::tag('button',get_string('commerce_fx_openai_refresh_button','local_subscriptions'),['type'=>'submit','class'=>'btn btn-outline-primary']);
            echo html_writer::tag('div',get_string('commerce_fx_openai_refresh_warning','local_subscriptions'),['class'=>'small text-muted mt-1']);
            echo html_writer::end_tag('form');
        } else {
            echo html_writer::div(get_string('commerce_fx_openai_unavailable','local_subscriptions'),'small text-muted mb-3');
        }
    }
    if (!empty($refreshpreview['entries'])) {
        echo html_writer::start_div('d-flex gap-2 mt-4');

        echo html_writer::start_tag('form',[
            'method'=>'post',
            'action'=>$pageurl->out(false),
            'class'=>'m-0',
            'id'=>'commerce-fx-apply-form',
        ]);
        echo html_writer::empty_tag('input',['type'=>'hidden','name'=>'sesskey','value'=>sesskey()]);
        echo html_writer::empty_tag('input',['type'=>'hidden','name'=>'action','value'=>'applyrefresh']);
        echo html_writer::tag(
            'button',
            get_string('commerce_fx_apply_refresh','local_subscriptions'),
            ['type'=>'submit','class'=>'btn btn-success']
        );
        echo html_writer::end_tag('form');

        echo html_writer::start_tag('form',['method'=>'post','action'=>$pageurl->out(false),'class'=>'m-0']);
        echo html_writer::empty_tag('input',['type'=>'hidden','name'=>'sesskey','value'=>sesskey()]);
        echo html_writer::empty_tag('input',['type'=>'hidden','name'=>'action','value'=>'discardrefresh']);
        echo html_writer::tag(
            'button',
            get_string('commerce_fx_discard_refresh','local_subscriptions'),
            ['type'=>'submit','class'=>'btn btn-outline-secondary']
        );
        echo html_writer::end_tag('form');

        echo html_writer::end_div();
    }
    echo html_writer::end_div();
}
echo html_writer::end_div();
echo html_writer::end_div();

$PAGE->requires->js_init_code(<<<JS
(function() {
    var boxes=function(){
        return Array.prototype.slice.call(
            document.querySelectorAll('input[name="applycurrencies[]"]')
        );
    };
    var all=document.querySelector('[data-fx-apply-select-all="1"]');
    var none=document.querySelector('[data-fx-apply-deselect-all="1"]');
    if (all) {
        all.addEventListener('click',function(){
            boxes().forEach(function(box){box.checked=true;});
        });
    }
    if (none) {
        none.addEventListener('click',function(){
            boxes().forEach(function(box){box.checked=false;});
        });
    }
})();
JS);

echo html_writer::start_tag('form', ['method' => 'post', 'action' => $pageurl->out(false)]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'save']);
echo html_writer::start_div('row g-3');
echo html_writer::div(
    html_writer::tag('label', get_string('commerce_fx_base_currency', 'local_subscriptions'), ['for' => 'fx-base', 'class' => 'form-label fw-semibold'])
    . html_writer::select($baseoptions, 'basecurrency', $base, false, ['id' => 'fx-base', 'class' => 'form-select']),
    'col-md-4'
);
echo html_writer::div(
    html_writer::tag('label', get_string('commerce_fx_source', 'local_subscriptions'), ['for' => 'fx-source', 'class' => 'form-label fw-semibold'])
    . html_writer::empty_tag('input', ['type' => 'text', 'name' => 'source', 'id' => 'fx-source', 'value' => 'manual', 'class' => 'form-control']),
    'col-md-4'
);
echo html_writer::end_div();

echo html_writer::start_div('table-responsive mt-4');
$table = new html_table();
$table->attributes['class'] = 'table align-middle';
$table->head = [
    get_string('currency'),
    get_string('commerce_fx_rate', 'local_subscriptions'),
    get_string('commerce_fx_source', 'local_subscriptions'),
    get_string('commerce_fx_reference_date', 'local_subscriptions'),
    get_string('commerce_fx_last_update', 'local_subscriptions'),
];
foreach ($enabled as $code) {
    if ($code === $base) {
        continue;
    }
    $entry = $rates[$code] ?? null;
    $table->data[] = [
        CommerceCurrencyLabelFormatter::format($code),
        html_writer::empty_tag('input', [
            'type' => 'text',
            'inputmode' => 'decimal',
            'name' => 'rate_' . strtolower($code),
            'value' => $entry['rate'] ?? '',
            'class' => 'form-control form-control-sm',
            'placeholder' => '1.000000',
        ]),
        $entry !== null ? s((string)$entry['source']) : '—',
        $entry !== null && (string)($entry['referencedate'] ?? '') !== ''
            ? s((string)$entry['referencedate'])
            : '—',
        $entry && $entry['updatedat'] > 0 ? userdate($entry['updatedat']) : '—',
    ];
}
echo html_writer::table($table);
echo html_writer::end_div();
echo html_writer::tag('p', get_string('commerce_fx_rate_definition', 'local_subscriptions', $base), ['class' => 'form-text']);
echo html_writer::tag('button', get_string('savechanges'), ['type' => 'submit', 'class' => 'btn btn-primary']);
echo html_writer::end_tag('form');
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('card mb-4');
echo html_writer::start_div('card-body');
echo html_writer::tag('h2', get_string('commerce_fx_suggestion_title', 'local_subscriptions'), ['class' => 'h5']);
echo html_writer::tag('p', get_string('commerce_fx_suggestion_help', 'local_subscriptions'), ['class' => 'text-muted']);
if ($targetoptions === []) {
    echo html_writer::div(get_string('commerce_fx_need_two_currencies', 'local_subscriptions'), 'alert alert-warning');
} else {
    echo html_writer::start_tag('form', ['method' => 'get', 'action' => $pageurl->out(false)]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'suggest', 'value' => 1]);
    echo html_writer::start_div('row g-3 align-items-end');
    echo html_writer::div(
        html_writer::tag('label', get_string('commerce_fx_source_price', 'local_subscriptions', $base), ['for' => 'fx-sourceamount', 'class' => 'form-label fw-semibold'])
        . html_writer::empty_tag('input', ['type' => 'text', 'name' => 'sourceamount', 'id' => 'fx-sourceamount', 'value' => optional_param('sourceamount', '', PARAM_RAW_TRIMMED), 'class' => 'form-control', 'required' => 'required']),
        'col-md-3'
    );
    $selectedtarget = strtoupper(optional_param('targetcurrency', array_key_first($targetoptions), PARAM_ALPHA));
    echo html_writer::div(
        html_writer::tag('label', get_string('commerce_fx_target_currency', 'local_subscriptions'), ['for' => 'fx-target', 'class' => 'form-label fw-semibold'])
        . html_writer::select($targetoptions, 'targetcurrency', $selectedtarget, false, ['id' => 'fx-target', 'class' => 'form-select']),
        'col-md-3'
    );
    echo html_writer::div(
        html_writer::tag('label', get_string('commerce_fx_rounding', 'local_subscriptions'), ['for' => 'fx-rounding', 'class' => 'form-label fw-semibold'])
        . html_writer::select([
            'none' => get_string('commerce_fx_rounding_native', 'local_subscriptions'),
            'whole' => get_string('commerce_fx_rounding_whole', 'local_subscriptions'),
            'ending90' => get_string('commerce_fx_rounding_90', 'local_subscriptions'),
        ], 'rounding', optional_param('rounding', 'none', PARAM_ALPHA), false, ['id' => 'fx-rounding', 'class' => 'form-select']),
        'col-md-3'
    );
    echo html_writer::div(html_writer::tag('button', get_string('commerce_fx_calculate', 'local_subscriptions'), ['type' => 'submit', 'class' => 'btn btn-outline-primary w-100']), 'col-md-3');
    echo html_writer::end_div();
    echo html_writer::end_tag('form');
}
if ($suggestionerror !== '') {
    echo html_writer::div(s($suggestionerror), 'alert alert-danger mt-3');
} else if ($suggestion !== null) {
    $target = $suggestion['target'];
    echo html_writer::div(
        html_writer::tag('strong', get_string('commerce_fx_suggestion_result', 'local_subscriptions'))
        . html_writer::div(CurrencyFormatter::format_minor((int)$suggestion['suggestedminor'], $target), 'fs-4 fw-bold mt-2')
        . html_writer::div(get_string('commerce_fx_suggestion_rate_used', 'local_subscriptions', (object)['rate' => $suggestion['rate'], 'base' => $base, 'target' => $target]), 'text-muted'),
        'alert alert-light border mt-3'
    );
}
echo html_writer::end_div();
echo html_writer::end_div();

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
