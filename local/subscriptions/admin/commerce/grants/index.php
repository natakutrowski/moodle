<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\grant\CommerceBulkGrantCampaignService;
use local_subscriptions\commerce\personaloffer\admin\CommercePersonalOfferCrmPresentation;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceOffersAccessNavigationRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceOffersAccessPolishRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_SUBSCRIPTIONS);

$query = trim(optional_param('q', '', PARAM_RAW_TRIMMED));
$status = trim(optional_param('status', '', PARAM_ALPHANUMEXT));
$period = trim(optional_param('period', '30', PARAM_ALPHANUMEXT));
$customfrom = trim(optional_param('datefrom', '', PARAM_RAW_TRIMMED));
$customto = trim(optional_param('dateto', '', PARAM_RAW_TRIMMED));
$page = max(0, optional_param('page', 0, PARAM_INT));
$perpage = min(100, max(20, optional_param('perpage', 50, PARAM_INT)));

$allowedstatuses = ['', 'planned', 'active', 'failed'];
if (!in_array($status, $allowedstatuses, true)) {
    $status = '';
}
$allowedperiods = ['today', '7', '30', '90', '365', 'all', 'custom'];
if (!in_array($period, $allowedperiods, true)) {
    $period = '30';
}

/** @return int|null */
$parsedate = static function(string $value, bool $endofday = false): ?int {
    $value = trim($value);
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value)) {
        return null;
    }
    try {
        $timezone = \core_date::get_user_timezone_object();
        $date = new \DateTimeImmutable($value, $timezone);
        $date = $endofday
            ? $date->setTime(23, 59, 59)
            : $date->setTime(0, 0, 0);
        return $date->getTimestamp();
    } catch (\Throwable) {
        return null;
    }
};

$now = time();
$timefrom = 0;
$timeto = 0;
if ($period === 'custom') {
    $timefrom = $parsedate($customfrom) ?? 0;
    $timeto = $parsedate($customto, true) ?? 0;
} else if ($period === 'today') {
    $timefrom = usergetmidnight($now);
    $timeto = $now;
} else if ($period !== 'all') {
    $days = (int)$period;
    $days = in_array($days, [7, 30, 90, 365], true) ? $days : 30;
    $timefrom = $now - ($days * DAYSECS);
    $timeto = $now;
    $period = (string)$days;
}

$urlparams = array_filter([
    'q' => $query,
    'status' => $status,
    'period' => $period,
    'datefrom' => $customfrom,
    'dateto' => $customto,
    'page' => $page,
    'perpage' => $perpage,
], static fn(mixed $value): bool => $value !== '' && $value !== 0);

$url = new moodle_url(
    '/local/subscriptions/admin/commerce/grants/index.php',
    $urlparams
);
$title = get_string('commerce_grants_title', 'local_subscriptions');

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $url,
    $title,
    'local-subscriptions-commerce-grants-page'
);

$where = [];
$params = [];
// This workspace is intentionally limited to administrative/manual grants.
// Purchase fulfillment grants belong to Sales and must not inflate Attribution KPIs.
$where[] = $DB->sql_like('g.purchasereference', ':manualsource', false);
$params['manualsource'] = 'manual-u%';

if ($status !== '') {
    $where[] = 'g.status = :status';
    $params['status'] = $status;
}
if ($timefrom > 0) {
    $where[] = 'g.timecreated >= :timefrom';
    $params['timefrom'] = $timefrom;
}
if ($timeto > 0) {
    $where[] = 'g.timecreated <= :timeto';
    $params['timeto'] = $timeto;
}
if ($query !== '') {
    $like = '%' . $DB->sql_like_escape($query) . '%';
    $params['qemail'] = $like;
    $params['qsku'] = $like;
    $params['qfirstname'] = $like;
    $params['qlastname'] = $like;
    $params['qfullname'] = $like;
    $fullname = $DB->sql_concat('u.firstname', "' '", 'u.lastname');
    $where[] = '('
        . $DB->sql_like('g.beneficiaryemail', ':qemail', false)
        . ' OR ' . $DB->sql_like('g.productsku', ':qsku', false)
        . ' OR EXISTS (SELECT 1 FROM {user} u'
        . ' WHERE u.id = g.beneficiaryuserid'
        . ' AND u.deleted = 0 AND ('
        . $DB->sql_like('u.firstname', ':qfirstname', false)
        . ' OR ' . $DB->sql_like('u.lastname', ':qlastname', false)
        . ' OR ' . $DB->sql_like($fullname, ':qfullname', false)
        . ')))';
}

