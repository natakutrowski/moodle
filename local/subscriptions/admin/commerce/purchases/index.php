<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\catalog\navigation\CommerceCatalogLinkGenerator;
use local_subscriptions\commerce\catalog\readmodel\CommerceCatalogReadRepository;
use local_subscriptions\commerce\purchase\action\CommercePurchaseActionPolicy;
use local_subscriptions\commerce\purchase\action\CommercePurchaseAdminClosureService;
use local_subscriptions\commerce\purchase\action\CommercePurchaseActionServiceFactory;
use local_subscriptions\commerce\purchase\presentation\CommercePurchasePresentation;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseListFilter;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseReadRepository;
use local_subscriptions\commerce\purchase\status\CommerceCommercialStatus;
use local_subscriptions\commerce\mail\sales\CommerceSalesFollowupService;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\commerce\sales\CommerceSalesDashboardRepository;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\payment\Provider;

$context = AdminSecurity::require(Capabilities::VIEW_PAYMENTS);
$pageurl = new moodle_url('/local/subscriptions/admin/commerce/purchases/index.php');
$pagetitle = get_string('commerce_purchases_title', 'local_subscriptions');
CrmPageConfigurator::configure($PAGE, $context, $pageurl, $pagetitle, 'local-subscriptions-commerce-purchases-page');

$query = optional_param('q', '', PARAM_RAW_TRIMMED);
$type = optional_param('type', '', PARAM_ALPHANUMEXT);
$commercialstatus = optional_param('commercialstatus', '', PARAM_ALPHANUMEXT);
$paymentstatus = optional_param('paymentstatus', '', PARAM_ALPHANUMEXT);
$fulfillmentstatus = optional_param('fulfillmentstatus', '', PARAM_ALPHANUMEXT);
$provider = optional_param('provider', '', PARAM_ALPHANUMEXT);
$currency = strtoupper(optional_param('currency', '', PARAM_ALPHA));
$offerorigin = optional_param('offerorigin', '', PARAM_ALPHA);
$adminstate = optional_param('adminstate', 'open', PARAM_ALPHA);
$adminstate = in_array($adminstate, ['open', 'closed', 'all'], true) ? $adminstate : 'open';
$period = optional_param('period', '30', PARAM_ALPHANUMEXT);
$customfrom = optional_param('from', '', PARAM_RAW_TRIMMED);
$customto = optional_param('to', '', PARAM_RAW_TRIMMED);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 25, PARAM_INT);
$perpage = in_array($perpage, [25, 50, 100], true) ? $perpage : 25;
$sort = optional_param('sort', 'date', PARAM_ALPHA);
$direction = strtolower(optional_param('dir', 'desc', PARAM_ALPHA)) === 'asc' ? 'asc' : 'desc';

$availablecolumns = [
    'date', 'reference', 'customer', 'type', 'products', 'provider', 'amount',
    'payment', 'fulfillment', 'commercial',
];
$defaultcolumns = [
    'date', 'reference', 'customer', 'type', 'products', 'provider', 'amount',
    'commercial',
];
$requestedcolumns = optional_param_array('columns', [], PARAM_ALPHA);
$visiblecolumns = $requestedcolumns === []
    ? $defaultcolumns
    : array_values(array_intersect($availablecolumns, $requestedcolumns));
if ($visiblecolumns === []) {
    $visiblecolumns = $defaultcolumns;
}

/** @return int|null */
$parsedate = static function(string $value, bool $endofday = false): ?int {
    $value = trim($value);
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $matches)) {
        return null;
    }
    try {
        $timezone = \core_date::get_user_timezone_object();
        $date = new \DateTimeImmutable($value, $timezone);
        $date = $endofday ? $date->setTime(23, 59, 59) : $date->setTime(0, 0, 0);
        return $date->getTimestamp();
    } catch (\Throwable) {
        return null;
    }
};

$now = time();
$datefrom = 0;
$dateto = 0;
if ($period === 'custom') {
    $datefrom = $parsedate($customfrom) ?? 0;
    $dateto = $parsedate($customto, true) ?? 0;
} elseif ($period === 'today') {
    $datefrom = usergetmidnight($now);
    $dateto = $now;
} elseif ($period !== 'all') {
    $days = (int)$period;
    $days = in_array($days, [7, 30, 90, 365], true) ? $days : 30;
    $datefrom = $now - ($days * DAYSECS);
    $dateto = $now;
    $period = (string)$days;
}

$filter = new CommercePurchaseListFilter(
    $query,
    $type,
    $commercialstatus,
    $paymentstatus,
    $fulfillmentstatus,
    $provider,
    $currency,
    $datefrom,
    $dateto,
    $sort,
    $direction,
    $offerorigin,
    $adminstate
);
$repository = new CommercePurchaseReadRepository($DB);
$result = $repository->search($filter, $page, $perpage);
$kpis = (new CommerceSalesDashboardRepository($repository))->snapshot($filter);

$actionservice = CommercePurchaseActionServiceFactory::create();
$adminclosureservice = new CommercePurchaseAdminClosureService($DB);
$closedwithoutfulfillment = array_fill_keys(
    $actionservice->closed_without_fulfillment_ids(
        array_map(static fn($purchase): int => (int)$purchase->id, $result->purchases)
    ),
    true
);

$options = static fn(array $values, callable $label): array =>
    ['' => get_string('all')] + array_combine($values, array_map($label, $values));
$typeoptions = $options(
    ['subscription', 'digital', 'bundle'],
    static fn(string $value): string => CommercePurchasePresentation::type_label($value)
);
$commercialoptions = $options(
    CommerceCommercialStatus::all(),
    static fn(string $value): string => CommercePurchasePresentation::commercial_status_label($value)
);
$paymentoptions = $options(
    ['none', 'created', 'redirected', 'pending', 'paid', 'succeeded', 'completed', 'failed', 'error', 'cancelled', 'refunded', 'unknown'],
    static fn(string $value): string => CommercePurchasePresentation::technical_status_label('payment', $value)
);
$fulfillmentoptions = $options(
    ['none', 'planned', 'pending', 'processing', 'fulfilled', 'completed', 'skipped', 'failed'],
    static fn(string $value): string => CommercePurchasePresentation::technical_status_label('fulfillment', $value)
);
$provideroptions = ['' => get_string('all')];
foreach (Provider::KNOWN as $providercode) {
    $provideroptions[$providercode] = Provider::get($providercode);
}
$currencyoptions = ['' => get_string('all'), 'EUR' => 'EUR', 'RUB' => 'RUB', 'USD' => 'USD'];
$offeroriginoptions = [
    '' => get_string('all'),
    'personaloffer' => get_string('commerce_sales_origin_personal_offer', 'local_subscriptions'),
    'standard' => get_string('commerce_sales_origin_standard', 'local_subscriptions'),
];
$adminstateoptions = [
    'open' => get_string('commerce_sales_adminstate_open', 'local_subscriptions'),
    'closed' => get_string('commerce_sales_adminstate_closed', 'local_subscriptions'),
    'all' => get_string('commerce_sales_adminstate_all', 'local_subscriptions'),
];
$periodoptions = [
    'today' => get_string('commerce_sales_period_today', 'local_subscriptions'),
    '7' => get_string('commerce_sales_period_7', 'local_subscriptions'),
    '30' => get_string('commerce_sales_period_30', 'local_subscriptions'),
    '90' => get_string('commerce_sales_period_90', 'local_subscriptions'),
    '365' => get_string('commerce_sales_period_365', 'local_subscriptions'),
    'all' => get_string('commerce_sales_period_all', 'local_subscriptions'),
    'custom' => get_string('commerce_sales_period_custom', 'local_subscriptions'),
];

