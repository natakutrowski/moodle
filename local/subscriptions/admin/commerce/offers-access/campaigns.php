<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\grant\CommerceBulkGrantCampaignService;
use local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferCampaignManager;
use local_subscriptions\crm\commerce\rendering\CommerceOffersAccessNavigationRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceOffersAccessPolishRenderer;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::VIEW_PAYMENTS);

$query = trim(optional_param('q', '', PARAM_RAW_TRIMMED));
$kind = trim(optional_param('kind', '', PARAM_ALPHA));
$state = trim(optional_param('state', '', PARAM_ALPHA));
$period = trim(optional_param('period', '90', PARAM_ALPHANUMEXT));
$customfrom = trim(optional_param('datefrom', '', PARAM_RAW_TRIMMED));
$customto = trim(optional_param('dateto', '', PARAM_RAW_TRIMMED));

if (!in_array($kind, ['', 'offer', 'grant'], true)) {
    $kind = '';
}
if (!in_array($state, ['', 'active', 'completed', 'error'], true)) {
    $state = '';
}
if (!in_array($period, ['today', '7', '30', '90', '365', 'all', 'custom'], true)) {
    $period = '90';
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
    $days = in_array($days, [7, 30, 90, 365], true) ? $days : 90;
    $timefrom = $now - ($days * DAYSECS);
    $timeto = $now;
    $period = (string)$days;
}

$url = new moodle_url(
    '/local/subscriptions/admin/commerce/offers-access/campaigns.php',
    array_filter([
        'q' => $query,
        'kind' => $kind,
        'state' => $state,
        'period' => $period,
        'datefrom' => $customfrom,
        'dateto' => $customto,
    ], static fn(mixed $value): bool => $value !== '')
);
$title = get_string('commerce_offers_access_campaigns_title', 'local_subscriptions');

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $url,
    $title,
    'local-subscriptions-commerce-offers-access-campaigns-page'
);

$rows = [];
$offercampaignmanager = CommercePersonalOfferCampaignManager::create($DB);

foreach ($offercampaignmanager->list_campaigns() as $campaign) {
    $status = (string)$campaign->status;
    $offersummary = $offercampaignmanager->summary((int)$campaign->id);
    $coarsestate = ((int)($offersummary['error'] ?? 0)) > 0
        ? 'error'
        : match ($status) {
            'closed' => 'completed',
            default => 'active',
        };
    $selected = (int)$campaign->selectedcount;
    $processed = (int)(($offersummary['issued'] ?? 0) + ($offersummary['replayed'] ?? 0));
    $progress = $selected > 0 ? min(100, (int)round(($processed / $selected) * 100)) : 0;

    $rows[] = (object)[
        'id' => (int)$campaign->id,
        'name' => (string)$campaign->name,
        'kind' => 'offer',
        'audience' => $selected,
        'processed' => $processed,
        'progresspercent' => $progress,
        'status' => $status,
        'state' => $coarsestate,
        'timemodified' => (int)$campaign->timemodified,
        'url' => new moodle_url(
            '/local/subscriptions/admin/commerce/personal-offers/campaign_view.php',
            ['id' => (int)$campaign->id]
        ),
    ];
}

foreach ((new CommerceBulkGrantCampaignService($DB))->campaigns() as $campaign) {
    $status = (string)$campaign->status;
    $coarsestate = match ($status) {
        CommerceBulkGrantCampaignService::STATUS_COMPLETED => 'completed',
        CommerceBulkGrantCampaignService::STATUS_COMPLETED_ERRORS => 'error',
        default => 'active',
    };
    $selected = (int)$campaign->selectedcount;
    $processed = (int)$campaign->processedcount;
    $progress = $selected > 0
        ? min(100, (int)round(($processed / $selected) * 100))
        : 0;

    $rows[] = (object)[
        'id' => (int)$campaign->id,
        'name' => (string)$campaign->name,
        'kind' => 'grant',
        'audience' => $selected,
        'processed' => $processed,
        'progresspercent' => $progress,
        'status' => $status,
        'state' => $coarsestate,
        'timemodified' => (int)$campaign->timemodified,
        'url' => new moodle_url(
            '/local/subscriptions/admin/commerce/grants/campaign_view.php',
            ['id' => (int)$campaign->id]
        ),
    ];
}

usort(
    $rows,
    static fn(\stdClass $a, \stdClass $b): int =>
        $b->timemodified <=> $a->timemodified
);

