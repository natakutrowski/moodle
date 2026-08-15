<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\mail\admin\CommerceMailAdminContextResolver;
use local_subscriptions\commerce\mail\admin\CommerceMailAdminPresentation;
use local_subscriptions\commerce\mail\admin\CommerceMailAdminService;
use local_subscriptions\commerce\mail\admin\CommerceMailHealthRenderer;
use local_subscriptions\commerce\mail\admin\CommerceMailSectionNavigationRenderer;
use local_subscriptions\commerce\mail\certification\CommerceMailEngineCertificationService;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::VIEW_PAYMENTS);

$q = optional_param('q', '', PARAM_RAW_TRIMMED);
$status = optional_param('status', '', PARAM_ALPHANUMEXT);
$mailtype = optional_param('mailtype', '', PARAM_ALPHANUMEXT);
$language = optional_param('language', '', PARAM_ALPHANUMEXT);
$purchaseid = optional_param('purchaseid', 0, PARAM_INT);
$attempts = max(0, optional_param('attempts', 0, PARAM_INT));
$includeaudit = optional_param('includeaudit', 0, PARAM_BOOL) === 1;
$period = optional_param('period', '30', PARAM_ALPHANUMEXT);
$customfrom = optional_param('from', '', PARAM_RAW_TRIMMED);
$customto = optional_param('to', '', PARAM_RAW_TRIMMED);
$page = max(0, optional_param('page', 0, PARAM_INT));
$perpage = optional_param('perpage', 25, PARAM_INT);
$perpage = in_array($perpage, [25, 50, 100], true) ? $perpage : 25;
$sort = optional_param('sort', 'date', PARAM_ALPHA);
$direction = strtolower(optional_param('dir', 'desc', PARAM_ALPHA)) === 'asc' ? 'asc' : 'desc';

$availablecolumns = ['date', 'recipient', 'type', 'status', 'context', 'language', 'attempts', 'error'];
$requestedcolumns = optional_param_array('columns', [], PARAM_ALPHA);
$visiblecolumns = $requestedcolumns === []
    ? $availablecolumns
    : array_values(array_intersect($availablecolumns, $requestedcolumns));
if ($visiblecolumns === []) {
    $visiblecolumns = $availablecolumns;
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
} else if ($period === 'today') {
    $datefrom = usergetmidnight($now);
    $dateto = $now;
} else if ($period !== 'all') {
    $days = (int)$period;
    $days = in_array($days, [7, 30, 90, 365], true) ? $days : 30;
    $datefrom = $now - ($days * DAYSECS);
    $dateto = $now;
    $period = (string)$days;
}

$filters = [
    'q' => $q,
    'status' => $status,
    'mailtype' => $mailtype,
    'language' => $language,
    'purchaseid' => $purchaseid,
    'attempts' => $attempts,
    'includeaudit' => $includeaudit,
    'datefrom' => $datefrom,
    'dateto' => $dateto,
    'sort' => $sort,
    'dir' => $direction,
];

$service = new CommerceMailAdminService();
$result = $service->search($filters, $page, $perpage);

// AUDIT visibility affects the journal/export only. Business KPIs always
// exclude audit copies so toggling the checkbox cannot inflate operational data.
$kpifilters = $filters;
$kpifilters['includeaudit'] = false;
$statistics = $service->statistics($kpifilters);
$operationalstatistics = $service->operational_statistics($kpifilters, $now);
$contextresolver = new CommerceMailAdminContextResolver($DB);
$healthreport = (new CommerceMailEngineCertificationService($DB))->certify();

$baseparams = array_filter([
    'q' => $q,
    'status' => $status,
    'mailtype' => $mailtype,
    'language' => $language,
    'purchaseid' => $purchaseid ?: null,
    'attempts' => $attempts ?: null,
    'includeaudit' => $includeaudit ? 1 : null,
    'period' => $period,
    'from' => $period === 'custom' ? $customfrom : '',
    'to' => $period === 'custom' ? $customto : '',
    'perpage' => $perpage,
    'sort' => $sort,
    'dir' => $direction,
], static fn($value): bool => $value !== '' && $value !== null);
$baseparams['columns'] = $visiblecolumns;