$params = array_filter([
    'q' => $query,
    'type' => $type,
    'commercialstatus' => $commercialstatus,
    'paymentstatus' => $paymentstatus,
    'fulfillmentstatus' => $fulfillmentstatus,
    'provider' => $provider,
    'currency' => $currency,
    'offerorigin' => $offerorigin,
    'adminstate' => $adminstate,
    'period' => $period,
    'from' => $period === 'custom' ? $customfrom : '',
    'to' => $period === 'custom' ? $customto : '',
    'perpage' => $perpage,
    'sort' => $sort,
    'dir' => $direction,
], static fn($value): bool => $value !== '');
$params['columns'] = $visiblecolumns;

$columnlabels = [
    'date' => get_string('date'),
    'reference' => get_string('commerce_purchase_reference', 'local_subscriptions'),
    'customer' => get_string('commerce_purchase_customer', 'local_subscriptions'),
    'type' => get_string('commerce_sales_product_type', 'local_subscriptions'),
    'products' => get_string('commerce_purchase_products', 'local_subscriptions'),
    'provider' => get_string('commerce_purchase_provider', 'local_subscriptions'),
    'amount' => get_string('commerce_purchase_amount', 'local_subscriptions'),
    'payment' => get_string('commerce_purchase_payment_status', 'local_subscriptions'),
    'fulfillment' => get_string('commerce_purchase_fulfillment_status', 'local_subscriptions'),
    'commercial' => get_string('commerce_purchase_commercial_status', 'local_subscriptions'),
];

$exporturl = new moodle_url(
    '/local/subscriptions/admin/commerce/purchases/export.php',
    $params
);

$filtersareactive = $query !== ''
    || $type !== ''
    || $commercialstatus !== ''
    || $paymentstatus !== ''
    || $fulfillmentstatus !== ''
    || $provider !== ''
    || $currency !== ''
    || $offerorigin !== ''
    || $adminstate !== 'open'
    || $period !== '30'
    || $customfrom !== ''
    || $customto !== '';

$salesperiodlabel = static function(
    string $period,
    int $datefrom,
    int $dateto
) use ($periodoptions): string {
    if ($period === 'all') {
        return get_string(
            'commerce_result_scope_period_all',
            'local_subscriptions'
        );
    }

    if ($datefrom > 0 && $dateto > 0) {
        $dateformat = get_string('strftimedatetimeshort', 'langconfig');
        return get_string(
            'commerce_result_scope_period_range',
            'local_subscriptions',
            (object)[
                'from' => userdate($datefrom, $dateformat),
                'to' => userdate($dateto, $dateformat),
            ]
        );
    }

    return get_string(
        'commerce_result_scope_period_named',
        'local_subscriptions',
        $periodoptions[$period] ?? $period
    );
};

$salesremoveurl = static function(
    array $params,
    string $name,
    mixed $reset = null
): moodle_url {
    unset($params['page']);

    if ($reset === null || $reset === '') {
        unset($params[$name]);
    } else {
        $params[$name] = $reset;
    }

    if ($name === 'period') {
        unset($params['from'], $params['to']);
    }

    return new moodle_url(
        '/local/subscriptions/admin/commerce/purchases/index.php',
        $params
    );
};

$salesscopepill = static function(
    string $label,
    ?moodle_url $removeurl = null
): string {
    $remove = '';
    if ($removeurl !== null) {
        $remove = html_writer::link(
            $removeurl,
            html_writer::span('×', 'crm-result-scope-pill-remove-symbol'),
            [
                'class' => 'crm-result-scope-pill-remove',
                'title' => get_string(
                    'commerce_result_scope_remove_filter',
                    'local_subscriptions'
                ),
                'aria-label' => get_string(
                    'commerce_result_scope_remove_filter_named',
                    'local_subscriptions',
                    $label
                ),
            ]
        );
    }

    return html_writer::span(
        html_writer::span(s($label), 'crm-result-scope-pill-label') . $remove,
        'crm-result-scope-pill'
    );
};

$salesscopeparams = $params;
unset($salesscopeparams['page']);

$salesscopepills = [];
$salesscopepills[] = $salesscopepill(
    $salesperiodlabel($period, $datefrom, $dateto),
    $period !== '30'
        ? $salesremoveurl($salesscopeparams, 'period', '30')
        : null
);

if ($query !== '') {
    $salesscopepills[] = $salesscopepill(
        get_string(
            'commerce_result_scope_search',
            'local_subscriptions',
            $query
        ),
        $salesremoveurl($salesscopeparams, 'q')
    );
}
if ($type !== '') {
    $salesscopepills[] = $salesscopepill(
        get_string(
            'commerce_result_scope_product_type',
            'local_subscriptions',
            $typeoptions[$type] ?? $type
        ),
        $salesremoveurl($salesscopeparams, 'type')
    );
}
if ($commercialstatus !== '') {
    $salesscopepills[] = $salesscopepill(
        get_string(
            'commerce_result_scope_commercial_status',
            'local_subscriptions',
            $commercialoptions[$commercialstatus] ?? $commercialstatus
        ),
        $salesremoveurl($salesscopeparams, 'commercialstatus')
    );
}
if ($provider !== '') {
    $salesscopepills[] = $salesscopepill(
        get_string(
            'commerce_result_scope_provider',
            'local_subscriptions',
            $provideroptions[$provider] ?? $provider
        ),
        $salesremoveurl($salesscopeparams, 'provider')
    );
}
if ($currency !== '') {
    $salesscopepills[] = $salesscopepill(
        get_string(
            'commerce_result_scope_currency',
            'local_subscriptions',
            $currency
        ),
        $salesremoveurl($salesscopeparams, 'currency')
    );
}
if ($offerorigin !== '') {
    $salesscopepills[] = $salesscopepill(
        get_string(
            'commerce_result_scope_origin',
            'local_subscriptions',
            $offeroriginoptions[$offerorigin] ?? $offerorigin
        ),
        $salesremoveurl($salesscopeparams, 'offerorigin')
    );
}
if ($paymentstatus !== '') {
    $salesscopepills[] = $salesscopepill(
        get_string(
            'commerce_result_scope_payment',
            'local_subscriptions',
            $paymentoptions[$paymentstatus] ?? $paymentstatus
        ),
        $salesremoveurl($salesscopeparams, 'paymentstatus')
    );
}
if ($fulfillmentstatus !== '') {
    $salesscopepills[] = $salesscopepill(
        get_string(
            'commerce_result_scope_fulfillment',
            'local_subscriptions',
            $fulfillmentoptions[$fulfillmentstatus] ?? $fulfillmentstatus
        ),
        $salesremoveurl($salesscopeparams, 'fulfillmentstatus')
    );
}
if ($adminstate !== 'open') {
    $salesscopepills[] = $salesscopepill(
        get_string(
            'commerce_result_scope_admin_state',
            'local_subscriptions',
            $adminstateoptions[$adminstate] ?? $adminstate
        ),
        $salesremoveurl($salesscopeparams, 'adminstate', 'open')
    );
}