$allrows = $rows;
$rows = array_values(array_filter(
    $rows,
    static function(\stdClass $row) use (
        $query,
        $kind,
        $state,
        $timefrom,
        $timeto
    ): bool {
        if ($query !== '' && stripos($row->name, $query) === false) {
            return false;
        }
        if ($kind !== '' && $row->kind !== $kind) {
            return false;
        }
        if ($state !== '' && $row->state !== $state) {
            return false;
        }
        if ($timefrom > 0 && $row->timemodified < $timefrom) {
            return false;
        }
        if ($timeto > 0 && $row->timemodified > $timeto) {
            return false;
        }
        return true;
    }
));

$counts = [
    'total' => 0,
    'active' => 0,
    'completed' => 0,
    'error' => 0,
];
foreach ($allrows as $row) {
    if ($timefrom > 0 && $row->timemodified < $timefrom) {
        continue;
    }
    if ($timeto > 0 && $row->timemodified > $timeto) {
        continue;
    }
    $counts['total']++;
    if (isset($counts[$row->state])) {
        $counts[$row->state]++;
    }
}

$periodoptions = [
    'today' => get_string('commerce_sales_period_today', 'local_subscriptions'),
    '7' => get_string('commerce_sales_period_7', 'local_subscriptions'),
    '30' => get_string('commerce_sales_period_30', 'local_subscriptions'),
    '90' => get_string('commerce_sales_period_90', 'local_subscriptions'),
    '365' => get_string('commerce_sales_period_365', 'local_subscriptions'),
    'all' => get_string('commerce_sales_period_all', 'local_subscriptions'),
    'custom' => get_string('commerce_sales_period_custom', 'local_subscriptions'),
];

$campaignperiodlabel = static function(
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

$kindoptions = [
    '' => get_string('all'),
    'offer' => get_string('commerce_offers_access_kind_offer', 'local_subscriptions'),
    'grant' => get_string('commerce_offers_access_kind_grant', 'local_subscriptions'),
];

$stateoptions = [
    '' => get_string('all'),
    'active' => get_string('commerce_offers_access_campaign_state_active', 'local_subscriptions'),
    'completed' => get_string('commerce_offers_access_campaign_state_completed', 'local_subscriptions'),
    'error' => get_string('commerce_offers_access_campaign_state_error', 'local_subscriptions'),
];

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
        'commerce_offers_access_campaigns_description',
        'local_subscriptions'
    ),
    HelpContext::COMMERCE
);
echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::OFFERS_ACCESS,
    $context
);
echo CommerceOffersAccessNavigationRenderer::render(
    CommerceOffersAccessNavigationRenderer::CAMPAIGNS
);

if (has_capability(Capabilities::MANAGE_CRM_ADMIN_TOOLS, $context)) {
    echo html_writer::div(
        html_writer::link(
            new moodle_url(
                '/local/subscriptions/admin/commerce/offers-access/create.php',
                ['audience' => 'many']
            ),
            html_writer::tag('i', '', [
                'class' => 'fa fa-plus me-1',
                'aria-hidden' => 'true',
            ])
            . get_string(
                'commerce_offers_access_new_campaign',
                'local_subscriptions'
            ),
            ['class' => 'btn crm-campaign-action-primary']
        ),
        'crm-offers-access-list-actions'
    );
}

echo html_writer::div(
    html_writer::div(
        html_writer::div((string)$counts['total'], 'crm-offers-access-kpi-value')
        . html_writer::div(
            get_string('commerce_offers_access_campaign_kpi_total', 'local_subscriptions'),
            'crm-offers-access-kpi-label'
        ),
        'crm-offers-access-kpi is-neutral'
    )
    . html_writer::div(
        html_writer::div((string)$counts['active'], 'crm-offers-access-kpi-value')
        . html_writer::div(
            get_string('commerce_offers_access_campaign_kpi_active', 'local_subscriptions'),
            'crm-offers-access-kpi-label'
        ),
        'crm-offers-access-kpi is-warning'
    )
    . html_writer::div(
        html_writer::div((string)$counts['completed'], 'crm-offers-access-kpi-value')
        . html_writer::div(
            get_string('commerce_offers_access_campaign_kpi_completed', 'local_subscriptions'),
            'crm-offers-access-kpi-label'
        ),
        'crm-offers-access-kpi is-success'
    )
    . html_writer::div(
        html_writer::div((string)$counts['error'], 'crm-offers-access-kpi-value')
        . html_writer::div(
            get_string('commerce_offers_access_campaign_kpi_error', 'local_subscriptions'),
            'crm-offers-access-kpi-label'
        ),
        'crm-offers-access-kpi is-error'
    ),
    'crm-offers-access-kpis'
);