$wheresql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$total = (int)$DB->count_records_sql(
    'SELECT COUNT(1) FROM {local_subs_commerce_grant} g' . $wheresql,
    $params
);
$grants = array_values($DB->get_records_sql(
    'SELECT g.*'
    . ' FROM {local_subs_commerce_grant} g'
    . $wheresql
    . ' ORDER BY g.timecreated DESC, g.id DESC',
    $params,
    $page * $perpage,
    $perpage
));

$summaryparams = $params;
$summarywhere = $where;
$summarywhere = array_values(array_filter(
    $summarywhere,
    static fn(string $clause): bool => !str_contains($clause, 'g.status = :status')
));
unset($summaryparams['status']);
$summarysql = $summarywhere
    ? ' WHERE ' . implode(' AND ', $summarywhere)
    : '';

$summaryrows = $DB->get_records_sql(
    'SELECT MIN(g.id) AS rowid, g.status, COUNT(1) AS total'
    . ' FROM {local_subs_commerce_grant} g'
    . $summarysql
    . ' GROUP BY g.status',
    $summaryparams
);
$counts = ['planned' => 0, 'active' => 0, 'failed' => 0, 'other' => 0];
foreach ($summaryrows as $summaryrow) {
    $key = (string)$summaryrow->status;
    if (array_key_exists($key, $counts)) {
        $counts[$key] = (int)$summaryrow->total;
    } else {
        $counts['other'] += (int)$summaryrow->total;
    }
}
$periodtotal = array_sum($counts);

$productnames = [];
$productids = [];
foreach ($DB->get_records(
    'local_subs_commerce_product',
    null,
    '',
    'id,sku,name'
) as $product) {
    $productnames[(string)$product->sku] =
        CommercePersonalOfferCrmPresentation::business_product_label(
            $DB,
            (int)$product->id
        );
    $productids[(string)$product->sku] = (int)$product->id;
}

$userids = [];
foreach ($grants as $grant) {
    if (!empty($grant->beneficiaryuserid)) {
        $userids[] = (int)$grant->beneficiaryuserid;
    }
}
$users = $userids
    ? $DB->get_records_list('user', 'id', array_values(array_unique($userids)), '', '*')
    : [];

$statuslabels = [
    'planned' => get_string('commerce_offers_access_grant_status_planned', 'local_subscriptions'),
    'active' => get_string('commerce_offers_access_grant_status_active', 'local_subscriptions'),
    'failed' => get_string('commerce_offers_access_grant_status_failed', 'local_subscriptions'),
];
$statusclasses = [
    'planned' => 'is-warning',
    'active' => 'is-success',
    'failed' => 'is-error',
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

$grantperiodlabel = static function(
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
        $format = get_string('strftimedatetimeshort', 'langconfig');
        return get_string(
            'commerce_result_scope_period_range',
            'local_subscriptions',
            (object)[
                'from' => userdate($datefrom, $format),
                'to' => userdate($dateto, $format),
            ]
        );
    }
    return get_string(
        'commerce_result_scope_period_named',
        'local_subscriptions',
        $periodoptions[$period] ?? $period
    );
};

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    [
        'label' => get_string('crm_commerce_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php'),
    ],
    [
        'label' => get_string('commerce_offers_access_title', 'local_subscriptions'),
        'url' => new moodle_url(
            '/local/subscriptions/admin/commerce/offers-access/index.php'
        ),
    ],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render(
    $title,
    get_string(
        'commerce_offers_access_grants_workspace_description',
        'local_subscriptions'
    ),
    HelpContext::COMMERCE
);
echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::OFFERS_ACCESS,
    $context
);
echo CommerceOffersAccessNavigationRenderer::render(
    CommerceOffersAccessNavigationRenderer::GRANTS
);

echo html_writer::div(
    html_writer::link(
        new moodle_url(
            '/local/subscriptions/admin/commerce/offers-access/create.php',
            ['kind' => 'grant']
        ),
        html_writer::tag('i', '', [
            'class' => 'fa fa-plus me-1',
            'aria-hidden' => 'true',
        ])
        . get_string(
            'commerce_offers_access_grant_action',
            'local_subscriptions'
        ),
        ['class' => 'btn crm-grant-action-primary']
    ),
    'crm-offers-access-list-actions'
);