// Compact operational filter panel.
$filterhtml = html_writer::start_tag('form', [
    'method' => 'get',
    'class' => 'crm-sales-filter-form crm-sales-filter-form-compact',
    'action' => $pageurl->out(false),
]);
$filterhtml .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sort', 'value' => $sort]);
$filterhtml .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'dir', 'value' => $direction]);
$filterhtml .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'perpage', 'value' => $perpage]);

$filterhtml .= html_writer::div(
    html_writer::tag('i', '', ['class' => 'fa fa-filter', 'aria-hidden' => 'true'])
        . html_writer::tag('strong', get_string('commerce_sales_filters_title', 'local_subscriptions')),
    'crm-sales-filter-title'
);

$filterhtml .= html_writer::start_div('crm-sales-filter-grid crm-sales-filter-grid-primary');
$filterhtml .= html_writer::div(
    html_writer::tag('label', get_string('commerce_purchases_search', 'local_subscriptions'), [
        'for' => 'q', 'class' => 'form-label',
    ])
    . html_writer::div(
        html_writer::empty_tag('input', [
            'type' => 'search', 'name' => 'q', 'id' => 'q', 'value' => $query,
            'class' => 'form-control',
            'placeholder' => get_string('commerce_sales_search_placeholder', 'local_subscriptions'),
        ])
        . html_writer::span(
            html_writer::tag('i', '', ['class' => 'fa fa-search', 'aria-hidden' => 'true']),
            'crm-sales-search-icon'
        ),
        'crm-sales-search-control'
    ),
    'crm-sales-filter-field crm-sales-filter-search'
);
foreach ([
    ['period', get_string('commerce_sales_period', 'local_subscriptions'), html_writer::select(
        $periodoptions, 'period', $period, false, ['id' => 'period', 'class' => 'form-select']
    )],
    ['type', get_string('commerce_sales_product_type', 'local_subscriptions'), html_writer::select(
        $typeoptions, 'type', $type, false, ['id' => 'type', 'class' => 'form-select']
    )],
    ['commercialstatus', get_string('commerce_purchase_commercial_status', 'local_subscriptions'), html_writer::select(
        $commercialoptions, 'commercialstatus', $commercialstatus, false,
        ['id' => 'commercialstatus', 'class' => 'form-select']
    )],
    ['provider', get_string('commerce_purchase_provider', 'local_subscriptions'), html_writer::select(
        $provideroptions, 'provider', $provider, false,
        ['id' => 'provider', 'class' => 'form-select']
    )],
    ['currency', get_string('commerce_sales_currency', 'local_subscriptions'), html_writer::select(
        $currencyoptions, 'currency', $currency, false,
        ['id' => 'currency', 'class' => 'form-select']
    )],
    ['offerorigin', get_string('commerce_sales_origin', 'local_subscriptions'), html_writer::select(
        $offeroriginoptions, 'offerorigin', $offerorigin, false,
        ['id' => 'offerorigin', 'class' => 'form-select']
    )],
] as [$id, $label, $control]) {
    $filterhtml .= html_writer::div(
        html_writer::tag('label', $label, ['for' => $id, 'class' => 'form-label']) . $control,
        'crm-sales-filter-field'
    );
}
$filterhtml .= html_writer::end_div();

$advancedopen = $paymentstatus !== ''
    || $fulfillmentstatus !== ''
    || $adminstate !== 'open'
    || $period === 'custom';
$advanced = html_writer::start_div('crm-sales-filter-advanced-grid');
$advanced .= html_writer::div(
    html_writer::tag(
        'label',
        get_string('commerce_purchase_payment_status', 'local_subscriptions'),
        ['for' => 'paymentstatus', 'class' => 'form-label']
    )
        . html_writer::select(
            $paymentoptions,
            'paymentstatus',
            $paymentstatus,
            false,
            ['id' => 'paymentstatus', 'class' => 'form-select']
        ),
    'crm-sales-filter-field'
);
$advanced .= html_writer::div(
    html_writer::tag(
        'label',
        get_string('commerce_purchase_fulfillment_status', 'local_subscriptions'),
        ['for' => 'fulfillmentstatus', 'class' => 'form-label']
    )
        . html_writer::select(
            $fulfillmentoptions,
            'fulfillmentstatus',
            $fulfillmentstatus,
            false,
            ['id' => 'fulfillmentstatus', 'class' => 'form-select']
        ),
    'crm-sales-filter-field'
);
$advanced .= html_writer::div(
    html_writer::tag(
        'label',
        get_string('commerce_sales_adminstate', 'local_subscriptions'),
        ['for' => 'adminstate', 'class' => 'form-label']
    )
        . html_writer::select(
            $adminstateoptions,
            'adminstate',
            $adminstate,
            false,
            ['id' => 'adminstate', 'class' => 'form-select']
        ),
    'crm-sales-filter-field'
);
$advanced .= html_writer::div(
    html_writer::tag(
        'label',
        get_string('commerce_sales_date_from', 'local_subscriptions'),
        ['for' => 'from', 'class' => 'form-label']
    )
        . html_writer::empty_tag('input', [
            'type' => 'date',
            'name' => 'from',
            'id' => 'from',
            'value' => $customfrom,
            'class' => 'form-control',
            'lang' => current_language(),
        ]),
    'crm-sales-filter-field'
);
$advanced .= html_writer::div(
    html_writer::tag(
        'label',
        get_string('commerce_sales_date_to', 'local_subscriptions'),
        ['for' => 'to', 'class' => 'form-label']
    )
        . html_writer::empty_tag('input', [
            'type' => 'date',
            'name' => 'to',
            'id' => 'to',
            'value' => $customto,
            'class' => 'form-control',
            'lang' => current_language(),
        ]),
    'crm-sales-filter-field'
);
$advanced .= html_writer::end_div();