$filterurl = new moodle_url(
    '/local/subscriptions/admin/commerce/offers-access/campaigns.php'
);
$filtersareactive = $query !== ''
    || $kind !== ''
    || $state !== ''
    || $period !== '90'
    || $customfrom !== ''
    || $customto !== '';

$filterbody = html_writer::start_tag('form', [
    'method' => 'get',
    'action' => $filterurl->out(false),
    'class' => 'crm-sales-filter-form crm-campaigns-filter-form',
]);
$filterbody .= html_writer::start_div('crm-sales-filter-grid');
$filterbody .= html_writer::div(
    html_writer::label(
        get_string('search'),
        'campaign-query',
        false,
        ['class' => 'form-label']
    )
    . html_writer::div(
        html_writer::empty_tag('input', [
            'id' => 'campaign-query',
            'type' => 'search',
            'name' => 'q',
            'value' => $query,
            'class' => 'form-control',
            'placeholder' => get_string(
                'commerce_offers_access_campaign_search_placeholder',
                'local_subscriptions'
            ),
        ]),
        'crm-sales-filter-search-control'
    ),
    'crm-sales-filter-field crm-sales-filter-search'
);
$filterbody .= html_writer::div(
    html_writer::label(
        get_string('commerce_offers_access_campaign_type', 'local_subscriptions'),
        'campaign-kind',
        false,
        ['class' => 'form-label']
    )
    . html_writer::select(
        $kindoptions,
        'kind',
        $kind,
        false,
        ['id' => 'campaign-kind', 'class' => 'form-select']
    ),
    'crm-sales-filter-field'
);
$filterbody .= html_writer::div(
    html_writer::label(
        get_string('status'),
        'campaign-state',
        false,
        ['class' => 'form-label']
    )
    . html_writer::select(
        $stateoptions,
        'state',
        $state,
        false,
        ['id' => 'campaign-state', 'class' => 'form-select']
    ),
    'crm-sales-filter-field'
);
$filterbody .= html_writer::div(
    html_writer::label(
        get_string('commerce_offers_access_period', 'local_subscriptions'),
        'campaign-period',
        false,
        ['class' => 'form-label']
    )
    . html_writer::select(
        $periodoptions,
        'period',
        $period,
        false,
        ['id' => 'campaign-period', 'class' => 'form-select']
    ),
    'crm-sales-filter-field'
);
$filterbody .= html_writer::end_div();

$customstyle = $period === 'custom' ? '' : 'display:none;';
$filterbody .= html_writer::div(
    html_writer::div(
        html_writer::label(
            get_string('commerce_sales_date_from', 'local_subscriptions'),
            'campaign-datefrom',
            false,
            ['class' => 'form-label']
        )
        . html_writer::empty_tag('input', [
            'id' => 'campaign-datefrom',
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
            'campaign-dateto',
            false,
            ['class' => 'form-label']
        )
        . html_writer::empty_tag('input', [
            'id' => 'campaign-dateto',
            'name' => 'dateto',
            'type' => 'date',
            'value' => $customto,
            'class' => 'form-control',
        ]),
        'crm-sales-filter-field'
    ),
    'crm-sales-filter-custom-period',
    ['id' => 'campaign-custom-period', 'style' => $customstyle]
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
            'class' => 'btn crm-campaign-action-primary ms-2',
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
                get_string('commerce_offers_access_search_filters', 'local_subscriptions')
            )
            . html_writer::span(
                $filtersareactive
                    ? get_string('commerce_sales_filters_active', 'local_subscriptions')
                    : get_string('commerce_sales_filters_collapsed_hint', 'local_subscriptions'),
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
        'class' => 'crm-sales-filter-panel crm-campaigns-filter-panel',
        'open' => $filtersareactive ? 'open' : null,
    ]
);

$PAGE->requires->js_init_code(<<<JS
(function() {
    var period = document.getElementById('campaign-period');
    var custom = document.getElementById('campaign-custom-period');
    if (!period || !custom) return;
    function sync() {
        custom.style.display = period.value === 'custom' ? '' : 'none';
    }
    period.addEventListener('change', sync);
    sync();
})();
JS);