echo html_writer::div(
    html_writer::div(
        html_writer::div((string)$periodtotal, 'crm-offers-access-kpi-value')
        . html_writer::div(
            get_string(
                'commerce_offers_access_grants_kpi_total',
                'local_subscriptions'
            ),
            'crm-offers-access-kpi-label'
        ),
        'crm-offers-access-kpi is-neutral'
    )
    . html_writer::div(
        html_writer::div((string)$counts['active'], 'crm-offers-access-kpi-value')
        . html_writer::div(
            get_string(
                'commerce_offers_access_grants_kpi_active',
                'local_subscriptions'
            ),
            'crm-offers-access-kpi-label'
        ),
        'crm-offers-access-kpi is-success'
    )
    . html_writer::div(
        html_writer::div((string)$counts['planned'], 'crm-offers-access-kpi-value')
        . html_writer::div(
            get_string(
                'commerce_offers_access_grants_kpi_pending',
                'local_subscriptions'
            ),
            'crm-offers-access-kpi-label'
        ),
        'crm-offers-access-kpi is-warning'
    )
    . html_writer::div(
        html_writer::div((string)$counts['failed'], 'crm-offers-access-kpi-value')
        . html_writer::div(
            get_string(
                'commerce_offers_access_grants_kpi_failed',
                'local_subscriptions'
            ),
            'crm-offers-access-kpi-label'
        ),
        'crm-offers-access-kpi is-error'
    ),
    'crm-offers-access-kpis'
);

$filterurl = new moodle_url(
    '/local/subscriptions/admin/commerce/grants/index.php'
);
$filtersareactive = $query !== ''
    || $status !== ''
    || $period !== '30'
    || $customfrom !== ''
    || $customto !== '';

$filterbody = html_writer::start_tag('form', [
    'method' => 'get',
    'action' => $filterurl->out(false),
    'class' => 'crm-sales-filter-form crm-grants-filter-form',
]);
$filterbody .= html_writer::start_div('crm-sales-filter-grid');
$filterbody .= html_writer::div(
    html_writer::label(
        get_string('search'),
        'grant-query',
        false,
        ['class' => 'form-label']
    )
    . html_writer::div(
        html_writer::empty_tag('input', [
            'id' => 'grant-query',
            'name' => 'q',
            'type' => 'search',
            'value' => $query,
            'class' => 'form-control',
            'placeholder' => get_string(
                'commerce_offers_access_grants_search_placeholder',
                'local_subscriptions'
            ),
        ]),
        'crm-sales-filter-search-control'
    ),
    'crm-sales-filter-field crm-sales-filter-search'
);
$filterbody .= html_writer::div(
    html_writer::label(
        get_string('commerce_offers_access_period', 'local_subscriptions'),
        'grant-period',
        false,
        ['class' => 'form-label']
    )
    . html_writer::select(
        $periodoptions,
        'period',
        $period,
        false,
        ['id' => 'grant-period', 'class' => 'form-select']
    ),
    'crm-sales-filter-field'
);
$filterbody .= html_writer::div(
    html_writer::label(
        get_string('status'),
        'grant-status',
        false,
        ['class' => 'form-label']
    )
    . html_writer::select(
        ['' => get_string('all')] + $statuslabels,
        'status',
        $status,
        false,
        ['id' => 'grant-status', 'class' => 'form-select']
    ),
    'crm-sales-filter-field'
);
$filterbody .= html_writer::end_div();

$customstyle = $period === 'custom' ? '' : 'display:none;';
$filterbody .= html_writer::div(
    html_writer::div(
        html_writer::label(
            get_string('commerce_sales_date_from', 'local_subscriptions'),
            'grant-datefrom',
            false,
            ['class' => 'form-label']
        )
        . html_writer::empty_tag('input', [
            'id' => 'grant-datefrom',
            'name' => 'datefrom',
            'type' => 'date',
            'value' => $customfrom,
            'class' => 'form-control',
        ]),
        'crm-sales-filter-field'
    )
    . html_writer::div(
        html_writer::label(
            get_string('commerce_sales_date_to', 'local_subscriptions'),
            'grant-dateto',
            false,
            ['class' => 'form-label']
        )
        . html_writer::empty_tag('input', [
            'id' => 'grant-dateto',
            'name' => 'dateto',
            'type' => 'date',
            'value' => $customto,
            'class' => 'form-control',
        ]),
        'crm-sales-filter-field'
    ),
    'crm-sales-filter-custom-period',
    ['id' => 'grant-custom-period', 'style' => $customstyle]
);