$morefilters = html_writer::tag(
    'details',
    html_writer::tag(
        'summary',
        html_writer::tag('i', '', ['class' => 'fa fa-sliders', 'aria-hidden' => 'true'])
            . get_string('commerce_sales_more_filters', 'local_subscriptions')
    ) . $advanced,
    ['class' => 'crm-sales-filter-advanced', 'open' => $advancedopen ? 'open' : null]
);

$columnitems = '';
foreach ($availablecolumns as $columnkey) {
    $inputid = 'sales-column-' . $columnkey;
    $columnitems .= html_writer::div(
        html_writer::empty_tag('input', [
            'type' => 'checkbox',
            'id' => $inputid,
            'name' => 'columns[]',
            'value' => $columnkey,
            'checked' => in_array($columnkey, $visiblecolumns, true) ? 'checked' : null,
            'class' => 'form-check-input',
        ])
        . html_writer::tag(
            'label',
            s($columnlabels[$columnkey]),
            ['for' => $inputid, 'class' => 'form-check-label']
        ),
        'form-check'
    );
}
$columnpicker = html_writer::tag(
    'details',
    html_writer::tag(
        'summary',
        html_writer::tag('i', '', ['class' => 'fa fa-columns', 'aria-hidden' => 'true'])
            . get_string('commerce_sales_columns', 'local_subscriptions')
    )
        . html_writer::div(
            $columnitems
                . html_writer::tag(
                    'button',
                    get_string('commerce_sales_apply_columns', 'local_subscriptions'),
                    ['type' => 'submit', 'class' => 'btn btn-sm btn-primary mt-2 w-100']
                ),
            'crm-sales-column-picker-menu'
        ),
    ['class' => 'crm-sales-column-picker']
);

$filterhtml .= html_writer::div(
    html_writer::div($morefilters, 'crm-sales-filter-footer-left')
    . html_writer::div(
        $columnpicker
        . html_writer::link(
            $exporturl,
            html_writer::tag('i', '', ['class' => 'fa fa-download', 'aria-hidden' => 'true'])
                . get_string('commerce_sales_export', 'local_subscriptions'),
            ['class' => 'btn btn-outline-secondary crm-sales-export-button']
        )
        . html_writer::link($pageurl, get_string('reset'), ['class' => 'btn btn-outline-secondary'])
        . html_writer::tag(
            'button',
            html_writer::tag('i', '', ['class' => 'fa fa-filter me-1', 'aria-hidden' => 'true'])
                . get_string('commerce_filters_apply', 'local_subscriptions'),
            ['type' => 'submit', 'class' => 'btn btn-primary']
        ),
        'crm-sales-filter-actions'
    ),
    'crm-sales-filter-footer'
);
$filterhtml .= html_writer::end_tag('form');

// KPI strip.
$percent = static fn(int $value, int $total): string => $total > 0
    ? format_float(($value / $total) * 100, 1) . '%'
    : '0%';
$revenuehtml = '';
foreach ($kpis['revenue'] as $code => $minor) {
    $revenuehtml .= html_writer::div(
        html_writer::span(s($code), 'crm-sales-kpi-currency')
        . html_writer::span(
            s(format_float(((int)$minor) / 100, 2)),
            'crm-sales-kpi-money'
        ),
        'crm-sales-kpi-money-row'
    );
}
if ($revenuehtml === '') {
    $revenuehtml = html_writer::span('—', 'crm-sales-kpi-value');
}
$kpiitems = [
    ['fa-shopping-bag', 'commerce_sales_kpi_matches', (string)$kpis['total'], ''],
    ['fa-line-chart', 'commerce_sales_kpi_revenue', $revenuehtml, '', true],
    ['fa-tags', 'commerce_sales_kpi_personal_offers', (string)$kpis['personaloffers'], $percent($kpis['personaloffers'], $kpis['total']), false, 'is-personal-offer'],
    ['fa-hourglass-half', 'commerce_sales_kpi_pending', (string)$kpis['pending'], $percent($kpis['pending'], $kpis['total']), false, 'is-warning'],
    ['fa-times-circle-o', 'commerce_sales_kpi_failed', (string)$kpis['failed'], $percent($kpis['failed'], $kpis['total']), false, 'is-danger'],
    ['fa-exclamation-circle', 'commerce_sales_kpi_invalid_checkouts', (string)$kpis['invalidcheckouts'], get_string('commerce_sales_kpi_invalid_current', 'local_subscriptions'), false, 'is-attention'],
];
$kpihtml = '';
foreach ($kpiitems as $item) {
    [$icon, $labelkey, $value, $foot] = $item;
    $rawhtml = !empty($item[4]);
    $tone = $item[5] ?? '';
    $content = html_writer::div(
        html_writer::div(
            html_writer::tag('i', '', [
                'class' => 'fa ' . $icon,
                'aria-hidden' => 'true',
            ]),
            'crm-sales-kpi-icon ' . $tone
        )
        . html_writer::div(
            html_writer::div(
                get_string($labelkey, 'local_subscriptions'),
                'crm-sales-kpi-label'
            )
            . ($rawhtml
                ? html_writer::div($value, 'crm-sales-kpi-revenue')
                : html_writer::div(s($value), 'crm-sales-kpi-value'))
            . ($foot !== ''
                ? html_writer::div(s($foot), 'crm-sales-kpi-foot')
                : ''),
            'crm-sales-kpi-copy'
        ),
        'crm-sales-kpi-inner'
    );
    if ($labelkey === 'commerce_sales_kpi_invalid_checkouts') {
        $content = html_writer::link(
            new moodle_url(
                '/local/subscriptions/admin/commerce/unfinished-checkouts/index.php'
            ),
            $content,
            ['class' => 'crm-sales-kpi-link']
        );
    }
    $kpihtml .= html_writer::div($content, 'crm-sales-kpi');
}

// Table toolbar.
$tabletoolbar = html_writer::div(
    html_writer::div(
        html_writer::div(
            get_string(
                'commerce_sales_found',
                'local_subscriptions',
                format_float($result->total, 0)
            ),
            'crm-sales-table-count'
        )
        . html_writer::div(
            html_writer::span(
                get_string(
                    'commerce_result_scope_label',
                    'local_subscriptions'
                ),
                'crm-result-scope-label'
            )
            . implode('', $salesscopepills),
            'crm-result-scope-pills'
        ),
        'crm-result-summary'
    )
    . html_writer::div(
        html_writer::tag('span', get_string('commerce_sales_per_page', 'local_subscriptions'), ['class' => 'text-muted small'])
        . html_writer::select([25 => '25', 50 => '50', 100 => '100'], 'perpage', $perpage, false, [
            'class' => 'form-select form-select-sm crm-sales-perpage-select',
            'onchange' => "this.form.submit()",
        ]),
        'crm-sales-table-toolbar-actions'
    ),
    'crm-sales-table-toolbar'
);