$pageurl = new moodle_url('/local/subscriptions/admin/commerce/mail/index.php');
$url = new moodle_url('/local/subscriptions/admin/commerce/mail/index.php', $baseparams);
$title = get_string('commerce_mail_admin_title', 'local_subscriptions');
CrmPageConfigurator::configure($PAGE, $context, $url, $title, 'local-subscriptions-commerce-mail-page');
$PAGE->requires->css('/local/subscriptions/styles/commerce_mail_admin.css');

$filteroptions = ['' => get_string('commerce_mail_filter_all', 'local_subscriptions')];
$periodoptions = [
    'today' => get_string('commerce_mail_period_today', 'local_subscriptions'),
    '7' => get_string('commerce_mail_period_7', 'local_subscriptions'),
    '30' => get_string('commerce_mail_period_30', 'local_subscriptions'),
    '90' => get_string('commerce_mail_period_90', 'local_subscriptions'),
    '365' => get_string('commerce_mail_period_365', 'local_subscriptions'),
    'all' => get_string('commerce_mail_period_all', 'local_subscriptions'),
    'custom' => get_string('commerce_mail_period_custom', 'local_subscriptions'),
];

$columnlabels = [
    'date' => get_string('date', 'local_subscriptions'),
    'recipient' => get_string('commerce_mail_recipient_column', 'local_subscriptions'),
    'type' => get_string('type', 'local_subscriptions'),
    'status' => get_string('status', 'local_subscriptions'),
    'context' => get_string('commerce_mail_context_column', 'local_subscriptions'),
    'language' => get_string('language', 'local_subscriptions'),
    'attempts' => get_string('attempts', 'local_subscriptions'),
    'error' => get_string('commerce_mail_last_error', 'local_subscriptions'),
];

$exporturl = new moodle_url('/local/subscriptions/admin/commerce/mail/export.php', $baseparams);

// Search/filter workspace.
$form = html_writer::start_tag('form', [
    'method' => 'get',
    'action' => $pageurl->out(false),
    'class' => 'commerce-mail-filter-form',
]);
$form .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sort', 'value' => $sort]);
$form .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'dir', 'value' => $direction]);
$form .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'perpage', 'value' => $perpage]);

$form .= html_writer::start_div('commerce-mail-filter-grid');
$form .= html_writer::div(
    html_writer::label(get_string('search'), 'commerce-mail-q', false, ['class' => 'form-label'])
    . html_writer::div(
        html_writer::empty_tag('input', [
            'id' => 'commerce-mail-q', 'name' => 'q', 'type' => 'search', 'value' => $q,
            'class' => 'form-control',
            'placeholder' => get_string('commerce_mail_search_placeholder_n42', 'local_subscriptions'),
        ])
        . html_writer::tag('i', '', ['class' => 'fa fa-search commerce-mail-filter-search-icon', 'aria-hidden' => 'true']),
        'commerce-mail-filter-search-control'
    ),
    'commerce-mail-filter-field commerce-mail-filter-search'
);
$form .= html_writer::div(
    html_writer::label(get_string('commerce_mail_period', 'local_subscriptions'), 'commerce-mail-period', false, ['class' => 'form-label'])
    . html_writer::select($periodoptions, 'period', $period, false, ['id' => 'commerce-mail-period', 'class' => 'form-select']),
    'commerce-mail-filter-field'
);
$form .= html_writer::div(
    html_writer::label(get_string('commerce_mail_type_filter', 'local_subscriptions'), 'commerce-mail-type', false, ['class' => 'form-label'])
    . html_writer::select($filteroptions + CommerceMailAdminPresentation::type_options(), 'mailtype', $mailtype, false, ['id' => 'commerce-mail-type', 'class' => 'form-select']),
    'commerce-mail-filter-field'
);
$form .= html_writer::div(
    html_writer::label(get_string('commerce_mail_status_filter', 'local_subscriptions'), 'commerce-mail-status', false, ['class' => 'form-label'])
    . html_writer::select($filteroptions + CommerceMailAdminPresentation::status_options(), 'status', $status, false, ['id' => 'commerce-mail-status', 'class' => 'form-select']),
    'commerce-mail-filter-field'
);
$form .= html_writer::div(
    html_writer::label(get_string('commerce_mail_language_filter', 'local_subscriptions'), 'commerce-mail-language', false, ['class' => 'form-label'])
    . html_writer::select($filteroptions + CommerceMailAdminPresentation::language_options(), 'language', $language, false, ['id' => 'commerce-mail-language', 'class' => 'form-select']),
    'commerce-mail-filter-field'
);
$form .= html_writer::end_div();