$filterbody .= html_writer::div(
    html_writer::link(
        $filterurl,
        get_string('reset'),
        ['class' => 'btn btn-outline-secondary']
    )
    . html_writer::tag(
        'button',
        html_writer::tag('i', '', [
            'class' => 'fa fa-filter me-1',
            'aria-hidden' => 'true',
        ])
        . get_string('commerce_filters_apply', 'local_subscriptions'),
        [
            'type' => 'submit',
            'class' => 'btn crm-grant-action-primary ms-2',
        ]
    ),
    'crm-sales-filter-actions'
);
$filterbody .= html_writer::end_tag('form');

echo html_writer::tag(
    'details',
    html_writer::tag(
        'summary',
        html_writer::div(
            html_writer::tag('i', '', [
                'class' => 'fa fa-filter',
                'aria-hidden' => 'true',
            ])
            . html_writer::tag(
                'strong',
                get_string(
                    'commerce_offers_access_search_filters',
                    'local_subscriptions'
                )
            )
            . html_writer::span(
                $filtersareactive
                    ? get_string(
                        'commerce_sales_filters_active',
                        'local_subscriptions'
                    )
                    : get_string(
                        'commerce_sales_filters_collapsed_hint',
                        'local_subscriptions'
                    ),
                'crm-sales-filter-panel-status'
            ),
            'crm-sales-filter-panel-summary-copy'
        )
        . html_writer::tag('i', '', [
            'class' => 'fa fa-chevron-down crm-sales-filter-panel-chevron',
            'aria-hidden' => 'true',
        ]),
        ['class' => 'crm-sales-filter-panel-summary']
    )
    . html_writer::div(
        $filterbody,
        'crm-sales-filter-card crm-sales-filter-card-collapsible'
    ),
    [
        'class' => 'crm-sales-filter-panel crm-grants-filter-panel',
        'open' => $filtersareactive ? 'open' : null,
    ]
);

$PAGE->requires->js_init_code(<<<JS
(function() {
    var period = document.getElementById('grant-period');
    var custom = document.getElementById('grant-custom-period');
    if (!period || !custom) return;
    function sync() {
        custom.style.display = period.value === 'custom' ? '' : 'none';
    }
    period.addEventListener('change', sync);
    sync();
})();
JS);

$scopeparams = $urlparams;
unset($scopeparams['page'], $scopeparams['perpage']);

$removeurl = static function(
    array $params,
    string $name,
    mixed $reset = null
): moodle_url {
    if ($reset === null || $reset === '') {
        unset($params[$name]);
    } else {
        $params[$name] = $reset;
    }
    if ($name === 'period') {
        unset($params['datefrom'], $params['dateto']);
    }
    return new moodle_url(
        '/local/subscriptions/admin/commerce/grants/index.php',
        $params
    );
};

$scopepill = static function(
    string $label,
    ?moodle_url $remove = null
): string {
    $close = '';
    if ($remove !== null) {
        $close = html_writer::link(
            $remove,
            html_writer::span(
                '×',
                'crm-result-scope-pill-remove-symbol'
            ),
            [
                'class' => 'crm-result-scope-pill-remove',
                'aria-label' => get_string(
                    'commerce_result_scope_remove_filter_named',
                    'local_subscriptions',
                    $label
                ),
            ]
        );
    }
    return html_writer::span(
        html_writer::span(
            s($label),
            'crm-result-scope-pill-label'
        )
        . $close,
        'crm-result-scope-pill'
    );
};

$scopepills = [
    $scopepill(
        $grantperiodlabel($period, $timefrom, $timeto),
        $period !== '30'
            ? $removeurl($scopeparams, 'period', '30')
            : null
    ),
];
if ($query !== '') {
    $scopepills[] = $scopepill(
        get_string(
            'commerce_result_scope_search',
            'local_subscriptions',
            $query
        ),
        $removeurl($scopeparams, 'q')
    );
}
if ($status !== '') {
    $scopepills[] = $scopepill(
        get_string(
            'commerce_result_scope_status',
            'local_subscriptions',
            $statuslabels[$status] ?? $status
        ),
        $removeurl($scopeparams, 'status')
    );
}