$scopeparams = [
    'q' => $query,
    'kind' => $kind,
    'state' => $state,
    'period' => $period,
    'datefrom' => $customfrom,
    'dateto' => $customto,
];

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
        '/local/subscriptions/admin/commerce/offers-access/campaigns.php',
        array_filter(
            $params,
            static fn(mixed $value): bool => $value !== ''
        )
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
            html_writer::span('×', 'crm-result-scope-pill-remove-symbol'),
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
        html_writer::span(s($label), 'crm-result-scope-pill-label')
        . $close,
        'crm-result-scope-pill'
    );
};

$scopepills = [
    $scopepill(
        $campaignperiodlabel($period, $timefrom, $timeto),
        $period !== '90'
            ? $removeurl($scopeparams, 'period', '90')
            : null
    ),
];
if ($query !== '') {
    $scopepills[] = $scopepill(
        get_string('commerce_result_scope_search', 'local_subscriptions', $query),
        $removeurl($scopeparams, 'q')
    );
}
if ($kind !== '') {
    $scopepills[] = $scopepill(
        get_string(
            'commerce_campaign_scope_type',
            'local_subscriptions',
            $kindoptions[$kind]
        ),
        $removeurl($scopeparams, 'kind')
    );
}
if ($state !== '') {
    $scopepills[] = $scopepill(
        get_string(
            'commerce_result_scope_status',
            'local_subscriptions',
            $stateoptions[$state]
        ),
        $removeurl($scopeparams, 'state')
    );
}

echo html_writer::div(
    html_writer::div(
        get_string(
            'commerce_offers_access_campaigns_found',
            'local_subscriptions',
            count($rows)
        ),
        'crm-sales-table-count'
    )
    . html_writer::div(
        html_writer::span(
            get_string('commerce_result_scope_label', 'local_subscriptions'),
            'crm-result-scope-label'
        )
        . implode('', $scopepills),
        'crm-result-scope-pills'
    ),
    'crm-result-summary crm-campaigns-result-summary'
);