$advanced = html_writer::start_div('commerce-mail-filter-advanced-grid');
$advanced .= html_writer::div(
    html_writer::label(get_string('commerce_mail_purchase_id', 'local_subscriptions'), 'commerce-mail-purchaseid', false, ['class' => 'form-label'])
    . html_writer::empty_tag('input', [
        'id' => 'commerce-mail-purchaseid', 'name' => 'purchaseid', 'type' => 'number',
        'min' => 1, 'value' => $purchaseid ?: '', 'class' => 'form-control',
    ]),
    'commerce-mail-filter-field'
);
$advanced .= html_writer::div(
    html_writer::label(get_string('commerce_mail_attempts_min', 'local_subscriptions'), 'commerce-mail-attempts', false, ['class' => 'form-label'])
    . html_writer::empty_tag('input', [
        'id' => 'commerce-mail-attempts', 'name' => 'attempts', 'type' => 'number',
        'min' => 0, 'value' => $attempts ?: '', 'class' => 'form-control',
    ]),
    'commerce-mail-filter-field'
);
$advanced .= html_writer::div(
    html_writer::label(get_string('commerce_mail_date_from', 'local_subscriptions'), 'commerce-mail-from', false, ['class' => 'form-label'])
    . html_writer::empty_tag('input', [
        'id' => 'commerce-mail-from', 'name' => 'from', 'type' => 'date', 'value' => $customfrom,
        'class' => 'form-control', 'lang' => current_language(),
    ]),
    'commerce-mail-filter-field'
);
$advanced .= html_writer::div(
    html_writer::label(get_string('commerce_mail_date_to', 'local_subscriptions'), 'commerce-mail-to', false, ['class' => 'form-label'])
    . html_writer::empty_tag('input', [
        'id' => 'commerce-mail-to', 'name' => 'to', 'type' => 'date', 'value' => $customto,
        'class' => 'form-control', 'lang' => current_language(),
    ]),
    'commerce-mail-filter-field'
);
$advanced .= html_writer::end_div();
$advancedopen = $purchaseid > 0 || $attempts > 0 || $period === 'custom';
$morefilters = html_writer::tag(
    'details',
    html_writer::tag('summary', html_writer::tag('i', '', ['class' => 'fa fa-sliders', 'aria-hidden' => 'true'])
        . get_string('commerce_mail_more_filters', 'local_subscriptions')) . $advanced,
    ['class' => 'commerce-mail-filter-advanced', 'open' => $advancedopen ? 'open' : null]
);