$toolbarform = html_writer::start_tag('form', ['method' => 'get', 'class' => 'm-0']);
foreach ($params as $name => $value) {
    if ($name === 'perpage') {
        continue;
    }
    if (is_array($value)) {
        foreach ($value as $item) {
            $toolbarform .= html_writer::empty_tag('input', [
                'type' => 'hidden',
                'name' => $name . '[]',
                'value' => $item,
            ]);
        }
        continue;
    }
    $toolbarform .= html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => $name,
        'value' => $value,
    ]);
}
$toolbarform .= $tabletoolbar . html_writer::end_tag('form');

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => $pagetitle, 'url' => null],
]);
echo CrmPageHeader::render($pagetitle, get_string('commerce_purchases_description', 'local_subscriptions'), HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::PURCHASES);

$filtertogglelabel = get_string('commerce_sales_filters_toggle', 'local_subscriptions');
$filtertogglemeta = $filtersareactive
    ? html_writer::span(
        get_string('commerce_sales_filters_active', 'local_subscriptions'),
        'crm-sales-filter-panel-status'
    )
    : html_writer::span(
        get_string('commerce_sales_filters_collapsed_hint', 'local_subscriptions'),
        'crm-sales-filter-panel-status'
    );

$filterpanel = html_writer::tag(
    'details',
    html_writer::tag(
        'summary',
        html_writer::div(
            html_writer::tag('i', '', [
                'class' => 'fa fa-filter',
                'aria-hidden' => 'true',
            ])
            . html_writer::tag('strong', $filtertogglelabel)
            . $filtertogglemeta,
            'crm-sales-filter-panel-summary-copy'
        )
        . html_writer::tag('i', '', [
            'class' => 'fa fa-chevron-down crm-sales-filter-panel-chevron',
            'aria-hidden' => 'true',
        ]),
        [
            'class' => 'crm-sales-filter-panel-summary',
            'aria-label' => $filtertogglelabel,
        ]
    )
    . html_writer::div($filterhtml, 'crm-sales-filter-card crm-sales-filter-card-collapsible'),
    [
        'class' => 'crm-sales-filter-panel',
        'open' => $filtersareactive ? 'open' : null,
    ]
);
echo $filterpanel;
echo html_writer::div($kpihtml, 'crm-sales-kpi-strip');