if ($rows === []) {
    echo CommerceOffersAccessPolishRenderer::empty_state(
        get_string('commerce_offers_access_campaigns_empty_title', 'local_subscriptions'),
        get_string('commerce_offers_access_campaigns_empty', 'local_subscriptions'),
        'fa-users',
        has_capability(Capabilities::MANAGE_CRM_ADMIN_TOOLS, $context)
            ? new moodle_url(
                '/local/subscriptions/admin/commerce/offers-access/create.php',
                ['audience' => 'many']
            )
            : null,
        get_string('commerce_offers_access_new_campaign', 'local_subscriptions')
    );
} else {
    $table = new html_table();
    $table->attributes['class'] =
        'generaltable table table-hover align-middle crm-offers-access-list-table';
    $table->head = [
        get_string('name'),
        get_string('commerce_offers_access_campaign_type', 'local_subscriptions'),
        get_string('commerce_offers_access_campaign_audience', 'local_subscriptions'),
        get_string('commerce_offers_access_campaign_processing', 'local_subscriptions'),
        get_string('status'),
        get_string('modified'),
        get_string('actions'),
    ];

    foreach ($rows as $row) {
        $kindlabel = get_string(
            $row->kind === 'offer'
                ? 'commerce_offers_access_kind_offer'
                : 'commerce_offers_access_kind_grant',
            'local_subscriptions'
        );
        $statuskey = $row->kind === 'offer'
            ? 'commerce_personal_offer_campaign_status_' . $row->status
            : 'commerce_bulk_grant_campaign_status_' . $row->status;
        $statuslabel = get_string_manager()->string_exists(
            $statuskey,
            'local_subscriptions'
        ) ? get_string($statuskey, 'local_subscriptions') : $row->status;

        $progress = html_writer::div(
            html_writer::div(
                html_writer::tag('i', '', [
                    'class' => 'fa fa-users me-1',
                    'aria-hidden' => 'true',
                ])
                . get_string(
                    'commerce_campaign_beneficiaries_processed',
                    'local_subscriptions',
                    (object)[
                        'processed' => $row->processed,
                        'total' => $row->audience,
                    ]
                ),
                'crm-campaign-processing-copy'
            )
            . html_writer::div(
                html_writer::div(
                    '',
                    'crm-offers-access-progress-bar',
                    [
                        'style' => 'width:' . (int)$row->progresspercent . '%;',
                    ]
                ),
                'crm-offers-access-progress-track'
            ),
            'crm-campaign-processing'
        );

        $stateclass = match ($row->state) {
            'completed' => 'is-success',
            'error' => 'is-error',
            default => 'is-warning',
        };

        $table->data[] = [
            html_writer::link(
                $row->url,
                s($row->name),
                ['class' => 'crm-offers-access-name-link']
            ),
            html_writer::span(
                html_writer::tag('i', '', [
                    'class' => 'fa '
                        . ($row->kind === 'offer' ? 'fa-tag' : 'fa-key')
                        . ' me-1',
                    'aria-hidden' => 'true',
                ])
                . $kindlabel,
                'crm-offers-access-kind is-' . $row->kind
            ),
            (string)$row->audience,
            $progress,
            html_writer::span(
                s($statuslabel),
                'crm-offers-access-status ' . $stateclass
            ),
            userdate(
                $row->timemodified,
                get_string('strftimedatetimeshort', 'langconfig')
            ),
            (function() use ($row): string {
                $display = html_writer::link(
                    $row->url,
                    html_writer::tag('i', '', [
                        'class' => 'fa fa-eye me-1',
                        'aria-hidden' => 'true',
                    ]) . get_string('view'),
                    ['class' => 'btn btn-sm crm-campaign-action-outline']
                );

                $groups = [];
                $campaignlinks = html_writer::link(
                    $row->url,
                    html_writer::tag('i', '', [
                        'class' => 'fa fa-eye me-2',
                        'aria-hidden' => 'true',
                    ]) . get_string('view'),
                    ['class' => 'crm-sales-row-menu-link']
                );

                if ($row->kind === 'offer') {
                    $campaignlinks .= html_writer::link(
                        new moodle_url(
                            '/local/subscriptions/admin/commerce/personal-offers/campaign_email.php',
                            ['id' => $row->id]
                        ),
                        html_writer::tag('i', '', [
                            'class' => 'fa fa-envelope-o me-2',
                            'aria-hidden' => 'true',
                        ])
                        . get_string(
                            'commerce_campaign_menu_email',
                            'local_subscriptions'
                        ),
                        ['class' => 'crm-sales-row-menu-link']
                    );
                    $campaignlinks .= html_writer::link(
                        new moodle_url(
                            '/local/subscriptions/admin/commerce/personal-offers/campaign_email_preview.php',
                            ['id' => $row->id]
                        ),
                        html_writer::tag('i', '', [
                            'class' => 'fa fa-eye me-2',
                            'aria-hidden' => 'true',
                        ])
                        . get_string(
                            'commerce_campaign_menu_preview_email',
                            'local_subscriptions'
                        ),
                        ['class' => 'crm-sales-row-menu-link']
                    );
                } else {
                    $campaignlinks .= html_writer::link(
                        new moodle_url(
                            '/local/subscriptions/admin/commerce/mail/index.php',
                            ['q' => $row->name]
                        ),
                        html_writer::tag('i', '', [
                            'class' => 'fa fa-envelope-o me-2',
                            'aria-hidden' => 'true',
                        ])
                        . get_string(
                            'commerce_campaign_menu_mail_journal',
                            'local_subscriptions'
                        ),
                        ['class' => 'crm-sales-row-menu-link']
                    );
                }

                $groups[] = html_writer::div(
                    html_writer::div(
                        get_string(
                            'commerce_offers_access_campaign_menu_campaign',
                            'local_subscriptions'
                        ),
                        'crm-sales-row-menu-section'
                    )
                    . $campaignlinks,
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
                            'title' => get_string('actions'),
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
                    'crm-sales-actions crm-campaign-actions'
                );
            })(),
        ];
    }

    echo html_writer::table($table);
}

$PAGE->requires->js_init_code(<<<JS
(function() {
    var menus = Array.prototype.slice.call(document.querySelectorAll('.local-subscriptions-commerce-offers-access-campaigns-page .crm-sales-row-actions-menu'));
    menus.forEach(function(menu) { menu.addEventListener('toggle', function() { if (!menu.open) return; menus.forEach(function(other) { if (other !== menu) other.open = false; }); }); });
    document.addEventListener('click', function(event) { menus.forEach(function(menu) { if (menu.open && !menu.contains(event.target)) menu.open = false; }); });
})();
JS);

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