$columnitems = '';
foreach ($availablecolumns as $columnkey) {
    $id = 'commerce-mail-column-' . $columnkey;
    $columnitems .= html_writer::div(
        html_writer::empty_tag('input', [
            'type' => 'checkbox', 'id' => $id, 'name' => 'columns[]', 'value' => $columnkey,
            'checked' => in_array($columnkey, $visiblecolumns, true) ? 'checked' : null,
            'class' => 'form-check-input',
        ])
        . html_writer::tag('label', $columnlabels[$columnkey], ['for' => $id, 'class' => 'form-check-label']),
        'form-check'
    );
}
$columnpicker = html_writer::tag(
    'details',
    html_writer::tag('summary', html_writer::tag('i', '', ['class' => 'fa fa-columns', 'aria-hidden' => 'true'])
        . get_string('commerce_mail_columns', 'local_subscriptions'))
    . html_writer::div(
        $columnitems . html_writer::tag('button', get_string('commerce_mail_columns_apply', 'local_subscriptions'), [
            'type' => 'submit', 'class' => 'btn btn-sm btn-primary mt-2 w-100',
        ]),
        'commerce-mail-column-picker-menu'
    ),
    ['class' => 'commerce-mail-column-picker']
);

$auditcheckbox = html_writer::div(
    html_writer::empty_tag('input', [
        'type' => 'checkbox',
        'id' => 'commerce-mail-includeaudit',
        'name' => 'includeaudit',
        'value' => '1',
        'checked' => $includeaudit ? 'checked' : null,
        'class' => 'form-check-input',
    ])
    . html_writer::tag(
        'label',
        get_string('commerce_mail_include_audit', 'local_subscriptions'),
        [
            'for' => 'commerce-mail-includeaudit',
            'class' => 'form-check-label',
        ]
    ),
    'form-check commerce-mail-filter-audit-toggle'
);

$form .= html_writer::div(
    html_writer::div(
        $morefilters . $auditcheckbox,
        'commerce-mail-filter-footer-left'
    )
    . html_writer::div(
        $columnpicker
        . html_writer::link($exporturl, html_writer::tag('i', '', ['class' => 'fa fa-download', 'aria-hidden' => 'true'])
            . get_string('commerce_mail_export', 'local_subscriptions'), ['class' => 'btn btn-outline-secondary'])
        . html_writer::link($pageurl, get_string('reset'), ['class' => 'btn btn-outline-secondary'])
        . html_writer::tag('button', html_writer::tag('i', '', ['class' => 'fa fa-filter', 'aria-hidden' => 'true'])
            . get_string('filter'), ['type' => 'submit', 'class' => 'btn btn-primary']),
        'commerce-mail-filter-actions'
    ),
    'commerce-mail-filter-footer'
);
$form .= html_writer::end_tag('form');

$filtersactive = $q !== '' || $status !== '' || $mailtype !== '' || $language !== '' || $purchaseid > 0
    || $attempts > 0 || $includeaudit || $period !== '30' || $customfrom !== '' || $customto !== '';
$filterpanel = html_writer::tag(
    'details',
    html_writer::tag(
        'summary',
        html_writer::div(
            html_writer::tag('i', '', ['class' => 'fa fa-filter', 'aria-hidden' => 'true'])
            . html_writer::tag(
                'strong',
                get_string('commerce_mail_filters_title', 'local_subscriptions')
            )
            . html_writer::span(
                $filtersactive
                    ? get_string('commerce_mail_filters_active', 'local_subscriptions')
                    : get_string(
                        'commerce_mail_filters_collapsed_hint',
                        'local_subscriptions'
                    ),
                'commerce-mail-filter-panel-status'
            ),
            'commerce-mail-filter-panel-summary-copy'
        )
        . html_writer::tag('i', '', ['class' => 'fa fa-chevron-down commerce-mail-filter-panel-chevron', 'aria-hidden' => 'true']),
        ['class' => 'commerce-mail-filter-panel-summary']
    )
    . html_writer::div($form, 'commerce-mail-filter-panel-body'),
    [
        'class' => 'commerce-mail-filter-panel',
        'open' => $filtersactive ? 'open' : null,
    ]
);