echo html_writer::div(
    html_writer::div(
        get_string(
            'commerce_offers_access_grants_found',
            'local_subscriptions',
            $total
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
        . implode('', $scopepills),
        'crm-result-scope-pills'
    ),
    'crm-result-summary crm-grants-result-summary'
);

if ($grants === []) {
    echo CommerceOffersAccessPolishRenderer::empty_state(
        get_string('commerce_offers_access_grants_empty_title', 'local_subscriptions'),
        get_string('commerce_offers_access_grants_empty', 'local_subscriptions'),
        'fa-key',
        new moodle_url(
            '/local/subscriptions/admin/commerce/offers-access/create.php',
            ['kind' => 'grant']
        ),
        get_string('commerce_offers_access_grant_action', 'local_subscriptions')
    );
} else {
    $table = new html_table();
    $table->attributes['class'] =
        'generaltable table table-hover align-middle crm-offers-access-list-table';
    $table->head = [
        get_string('commerce_bulk_grant_customer', 'local_subscriptions'),
        get_string('commerce_offers_access_config_product', 'local_subscriptions'),
        get_string('status'),
        get_string('commerce_offers_access_validity', 'local_subscriptions'),
        get_string('commerce_offers_access_created', 'local_subscriptions'),
        get_string('actions'),
    ];

    foreach ($grants as $grant) {
        $userid = (int)($grant->beneficiaryuserid ?? 0);
        $user = $userid > 0 ? ($users[$userid] ?? null) : null;
        $name = $user ? fullname($user) : '';
        $client = html_writer::div(
            s($name !== '' ? $name : (string)$grant->beneficiaryemail),
            'crm-offers-access-client-name'
        );
        if ($name !== '') {
            $client .= html_writer::div(
                s((string)$grant->beneficiaryemail),
                'crm-offers-access-client-email'
            );
        }
        if ($userid > 0) {
            $client = html_writer::link(
                new moodle_url(
                    '/local/subscriptions/admin/users/view.php',
                    ['id' => $userid]
                ),
                $client,
                ['class' => 'crm-offers-access-client-link']
            );
        }

        $productname = $productnames[(string)$grant->productsku]
            ?? (string)$grant->productsku;
        $product = s($productname);
        $linkedproductid = $productids[(string)$grant->productsku] ?? 0;
        if ($linkedproductid > 0) {
            $product = html_writer::link(
                new moodle_url(
                    '/local/subscriptions/admin/commerce/products/view.php',
                    ['id' => $linkedproductid]
                ),
                s($productname),
                ['class' => 'crm-offers-access-product-link']
            );
        }

        $grantstatus = (string)$grant->status;
        $statuslabel = $statuslabels[$grantstatus] ?? $grantstatus;
        $statushtml = html_writer::span(
            s($statuslabel),
            'crm-offers-access-status ' . ($statusclasses[$grantstatus] ?? '')
        );

        $validity = userdate(
            (int)$grant->validfrom,
            get_string('strftimedate', 'langconfig')
        );
        if (!empty($grant->validuntil)) {
            $validity .= ' → ' . userdate(
                (int)$grant->validuntil,
                get_string('strftimedate', 'langconfig')
            );
        } else {
            $validity .= ' → ∞';
        }

        $detailurl = new moodle_url(
            '/local/subscriptions/admin/commerce/grants/view.php',
            ['id' => (int)$grant->id]
        );

        $table->data[] = [
            $client,
            $product,
            $statushtml,
            s($validity),
            html_writer::link(
                $detailurl,
                userdate(
                    (int)$grant->timecreated,
                    get_string('strftimedatetimeshort', 'langconfig')
                ),
                ['class' => 'crm-offers-access-date-link']
            ),
            (function() use (
                $detailurl,
                $userid,
                $grant
            ): string {
                $display = html_writer::link(
                    $detailurl,
                    html_writer::tag('i', '', [
                        'class' => 'fa fa-eye me-1',
                        'aria-hidden' => 'true',
                    ]) . get_string('view'),
                    ['class' => 'btn btn-sm crm-grant-action-outline']
                );

                $groups = [];
                $groups[] = html_writer::div(
                    html_writer::div(
                        get_string(
                            'commerce_grant_menu_grant',
                            'local_subscriptions'
                        ),
                        'crm-sales-row-menu-section'
                    )
                    . html_writer::link(
                        $detailurl,
                        get_string(
                            'commerce_grant_menu_details',
                            'local_subscriptions'
                        ),
                        ['class' => 'crm-sales-row-menu-link']
                    ),
                    'crm-sales-row-menu-group'
                );

                if ($userid > 0) {
                    $groups[] = html_writer::div(
                        html_writer::div(
                            get_string(
                                'commerce_grant_menu_client',
                                'local_subscriptions'
                            ),
                            'crm-sales-row-menu-section'
                        )
                        . html_writer::link(
                            new moodle_url(
                                '/local/subscriptions/admin/users/view.php',
                                ['id' => $userid]
                            ),
                            get_string(
                                'commerce_offers_access_config_open_user360',
                                'local_subscriptions'
                            ),
                            ['class' => 'crm-sales-row-menu-link']
                        ),
                        'crm-sales-row-menu-group'
                    );
                }

                $groups[] = html_writer::div(
                    html_writer::div(
                        get_string(
                            'commerce_grant_menu_communication',
                            'local_subscriptions'
                        ),
                        'crm-sales-row-menu-section'
                    )
                    . html_writer::link(
                        new moodle_url(
                            '/local/subscriptions/admin/commerce/mail/index.php',
                            ['q' => (string)$grant->beneficiaryemail]
                        ),
                        get_string(
                            'commerce_grant_menu_mail_journal',
                            'local_subscriptions'
                        ),
                        ['class' => 'crm-sales-row-menu-link']
                    ),
                    'crm-sales-row-menu-group'
                );

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
                            'aria-label' => get_string('actions'),
                        ]
                    )
                    . html_writer::div(
                        implode('', $groups),
                        'crm-sales-row-menu'
                    ),
                    ['class' => 'crm-sales-row-actions-menu']
                );

                return html_writer::div(
                    $display . $menu,
                    'crm-sales-actions crm-grants-actions'
                );
            })(),
        ];
    }

    echo html_writer::table($table);

    if ($total > $perpage) {
        echo $OUTPUT->paging_bar(
            $total,
            $page,
            $perpage,
            $filterurl,
            'page'
        );
    }
}