if ($result->purchases === []) {
    echo html_writer::div(
        html_writer::tag('i', '', ['class' => 'fa fa-search fa-2x mb-3 text-muted', 'aria-hidden' => 'true'])
        . html_writer::tag('h3', get_string('commerce_purchases_empty_title', 'local_subscriptions'), ['class' => 'h5'])
        . html_writer::tag('p', get_string('commerce_purchases_empty', 'local_subscriptions'), ['class' => 'text-muted mb-0']),
        'crm-sales-empty'
    );
} else {
    $catalogrepository = new CommerceCatalogReadRepository($DB);
$salesfollowupservice = CommerceSalesFollowupService::create($DB);

    $personalofferids = [];
    $visibleofferuuids = array_values(array_unique(array_filter(array_map(
        static fn($purchase): string => $purchase->haspersonaloffer
            ? $purchase->personalofferuuid
            : '',
        $result->purchases
    ))));
    if ($visibleofferuuids !== []) {
        [$offerinsql, $offerparams] = $DB->get_in_or_equal(
            $visibleofferuuids,
            SQL_PARAMS_NAMED,
            'salesoffer'
        );
        foreach ($DB->get_records_select(
            'local_subs_commerce_offer',
            "offeruuid {$offerinsql}",
            $offerparams,
            '',
            'id,offeruuid'
        ) as $offerrecord) {
            $personalofferids[strtolower((string)$offerrecord->offeruuid)] = (int)$offerrecord->id;
        }
    }

    $sortkeys = [
        'date' => 'date',
        'reference' => 'reference',
        'customer' => 'customer',
        'type' => 'type',
        'products' => 'product',
        'amount' => 'amount',
        'payment' => 'payment',
        'fulfillment' => 'fulfillment',
        'commercial' => 'commercial',
    ];

    $sortableheader = static function(string $columnkey, string $label) use (
        $sortkeys,
        $sort,
        $direction,
        $params,
        $pageurl
    ): string {
        $sortkey = $sortkeys[$columnkey] ?? '';
        if ($sortkey === '') {
            return s($label);
        }

        $isactive = $sort === $sortkey;
        $nextdirection = $isactive && $direction === 'asc' ? 'desc' : 'asc';
        $sortparams = $params;
        $sortparams['sort'] = $sortkey;
        $sortparams['dir'] = $nextdirection;
        $sortparams['page'] = 0;

        $icon = !$isactive
            ? 'fa-sort'
            : ($direction === 'asc' ? 'fa-sort-asc' : 'fa-sort-desc');

        return html_writer::link(
            new moodle_url($pageurl, $sortparams),
            s($label)
                . html_writer::tag('i', '', [
                    'class' => 'fa ' . $icon . ' crm-sales-sort-icon',
                    'aria-hidden' => 'true',
                ]),
            [
                'class' => 'crm-sales-sort-link' . ($isactive ? ' is-active' : ''),
                'aria-label' => get_string(
                    'commerce_sales_sort_by',
                    'local_subscriptions',
                    (object)[
                        'column' => $label,
                        'direction' => $nextdirection === 'asc'
                            ? get_string('commerce_sales_sort_ascending', 'local_subscriptions')
                            : get_string('commerce_sales_sort_descending', 'local_subscriptions'),
                    ]
                ),
            ]
        );
    };

    $table = new html_table();
    $table->attributes = [
        'class' => 'generaltable table table-hover align-middle crm-sales-table',
        'aria-label' => get_string('commerce_purchases_table_label', 'local_subscriptions'),
    ];

    $table->head = [];
    foreach ($visiblecolumns as $columnkey) {
        $table->head[] = $sortableheader($columnkey, $columnlabels[$columnkey]);
    }
    $table->head[] = get_string('actions');

    foreach ($result->purchases as $purchase) {
        $viewurl = new moodle_url(
            '/local/subscriptions/admin/commerce/purchases/view.php',
            ['id' => $purchase->id]
        );

        $customername = $purchase->customer->display_name();
        $customerlabel = $customername !== ''
            ? $customername
            : ($purchase->customer->email !== ''
                ? $purchase->customer->email
                : get_string('unknownuser'));

        $customerhtml = '';
        if ($purchase->customer->userid !== null || $purchase->customer->email !== '') {
            $user360params = $purchase->customer->userid !== null
                ? ['id' => $purchase->customer->userid]
                : ['email' => $purchase->customer->email];
            $customerhtml .= html_writer::link(
                new moodle_url('/local/subscriptions/admin/users/view.php', $user360params),
                s($customerlabel),
                ['class' => 'crm-sales-customer-name']
            );
        } else {
            $customerhtml .= html_writer::span(s($customerlabel), 'crm-sales-customer-name');
        }
        if ($purchase->customer->email !== '') {
            $customerhtml .= html_writer::div(
                s($purchase->customer->email),
                'crm-sales-customer-email'
            );
        }

        $productlinks = [];
        foreach (array_slice($purchase->productitems, 0, 3) as $productitem) {
            $catalogdetails = $catalogrepository->find_by_purchase_reference(
                (string)$productitem['sku']
            );
            $productlinks[] = $catalogdetails === null
                ? s($productitem['label'])
                : html_writer::link(
                    CommerceCatalogLinkGenerator::view_url($catalogdetails->get_summary()),
                    s($productitem['label']),
                    ['class' => 'crm-sales-product-link']
                );
        }
        $products = implode('<br>', $productlinks);
        if (count($purchase->productitems) > 3) {
            $products .= html_writer::div(
                '+' . (count($purchase->productitems) - 3),
                'small text-muted'
            );
        }

        $providerhtml = '—';
        if ($purchase->provider !== null && $purchase->provider !== '') {
            $providername = Provider::get($purchase->provider);
            $providerurl = Provider::icon_url($purchase->provider);
            if ($providerurl !== null) {
                $providerhtml = html_writer::empty_tag('img', [
                    'src' => $providerurl->out(false),
                    'alt' => $providername,
                    'title' => $providername,
                    'aria-label' => $providername,
                    'class' => 'crm-sales-provider-icon',
                    'width' => 22,
                    'height' => 22,
                    'loading' => 'lazy',
                ]);
            } else {
                $providerhtml = html_writer::span(
                    html_writer::tag('i', '', [
                        'class' => 'fa fa-credit-card',
                        'aria-hidden' => 'true',
                    ]),
                    'crm-sales-provider-icon-fallback',
                    [
                        'title' => $providername,
                        'aria-label' => $providername,
                    ]
                );
            }
        }
        $paymenthtml = html_writer::div(
            CommercePurchasePresentation::technical_status_badge(
                'payment',
                $purchase->paymentstatus
            ),
            'crm-sales-payment-inline'
        );

        $fulfillmenthtml = CommercePurchasePresentation::technical_status_badge(
            'fulfillment',
            $purchase->fulfillmentstatus
        );

        $commercialhtml = html_writer::div(
            CommercePurchasePresentation::commercial_status_badge(
                $purchase->commercialstatus
            )
            . ($purchase->adminclosed
                ? html_writer::span(
                    get_string(
                        'commerce_sales_admin_closed_badge',
                        'local_subscriptions'
                    ),
                    'badge crm-sales-admin-closed-badge'
                )
                : ''),
            'crm-sales-commercial-summary'
        );

        $actions = (static function() use (
            $purchase,
            $viewurl,
            $context,
            $closedwithoutfulfillment,
            $salesfollowupservice,
            $adminclosureservice,
            $pageurl
        ): string {
            $primary = html_writer::link(
                $viewurl,
                html_writer::tag('i', '', [
                    'class' => 'fa fa-eye me-1',
                    'aria-hidden' => 'true',
                ]) . get_string('view'),
                ['class' => 'btn btn-sm btn-outline-primary']
            );

            $returnurl = $pageurl->out_as_local_url(false);
            $policy = new CommercePurchaseActionPolicy();

            $sections = [
                'customer' => [],
                'communication' => [],
                'commercial' => [],
                'order' => [],
                'administration' => [],
            ];

            if ($purchase->customer->userid !== null
                    || $purchase->customer->email !== '') {
                $user360params = $purchase->customer->userid !== null
                    ? ['id' => $purchase->customer->userid]
                    : ['email' => $purchase->customer->email];
                $sections['customer'][] = html_writer::link(
                    new moodle_url(
                        '/local/subscriptions/admin/users/view.php',
                        $user360params
                    ),
                    html_writer::tag('i', '', [
                        'class' => 'fa fa-user-circle-o',
                        'aria-hidden' => 'true',
                    ]) . html_writer::span(
                        get_string(
                            'commerce_sales_action_open_user360',
                            'local_subscriptions'
                        )
                    ),
                    ['class' => 'crm-sales-row-menu-link']
                );
            }

            if (has_capability(Capabilities::MANAGE_SUBSCRIPTIONS, $context)) {
                try {
                    if ($salesfollowupservice->is_summary_eligible($purchase)) {
                        $sections['communication'][] = html_writer::link(
                            new moodle_url(
                                '/local/subscriptions/admin/commerce/purchases/followup_mail.php',
                                ['id' => (int)$purchase->id]
                            ),
                            html_writer::tag('i', '', [
                                'class' => 'fa fa-paper-plane-o',
                                'aria-hidden' => 'true',
                            ]) . html_writer::span(
                                get_string(
                                    'commerce_sales_followup_action',
                                    'local_subscriptions'
                                )
                            ),
                            [
                                'class' => 'crm-sales-row-menu-link '
                                    . 'crm-sales-row-menu-followup',
                            ]
                        );
                    }
                } catch (\Throwable) {
                    // Keep the table usable if a single row cannot be enriched.
                }

                if ($policy->can_resend_receipt_summary($purchase)) {
                    $resendreceipturl = new moodle_url(
                        '/local/subscriptions/admin/commerce/purchases/resend_receipt.php',
                        [
                            'id' => (int)$purchase->id,
                            'confirm' => 1,
                            'sesskey' => sesskey(),
                            'returnurl' => $returnurl,
                        ]
                    );
                    $sections['communication'][] = html_writer::link(
                        $resendreceipturl,
                        html_writer::tag('i', '', [
                            'class' => 'fa fa-envelope-o',
                            'aria-hidden' => 'true',
                        ]) . html_writer::span(
                            get_string(
                                'commerce_sales_action_resend_invoice',
                                'local_subscriptions'
                            )
                        ),
                        [
                            'class' => 'crm-sales-row-menu-link',
                            'data-confirmation' => 'modal',
                            'data-confirmation-title-str' => json_encode([
                                'commerce_sales_action_resend_invoice',
                                'local_subscriptions',
                            ]),
                            'data-confirmation-content-str' => json_encode([
                                'commerce_sales_action_resend_invoice_confirm',
                                'local_subscriptions',
                            ]),
                            'data-confirmation-yes-button-str' => json_encode(['yes']),
                            'data-confirmation-destination' => $resendreceipturl->out(false),
                        ]
                    );
                }

                if ($policy->can_resend_access_summary($purchase)) {
                    $resendaccessurl = new moodle_url(
                        '/local/subscriptions/admin/commerce/purchases/resend_access.php',
                        [
                            'id' => (int)$purchase->id,
                            'confirm' => 1,
                            'sesskey' => sesskey(),
                            'returnurl' => $returnurl,
                        ]
                    );
                    $sections['communication'][] = html_writer::link(
                        $resendaccessurl,
                        html_writer::tag('i', '', [
                            'class' => 'fa fa-key',
                            'aria-hidden' => 'true',
                        ]) . html_writer::span(
                            get_string(
                                'commerce_sales_action_resend_access',
                                'local_subscriptions'
                            )
                        ),
                        [
                            'class' => 'crm-sales-row-menu-link',
                            'data-confirmation' => 'modal',
                            'data-confirmation-title-str' => json_encode([
                                'commerce_sales_action_resend_access',
                                'local_subscriptions',
                            ]),
                            'data-confirmation-content-str' => json_encode([
                                'commerce_purchase_resend_access_confirm',
                                'local_subscriptions',
                            ]),
                            'data-confirmation-yes-button-str' => json_encode(['yes']),
                            'data-confirmation-destination' => $resendaccessurl->out(false),
                        ]
                    );
                }
            }

            if (has_capability(Capabilities::MANAGE_CRM_ADMIN_TOOLS, $context)
                    && $policy->can_create_personal_offer_summary($purchase)) {
                $sections['commercial'][] = html_writer::link(
                    new moodle_url(
                        '/local/subscriptions/admin/commerce/personal-offers/create.php',
                        [
                            'prefillemail' => $purchase->customer->email,
                            'prefillsourcemode' => 'purchase',
                            'prefillsourcepurchase' => $purchase->reference,
                        ]
                    ),
                    html_writer::tag('i', '', [
                        'class' => 'fa fa-tag',
                        'aria-hidden' => 'true',
                    ]) . html_writer::span(
                        get_string(
                            'commerce_sales_action_create_offer',
                            'local_subscriptions'
                        )
                    ),
                    ['class' => 'crm-sales-row-menu-link']
                );
            }

            $sections['order'][] = html_writer::link(
                new moodle_url(
                    '/local/subscriptions/order_details.php',
                    [
                        'reference' => $purchase->reference,
                        'adminreturn' => 1,
                    ]
                ),
                html_writer::tag('i', '', [
                    'class' => 'fa fa-external-link',
                    'aria-hidden' => 'true',
                ]) . html_writer::span(
                    get_string(
                        'commerce_sales_action_view_customer_order',
                        'local_subscriptions'
                    )
                ),
                ['class' => 'crm-sales-row-menu-link']
            );
            $sections['order'][] = html_writer::link(
                new moodle_url('/local/subscriptions/order_invoice.php', [
                    'reference' => $purchase->reference,
                ]),
                html_writer::tag('i', '', [
                    'class' => 'fa fa-file-pdf-o',
                    'aria-hidden' => 'true',
                ]) . html_writer::span(
                    get_string(
                        'commerce_purchase_download_invoice',
                        'local_subscriptions'
                    )
                ),
                [
                    'class' => 'crm-sales-row-menu-link',
                    'target' => '_blank',
                    'rel' => 'noopener noreferrer',
                ]
            );
            $sections['order'][] = html_writer::link(
                new moodle_url('/local/subscriptions/admin/commerce/mail/index.php', [
                    'purchaseid' => $purchase->id,
                ]),
                html_writer::tag('i', '', [
                    'class' => 'fa fa-list-alt',
                    'aria-hidden' => 'true',
                ]) . html_writer::span(
                    get_string(
                        'commerce_purchase_open_mail_journal',
                        'local_subscriptions'
                    )
                ),
                ['class' => 'crm-sales-row-menu-link']
            );

            if ($purchase->provider === Provider::ALFA
                    && !in_array(
                        $purchase->paymentstatus,
                        ['paid', 'completed', 'succeeded'],
                        true
                    )) {
                $sections['order'][] = html_writer::link(
                    new moodle_url(
                        '/local/subscriptions/admin/commerce/purchases/reconcile_alfa.php',
                        ['id' => $purchase->id]
                    ),
                    html_writer::tag('i', '', [
                        'class' => 'fa fa-refresh',
                        'aria-hidden' => 'true',
                    ]) . html_writer::span(
                        get_string(
                            'commerce_alfa_crm_verify_short',
                            'local_subscriptions'
                        )
                    ),
                    ['class' => 'crm-sales-row-menu-link']
                );
            }

            if (!isset($closedwithoutfulfillment[$purchase->id])
                    && $policy->can_retry_summary($purchase)
                    && has_capability(
                        Capabilities::MANAGE_SUBSCRIPTIONS,
                        $context
                    )) {
                $retryurl = new moodle_url(
                    '/local/subscriptions/admin/commerce/purchases/retry_fulfillment.php',
                    [
                        'id' => $purchase->id,
                        'confirm' => 1,
                        'sesskey' => sesskey(),
                    ]
                );
                $sections['order'][] = html_writer::link(
                    $retryurl,
                    html_writer::tag('i', '', [
                        'class' => 'fa fa-repeat',
                        'aria-hidden' => 'true',
                    ]) . html_writer::span(
                        get_string(
                            'commerce_purchase_retry_short',
                            'local_subscriptions'
                        )
                    ),
                    [
                        'class' => 'crm-sales-row-menu-link',
                        'data-confirmation' => 'modal',
                        'data-confirmation-title-str' => json_encode([
                            'commerce_purchase_retry_fulfillment',
                            'local_subscriptions',
                        ]),
                        'data-confirmation-content-str' => json_encode([
                            'commerce_purchase_retry_confirm',
                            'local_subscriptions',
                        ]),
                        'data-confirmation-yes-button-str' => json_encode(['yes']),
                        'data-confirmation-destination' => $retryurl->out(false),
                    ]
                );
            }

            if (has_capability(Capabilities::MANAGE_SUBSCRIPTIONS, $context)) {
                if ($purchase->adminclosed) {
                    $reopenurl = new moodle_url(
                        '/local/subscriptions/admin/commerce/purchases/admin_state.php',
                        [
                            'id' => (int)$purchase->id,
                            'action' => 'reopen',
                            'sesskey' => sesskey(),
                            'returnurl' => $returnurl,
                        ]
                    );
                    $sections['administration'][] = html_writer::link(
                        $reopenurl,
                        html_writer::tag('i', '', [
                            'class' => 'fa fa-folder-open-o',
                            'aria-hidden' => 'true',
                        ]) . html_writer::span(
                            get_string(
                                'commerce_sales_action_reopen',
                                'local_subscriptions'
                            )
                        ),
                        ['class' => 'crm-sales-row-menu-link']
                    );
                } elseif ($adminclosureservice->can_close($purchase)) {
                    $closeurl = new moodle_url(
                        '/local/subscriptions/admin/commerce/purchases/admin_state.php',
                        [
                            'id' => (int)$purchase->id,
                            'action' => 'close',
                            'sesskey' => sesskey(),
                            'returnurl' => $returnurl,
                        ]
                    );
                    $sections['administration'][] = html_writer::link(
                        $closeurl,
                        html_writer::tag('i', '', [
                            'class' => 'fa fa-archive',
                            'aria-hidden' => 'true',
                        ]) . html_writer::span(
                            get_string(
                                'commerce_sales_action_close',
                                'local_subscriptions'
                            )
                        ),
                        [
                            'class' => 'crm-sales-row-menu-link is-danger',
                            'data-confirmation' => 'modal',
                            'data-confirmation-title-str' => json_encode([
                                'commerce_sales_action_close',
                                'local_subscriptions',
                            ]),
                            'data-confirmation-content-str' => json_encode([
                                'commerce_sales_action_close_confirm',
                                'local_subscriptions',
                            ]),
                            'data-confirmation-yes-button-str' => json_encode([
                                'commerce_sales_action_close',
                                'local_subscriptions',
                            ]),
                            'data-confirmation-destination' => $closeurl->out(false),
                        ]
                    );
                }
            }

            $sectionlabels = [
                'customer' => 'commerce_sales_actions_customer',
                'communication' => 'commerce_sales_actions_communication',
                'commercial' => 'commerce_sales_actions_commercial',
                'order' => 'commerce_sales_actions_order',
                'administration' => 'commerce_sales_actions_administration',
            ];
            $menuitems = [];
            foreach ($sections as $sectionkey => $items) {
                if ($items === []) {
                    continue;
                }
                $menuitems[] = html_writer::div(
                    get_string(
                        $sectionlabels[$sectionkey],
                        'local_subscriptions'
                    ),
                    'crm-sales-row-menu-section'
                );
                array_push($menuitems, ...$items);
            }

            $menu = html_writer::tag(
                'details',
                html_writer::tag(
                    'summary',
                    html_writer::tag('i', '', [
                        'class' => 'fa fa-ellipsis-h',
                        'aria-hidden' => 'true',
                    ]),
                    [
                        'class' => 'btn btn-sm btn-outline-secondary crm-sales-row-menu-toggle',
                        'aria-label' => get_string('commerce_sales_more_actions', 'local_subscriptions'),
                        'title' => get_string('commerce_sales_more_actions', 'local_subscriptions'),
                    ]
                )
                    . html_writer::div(
                        implode('', $menuitems),
                        'crm-sales-row-menu'
                    ),
                ['class' => 'crm-sales-row-actions-menu']
            );

            return html_writer::div(
                $primary . $menu,
                'crm-sales-actions'
            );
        })();

        $cells = [
            'date' => html_writer::div(
                    userdate(
                        $purchase->timecreated,
                        get_string('strftimedate', 'langconfig')
                    ),
                    'crm-sales-date'
                )
                . html_writer::div(
                    userdate(
                        $purchase->timecreated,
                        get_string('strftimetime', 'langconfig')
                    ),
                    'crm-sales-time'
                ),
            'reference' => html_writer::div(
                html_writer::link(
                    $viewurl,
                    s($purchase->publicreference),
                    ['class' => 'crm-sales-reference']
                )
                . html_writer::div(
                    get_string(
                        'commerce_purchase_internal_reference_short',
                        'local_subscriptions'
                    )
                        . ': '
                        . html_writer::tag(
                            'code',
                            s($purchase->reference)
                        ),
                    'crm-sales-internal-reference'
                )
            ),
            'customer' => $customerhtml,
            'type' => CommercePurchasePresentation::type_badge($purchase->type)
                . ($purchase->haspersonaloffer
                    ? html_writer::div(
                        isset($personalofferids[$purchase->personalofferuuid])
                            ? html_writer::link(
                                new moodle_url(
                                    '/local/subscriptions/admin/commerce/personal-offers/view.php',
                                    ['id' => $personalofferids[$purchase->personalofferuuid]]
                                ),
                                html_writer::tag('i', '', [
                                    'class' => 'fa fa-tag me-1',
                                    'aria-hidden' => 'true',
                                ])
                                    . get_string(
                                        'commerce_sales_personal_offer_badge',
                                        'local_subscriptions'
                                    ),
                                [
                                    'class' => 'crm-sales-personal-offer-badge',
                                    'title' => $purchase->personaloffercampaign !== ''
                                        ? $purchase->personaloffercampaign
                                        : null,
                                ]
                            )
                            : html_writer::span(
                                html_writer::tag('i', '', [
                                    'class' => 'fa fa-tag me-1',
                                    'aria-hidden' => 'true',
                                ])
                                    . get_string(
                                        'commerce_sales_personal_offer_badge',
                                        'local_subscriptions'
                                    ),
                                [
                                    'class' => 'crm-sales-personal-offer-badge',
                                    'title' => $purchase->personaloffercampaign !== ''
                                        ? $purchase->personaloffercampaign
                                        : null,
                                ]
                            ),
                        'crm-sales-personal-offer-wrap'
                    )
                    : ''),
            'products' => $products,
            'provider' => html_writer::div(
                $providerhtml,
                'crm-sales-provider-cell'
            ),
            'amount' => html_writer::span(
                s(CommercePurchasePresentation::money(
                    $purchase->totalminor,
                    $purchase->currency
                )),
                'crm-sales-amount'
            ),
            'payment' => $paymenthtml,
            'fulfillment' => $fulfillmenthtml,
            'commercial' => $commercialhtml,
        ];

        $row = [];
        foreach ($visiblecolumns as $columnkey) {
            $row[] = $cells[$columnkey];
        }
        $row[] = $actions;
        if ($purchase->haspersonaloffer) {
            $tablerow = new html_table_row($row);
            $tablerow->attributes['class'] = 'crm-sales-row-personal-offer';
            $table->data[] = $tablerow;
        } else {
            $table->data[] = $row;
        }
    }

    echo html_writer::div(
        $toolbarform
        . html_writer::div(
            html_writer::table($table),
            'crm-sales-table-scroll'
        )
        . html_writer::div(
            $OUTPUT->paging_bar(
                $result->total,
                $result->page,
                $result->perpage,
                new moodle_url($pageurl, $params)
            ),
            'crm-sales-pagination'
        ),
        'crm-sales-table-card'
    );
}

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