// KPI strip: six operational signals, audits excluded unless explicitly requested.
$percent = static fn(int $value, int $total): string => $total > 0
    ? number_format(($value / $total) * 100, 1, ',', ' ') . '%'
    : '0%';
$lastoffercheck = (int)$operationalstatistics['lastoffercheck'];
$lastofferchecklabel = $lastoffercheck > 0
    ? get_string(
        'commerce_mail_kpi_last_check',
        'local_subscriptions',
        userdate($lastoffercheck, get_string('strftimetime', 'langconfig'))
    )
    : get_string('commerce_mail_kpi_last_check_never', 'local_subscriptions');

$kpis = [
    ['fa-paper-plane-o', 'commerce_mail_kpi_sent', $statistics['sent'], $percent($statistics['sent'], $statistics['total']), 'is-success'],
    ['fa-hourglass-half', 'commerce_mail_kpi_pending', $statistics['pending'], $percent($statistics['pending'], $statistics['total']), 'is-warning'],
    ['fa-times-circle-o', 'commerce_mail_kpi_failed', $statistics['failed'], $percent($statistics['failed'], $statistics['total']), 'is-danger'],
    ['fa-tag', 'commerce_mail_kpi_offers_sent', $operationalstatistics['offerssent'], $lastofferchecklabel, 'is-campaign'],
    ['fa-clock-o', 'commerce_mail_kpi_offers_pending', $operationalstatistics['offerspending'], $lastofferchecklabel, 'is-warning'],
    ['fa-tachometer', 'commerce_mail_kpi_sent_last_hour', $operationalstatistics['sentlasthour'], get_string('commerce_mail_kpi_smtp_load_help', 'local_subscriptions'), 'is-throughput'],
];
$kpihtml = '';
foreach ($kpis as [$icon, $labelkey, $value, $foot, $tone]) {
    $kpihtml .= html_writer::div(
        html_writer::div(html_writer::tag('i', '', ['class' => 'fa ' . $icon, 'aria-hidden' => 'true']), 'commerce-mail-kpi-icon ' . $tone)
        . html_writer::div(
            html_writer::div(get_string($labelkey, 'local_subscriptions'), 'commerce-mail-kpi-label')
            . html_writer::div((string)$value, 'commerce-mail-kpi-value')
            . html_writer::div($foot, 'commerce-mail-kpi-foot'),
            'commerce-mail-kpi-copy'
        ),
        'commerce-mail-kpi'
    );
}
$kpihtml = html_writer::div($kpihtml, 'commerce-mail-kpi-strip');

$sortkeys = [
    'date' => 'date', 'recipient' => 'recipient', 'type' => 'type', 'status' => 'status',
    'language' => 'language', 'attempts' => 'attempts', 'error' => 'error',
];
$sortableheader = static function(string $columnkey, string $label) use ($sortkeys, $sort, $direction, $baseparams, $pageurl): string {
    $sortkey = $sortkeys[$columnkey] ?? '';
    if ($sortkey === '') {
        return s($label);
    }
    $active = $sort === $sortkey;
    $nextdir = $active && $direction === 'asc' ? 'desc' : 'asc';
    $params = $baseparams;
    $params['sort'] = $sortkey;
    $params['dir'] = $nextdir;
    $params['page'] = 0;
    $icon = !$active ? 'fa-sort' : ($direction === 'asc' ? 'fa-sort-asc' : 'fa-sort-desc');
    return html_writer::link(
        new moodle_url($pageurl, $params),
        s($label) . html_writer::tag('i', '', ['class' => 'fa ' . $icon, 'aria-hidden' => 'true']),
        ['class' => 'commerce-mail-sort-link' . ($active ? ' is-active' : '')]
    );
};

$table = new html_table();
$table->attributes['class'] = 'generaltable table-hover align-middle commerce-mail-journal-table';
$table->head = [];
foreach ($visiblecolumns as $columnkey) {
    $table->head[] = $sortableheader($columnkey, $columnlabels[$columnkey]);
}
$table->head[] = get_string('actions', 'local_subscriptions');