$campaignservice = new CommerceBulkGrantCampaignService($DB);
$campaigns = array_slice($campaignservice->campaigns(), 0, 5);
if ($campaigns !== []) {
    echo html_writer::div(
        html_writer::div(
            html_writer::tag(
                'h2',
                html_writer::tag('i', '', [
                    'class' => 'fa fa-clock-o me-2',
                    'aria-hidden' => 'true',
                ])
                . get_string(
                    'commerce_offers_access_recent_grant_campaigns',
                    'local_subscriptions'
                )
            )
            . html_writer::link(
                new moodle_url(
                    '/local/subscriptions/admin/commerce/offers-access/campaigns.php',
                    ['kind' => 'grant']
                ),
                get_string('commerce_offers_access_view_all', 'local_subscriptions'),
                ['class' => 'crm-offers-access-section-link']
            ),
            'crm-offers-access-section-heading'
        )
        . implode('', array_map(
            static function(\stdClass $campaign): string {
                return html_writer::link(
                    new moodle_url(
                        '/local/subscriptions/admin/commerce/grants/campaign_view.php',
                        ['id' => (int)$campaign->id]
                    ),
                    html_writer::div(
                        html_writer::span(
                            s((string)$campaign->name),
                            'crm-offers-access-recent-name'
                        )
                        . html_writer::span(
                            (int)$campaign->processedcount
                            . ' / '
                            . (int)$campaign->selectedcount,
                            'crm-offers-access-recent-meta'
                        ),
                        'crm-offers-access-recent-row'
                    ),
                    ['class' => 'crm-offers-access-recent-link']
                );
            },
            $campaigns
        )),
        'crm-offers-access-recent'
    );
}

$PAGE->requires->js_init_code(<<<JS
(function() {
    var menus = Array.prototype.slice.call(
        document.querySelectorAll(
            '.local-subscriptions-commerce-grants-page .crm-sales-row-actions-menu'
        )
    );
    menus.forEach(function(menu) {
        menu.addEventListener('toggle', function() {
            if (!menu.open) return;
            menus.forEach(function(other) {
                if (other !== menu) other.open = false;
            });
        });
    });
    document.addEventListener('click', function(event) {
        menus.forEach(function(menu) {
            if (menu.open && !menu.contains(event.target)) {
                menu.open = false;
            }
        });
    });
})();
JS);

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