foreach ($result['records'] as $record) {
    $resolved = $contextresolver->resolve($record);
    $viewurl = new moodle_url('/local/subscriptions/admin/commerce/mail/view.php', ['id' => $record->id]);

    $typebadge = html_writer::span(
        s(CommerceMailAdminPresentation::type_label((string)$record->mailtype)),
        'badge rounded-pill commerce-mail-type-badge ' . CommerceMailAdminPresentation::type_badge_class((string)$record->mailtype)
    );
    $statusbadge = html_writer::span(
        html_writer::tag('i', '', ['class' => 'fa fa-circle', 'aria-hidden' => 'true'])
        . s(CommerceMailAdminPresentation::status_label((string)$record->status)),
        'badge rounded-pill commerce-mail-status-badge ' . CommerceMailAdminPresentation::status_badge_class((string)$record->status)
    );

    $recipientlabel = trim((string)$record->recipientname) !== '' ? (string)$record->recipientname : (string)$record->recipientemail;
    $recipient = $resolved['recipienturl'] instanceof moodle_url
        ? html_writer::link($resolved['recipienturl'], s($recipientlabel), ['class' => 'commerce-mail-recipient-name'])
        : html_writer::span(s($recipientlabel), 'commerce-mail-recipient-name');
    if ((string)$record->recipientemail !== '') {
        $recipient .= html_writer::div(
            $resolved['recipienturl'] instanceof moodle_url
                ? html_writer::link($resolved['recipienturl'], s((string)$record->recipientemail), ['class' => 'commerce-mail-recipient-email'])
                : s((string)$record->recipientemail),
            'commerce-mail-recipient-email-row'
        );
    }

    $contextcell = '—';
    if ((string)$resolved['contexttitle'] !== '') {
        $contexttitle = $resolved['contexturl'] instanceof moodle_url
            ? html_writer::link($resolved['contexturl'], s((string)$resolved['contexttitle']), ['class' => 'commerce-mail-context-title'])
            : html_writer::span(s((string)$resolved['contexttitle']), 'commerce-mail-context-title');
        $contextcell = $contexttitle;
        if ((string)$resolved['contextsubtitle'] !== '') {
            $subtitle = $resolved['producturl'] instanceof moodle_url
                ? html_writer::link($resolved['producturl'], s((string)$resolved['contextsubtitle']), ['class' => 'commerce-mail-context-product'])
                : s((string)$resolved['contextsubtitle']);
            $contextcell .= html_writer::div($subtitle, 'commerce-mail-context-subtitle');
        }
    } else if (!empty($record->purchaseid)) {
        $contextcell = $resolved['purchaseurl'] instanceof moodle_url
            ? html_writer::link($resolved['purchaseurl'], get_string('commerce_mail_context_order', 'local_subscriptions', (int)$record->purchaseid), ['class' => 'commerce-mail-context-title'])
            : '#' . (int)$record->purchaseid;
    }

    $lasterror = trim((string)($record->lasterror ?? ''));
    $errorcell = $lasterror === ''
        ? '—'
        : html_writer::span(s(shorten_text($lasterror, 65)), 'commerce-mail-error-text', ['title' => $lasterror]);

    $actions = html_writer::link(
        $viewurl,
        html_writer::tag(
            'i',
            '',
            ['class' => 'fa fa-eye', 'aria-hidden' => 'true']
        ) . get_string('view'),
        [
            'class' => 'btn btn-sm btn-outline-primary '
                . 'commerce-mail-view-button',
        ]
    );

    $sections = [
        'message' => [],
        'delivery' => [],
        'context' => [],
    ];

    $sections['message'][] = html_writer::link(
        new moodle_url(
            '/local/subscriptions/admin/commerce/mail/view.php',
            ['id' => $record->id, 'view' => 'desktop']
        ),
        html_writer::tag('i', '', [
            'class' => 'fa fa-desktop',
            'aria-hidden' => 'true',
        ]) . html_writer::span(
            get_string('commerce_mail_action_preview', 'local_subscriptions')
        ),
        ['class' => 'commerce-mail-row-menu-link']
    );

    if ((string)$record->status === 'queued'
            && has_capability(
                Capabilities::MANAGE_SUBSCRIPTIONS,
                $context
            )) {
        $sections['delivery'][] = html_writer::link(
            new moodle_url(
                '/local/subscriptions/admin/commerce/mail/action.php',
                [
                    'id' => $record->id,
                    'action' => 'sendnow',
                    'sesskey' => sesskey(),
                ]
            ),
            html_writer::tag('i', '', [
                'class' => 'fa fa-bolt',
                'aria-hidden' => 'true',
            ]) . html_writer::span(
                get_string('commerce_mail_send_now', 'local_subscriptions')
            ),
            [
                'class' => 'commerce-mail-row-menu-link '
                    . 'is-primary-action',
            ]
        );
    }

    if ((string)$record->status === 'sent'
            && has_capability(
                Capabilities::MANAGE_SUBSCRIPTIONS,
                $context
            )) {
        $sections['delivery'][] = html_writer::link(
            new moodle_url(
                '/local/subscriptions/admin/commerce/mail/action.php',
                [
                    'id' => $record->id,
                    'action' => 'resend',
                    'sesskey' => sesskey(),
                ]
            ),
            html_writer::tag('i', '', [
                'class' => 'fa fa-paper-plane-o',
                'aria-hidden' => 'true',
            ]) . html_writer::span(
                get_string('commerce_mail_resend', 'local_subscriptions')
            ),
            ['class' => 'commerce-mail-row-menu-link']
        );
    }

    if ((string)$record->status === 'failed'
            && has_capability(
                Capabilities::MANAGE_SUBSCRIPTIONS,
                $context
            )) {
        $sections['delivery'][] = html_writer::link(
            new moodle_url(
                '/local/subscriptions/admin/commerce/mail/action.php',
                [
                    'id' => $record->id,
                    'action' => 'retry',
                    'sesskey' => sesskey(),
                ]
            ),
            html_writer::tag('i', '', [
                'class' => 'fa fa-repeat',
                'aria-hidden' => 'true',
            ]) . html_writer::span(
                get_string('retry', 'local_subscriptions')
            ),
            ['class' => 'commerce-mail-row-menu-link']
        );
    }

    if ($resolved['purchaseurl'] instanceof moodle_url) {
        $sections['context'][] = html_writer::link(
            $resolved['purchaseurl'],
            html_writer::tag('i', '', [
                'class' => 'fa fa-shopping-cart',
                'aria-hidden' => 'true',
            ]) . html_writer::span(
                get_string(
                    'commerce_mail_action_open_order',
                    'local_subscriptions'
                )
            ),
            ['class' => 'commerce-mail-row-menu-link']
        );
    }

    if ($resolved['contexturl'] instanceof moodle_url
            && (string)$record->mailtype === 'personal_offer') {
        $sections['context'][] = html_writer::link(
            $resolved['contexturl'],
            html_writer::tag('i', '', [
                'class' => 'fa fa-tag',
                'aria-hidden' => 'true',
            ]) . html_writer::span(
                get_string(
                    'commerce_mail_action_open_context',
                    'local_subscriptions'
                )
            ),
            ['class' => 'commerce-mail-row-menu-link']
        );
    }

    $sectionlabels = [
        'message' => 'commerce_mail_actions_message',
        'delivery' => 'commerce_mail_actions_delivery',
        'context' => 'commerce_mail_actions_context',
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
            'commerce-mail-row-menu-section'
        );
        array_push($menuitems, ...$items);
    }

    $actions .= html_writer::tag(
        'details',
        html_writer::tag(
            'summary',
            html_writer::tag('i', '', [
                'class' => 'fa fa-ellipsis-h',
                'aria-hidden' => 'true',
            ]),
            [
                'class' => 'btn btn-sm btn-outline-secondary '
                    . 'commerce-mail-row-menu-toggle',
                'title' => get_string(
                    'commerce_mail_more_actions',
                    'local_subscriptions'
                ),
            ]
        )
        . html_writer::div(
            implode('', $menuitems),
            'commerce-mail-row-menu'
        ),
        ['class' => 'commerce-mail-row-actions-menu']
    );
    $actions = html_writer::div(
        $actions,
        'commerce-mail-row-actions'
    );

    $cells = [
        'date' => html_writer::div(userdate((int)$record->timecreated, get_string('strftimedate', 'langconfig')), 'commerce-mail-date')
            . html_writer::div(userdate((int)$record->timecreated, get_string('strftimetime', 'langconfig')), 'commerce-mail-time'),
        'recipient' => $recipient,
        'type' => $typebadge,
        'status' => $statusbadge,
        'context' => $contextcell,
        'language' => html_writer::span(
            s(CommerceMailAdminPresentation::language_flag(
                (string)$record->language
            )),
            'commerce-mail-language-flag',
            [
                'title' => CommerceMailAdminPresentation::language_label(
                    (string)$record->language
                ),
                'aria-label' => CommerceMailAdminPresentation::language_label(
                    (string)$record->language
                ),
            ]
        ),
        'attempts' => html_writer::span((int)$record->attemptcount . '/' . (int)$record->maxattempts, 'commerce-mail-attempts'),
        'error' => $errorcell,
    ];
    $row = [];
    foreach ($visiblecolumns as $columnkey) {
        $row[] = $cells[$columnkey];
    }
    $row[] = $actions;
    $table->data[] = $row;
}

$toolbar = html_writer::start_tag('form', ['method' => 'get', 'class' => 'commerce-mail-table-toolbar']);
foreach ($baseparams as $name => $value) {
    if ($name === 'perpage') {
        continue;
    }
    if (is_array($value)) {
        foreach ($value as $item) {
            $toolbar .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $name . '[]', 'value' => $item]);
        }
    } else {
        $toolbar .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $name, 'value' => $value]);
    }
}
$toolbar .= html_writer::tag('strong', get_string('commerce_mail_found', 'local_subscriptions', $result['total']), ['class' => 'commerce-mail-found']);
$toolbar .= html_writer::div(
    html_writer::label(get_string('commerce_mail_per_page', 'local_subscriptions'), 'commerce-mail-perpage', false, ['class' => 'form-label mb-0'])
    . html_writer::select([25 => '25', 50 => '50', 100 => '100'], 'perpage', $perpage, false, [
        'id' => 'commerce-mail-perpage', 'class' => 'form-select form-select-sm', 'onchange' => 'this.form.submit()',
    ]),
    'commerce-mail-perpage'
);
$toolbar .= html_writer::end_tag('form');

$tablecard = html_writer::div(
    $toolbar
    . html_writer::div(html_writer::table($table), 'commerce-mail-table-scroll')
    . html_writer::div($OUTPUT->paging_bar($result['total'], $page, $perpage, $url), 'commerce-mail-pagination'),
    'commerce-mail-table-card'
);

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    ['label' => get_string('crm_commerce_title', 'local_subscriptions'), 'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php')],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render($title, get_string('commerce_mail_admin_description_n41', 'local_subscriptions'), HelpContext::COMMERCE);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::MAIL);
echo html_writer::div(
    CommerceMailSectionNavigationRenderer::render(CommerceMailSectionNavigationRenderer::JOURNAL)
    . CommerceMailHealthRenderer::render_compact($healthreport),
    'commerce-mail-workspace-nav-row'
);
echo $kpihtml;
echo $filterpanel;
echo $tablecard;
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
