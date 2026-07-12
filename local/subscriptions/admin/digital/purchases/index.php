<?php

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir . '/excellib.class.php');
require_once($CFG->libdir . '/clilib.php');

use local_subscriptions\subscription_config;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\admin\AdminNavigation;
use local_subscriptions\admin\AdminFormatter;
use local_subscriptions\admin\AdminEntityLinks;
use local_subscriptions\support\DigitalPresenter;
use local_subscriptions\digital\DigitalPurchaseAdminFilter;
use local_subscriptions\digital\repositories\DigitalPurchaseAdminRepository;
use local_subscriptions\crm\user\email\UserEmailPresetBuilder;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;

$context = AdminSecurity::require(Capabilities::VIEW_DIGITAL);
$canmanagedigital = has_capability(Capabilities::MANAGE_DIGITAL, $context);

$download = optional_param('download', 0, PARAM_BOOL);
$checkprovider = optional_param('checkprovider', 0, PARAM_BOOL);

$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 50, PARAM_INT);
$perpage = max(25, min(200, $perpage));

$statusfilter = DigitalPurchaseAdminFilter::normalize_status(
    optional_param('status', '', PARAM_ALPHANUMEXT)
);

$issuefilter = DigitalPurchaseAdminFilter::normalize_issue(
    optional_param('issue', '', PARAM_ALPHANUMEXT)
);

$reconcilepending = optional_param('reconcile_pending', 0, PARAM_BOOL);
$providerpaidonly = optional_param('providerpaidonly', 0, PARAM_BOOL);
$dbpaidonly = optional_param('dbpaidonly', 0, PARAM_BOOL);

$campususerfilter = optional_param('campususer', 'all', PARAM_ALPHA);

if (!in_array($campususerfilter, ['all', 'registered', 'guest'], true)) {
    $campususerfilter = 'all';
}

if ($reconcilepending) {
    require_sesskey();

    $result = local_subscriptions_reconcile_pending_digital_payments();

    $a = (object)[
        'reconciled' => $result['reconciled'],
        'failed' => $result['failed'] ?? 0,
        'skipped' => $result['skipped'],
        'errors' => $result['errors'],
    ];

    redirect(
        new moodle_url(subscription_config::digital_purchases_admin_page(), [
            'status' => 'pending',
            'checkprovider' => 1,
        ]),
        get_string('digital_purchases_reconcile_done', 'local_subscriptions', $a),
        5,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$PAGE->set_context($context);
$baseurlparams = [
    'checkprovider' => $checkprovider,
    'campususer' => $campususerfilter,
    'status' => $statusfilter,
    'dbpaidonly' => $dbpaidonly,
    'providerpaidonly' => $providerpaidonly,
    'perpage' => $perpage,
    'issue' => $issuefilter,
];

$PAGE->set_url(new moodle_url(subscription_config::digital_purchases_admin_page(), $baseurlparams));
$PAGE->set_title(get_string('digital_purchases_title', 'local_subscriptions'));
$PAGE->set_heading(get_string('digital_purchases_title', 'local_subscriptions'));

$lang = strtolower(substr(current_language(), 0, 2));

$repository = new DigitalPurchaseAdminRepository();

$totalcount = $repository->count(
    $lang,
    $statusfilter,
    $issuefilter,
    $campususerfilter,
    (bool)$dbpaidonly
);

$records = $repository->get_records(
    $lang,
    $statusfilter,
    $issuefilter,
    $campususerfilter,
    (bool)$dbpaidonly,
    $download ? 0 : $page * $perpage,
    $download ? 0 : $perpage
);

$providerstatuses = [];

if ($checkprovider || $download) {
    foreach ($records as $r) {
        try {
            $providerstatuses[$r->id] = local_subscriptions_check_digital_provider_status($r);
        } catch (Throwable $e) {
            $providerstatuses[$r->id] = [
                'status' => 'ERROR',
                'reason' => $e->getMessage(),
            ];
        }
    }
}

if ($providerpaidonly && $checkprovider) {
    foreach ($records as $id => $r) {
        $providerstatus = $providerstatuses[$id]['status'] ?? '';

        if ($providerstatus !== 'PAID') {
            unset($records[$id]);
            unset($providerstatuses[$id]);
        }
    }
}

if ($download) {
    $filename = get_string('digital_purchases_export_filename', 'local_subscriptions') . '_' . date('Y-m-d_H-i') . '.xlsx';

    $workbook = new MoodleExcelWorkbook('-');
    $workbook->send($filename);

    $worksheet = $workbook->add_worksheet(get_string('digital_purchases_export_sheet', 'local_subscriptions'));

    $headerformat = $workbook->add_format([
        'bold' => 1,
        'bg_color' => '#D9EAF7',
    ]);

    $moneyformat = $workbook->add_format([
        'num_format' => '#,##0.00',
    ]);

    $headers = [
        get_string('idnumber'),
        get_string('product', 'local_subscriptions'),
        get_string('digital_purchases_export_slug', 'local_subscriptions'),
        get_string('digital_purchases_export_file_classic', 'local_subscriptions'),
        get_string('digital_purchases_export_file_mobile', 'local_subscriptions'),
        get_string('firstname'),
        get_string('lastname'),
        get_string('email'),
        get_string('language'),
        get_string('price', 'local_subscriptions'),
        get_string('currency', 'local_subscriptions'),
        get_string('digital_success_provider', 'local_subscriptions'),
        get_string('digital_purchases_db_status', 'local_subscriptions'),
        get_string('digital_purchases_provider_status', 'local_subscriptions'),
        get_string('digital_purchases_provider_reason', 'local_subscriptions'),
        get_string('digital_purchases_export_transaction_id', 'local_subscriptions'),
        get_string('digital_purchases_export_session_id', 'local_subscriptions'),
        get_string('digital_purchases_export_pdf_email_sent', 'local_subscriptions'),
        get_string('digital_purchases_export_receipt_sent', 'local_subscriptions'),
        get_string('creation_date', 'local_subscriptions'),
        get_string('digital_purchases_export_payment_date', 'local_subscriptions'),
        get_string('digital_purchases_export_last_update', 'local_subscriptions'),
        get_string('digital_purchases_export_link_expiration', 'local_subscriptions'),
        get_string('digital_purchases_export_download_classic', 'local_subscriptions'),
        get_string('digital_purchases_export_download_mobile', 'local_subscriptions'),
        get_string('digital_purchases_export_last_error', 'local_subscriptions'),
    ];

    foreach ($headers as $col => $header) {
        $worksheet->write_string(0, $col, $header, $headerformat);
    }

    $row = 1;

    foreach ($records as $r) {
        $downloadurl = '';
        $downloadurlmobile = '';

        if (!empty($r->download_token)) {
            $downloadurl = (new moodle_url('/download/pdf/' . $r->download_token))->out(false);

            if (!empty($r->mobile_filename)) {
                $downloadurlmobile = (new moodle_url('/download/pdf/' . $r->download_token, [
                    'version' => 'mobile',
                ]))->out(false);
            }
        }

        $providerstatus = $providerstatuses[$r->id] ?? [
            'status' => $checkprovider ? 'UNKNOWN' : '',
            'reason' => '',
        ];

        $worksheet->write_number($row, 0, (int)$r->id);
        $worksheet->write_string($row, 1, $r->productname ?? '');
        $worksheet->write_string($row, 2, $r->slug ?? '');
        $worksheet->write_string($row, 3, $r->filename ?? '');
        $worksheet->write_string($row, 4, $r->mobile_filename ?? '');
        $worksheet->write_string($row, 5, $r->firstname ?? '');
        $worksheet->write_string($row, 6, $r->lastname ?? '');
        $worksheet->write_string($row, 7, $r->email ?? '');
        $worksheet->write_string($row, 8, $r->buyer_lang ?? '');

        if ($r->price !== null) {
            $worksheet->write_number($row, 9, (float)$r->price, $moneyformat);
        } else {
            $worksheet->write_string($row, 9, '');
        }

        $worksheet->write_string($row, 10, $r->currency ?? '');
        $worksheet->write_string($row, 11, $r->payment_provider ?? '');
        $worksheet->write_string($row, 12, $r->status ?? '');
        $worksheet->write_string($row, 13, $providerstatus['status'] ?? '');
        $worksheet->write_string($row, 14, $providerstatus['reason'] ?? '');
        $worksheet->write_string($row, 15, $r->transactionid ?? '');
        $worksheet->write_string($row, 16, $r->sessionid ?? '');
        $worksheet->write_string($row, 17, !empty($r->emailsent) ? get_string('yes') : get_string('no'));
        $worksheet->write_string($row, 18, !empty($r->receipt_sent) ? get_string('yes') : get_string('no'));
        $worksheet->write_string($row, 19, !empty($r->creation_date) ? userdate((int)$r->creation_date, '%d/%m/%Y %H:%M') : '');
        $worksheet->write_string($row, 20, !empty($r->payment_date) ? userdate((int)$r->payment_date, '%d/%m/%Y %H:%M') : '');
        $worksheet->write_string($row, 21, !empty($r->last_update) ? userdate((int)$r->last_update, '%d/%m/%Y %H:%M') : '');
        $worksheet->write_string($row, 22, !empty($r->download_token_expires) ? userdate((int)$r->download_token_expires, '%d/%m/%Y %H:%M') : get_string('no_expiration', 'local_subscriptions'));
        $worksheet->write_string($row, 23, $downloadurl);
        $worksheet->write_string($row, 24, $downloadurlmobile);
        $worksheet->write_string($row, 25, $r->last_error ?? '');

        $row++;
    }

    $widths = [
        8, 35, 28, 35, 35, 18, 18, 35, 10, 12, 10, 14, 14,
        18, 50, 35, 35, 16, 14, 22, 22, 22, 22, 80, 80, 60,
    ];

    foreach ($widths as $col => $width) {
        $worksheet->set_column($col, $col, $width);
    }

    $workbook->close();
    exit;
}

echo $OUTPUT->header();
echo AdminNavigation::back_button();

echo CrmPageHeader::render(
    get_string(
        'digital_purchases_title',
        'local_subscriptions'
    ),
    get_string(
        'digital_purchases_help_description',
        'local_subscriptions'
    ),
    HelpContext::DIGITAL_PURCHASES
);

echo html_writer::start_div('card card-body mb-4');

echo html_writer::start_tag('form', [
    'method' => 'get',
    'action' => new moodle_url(subscription_config::digital_purchases_admin_page()),
    'class' => 'row g-3 align-items-end',
]);

echo html_writer::div(
    html_writer::label(get_string('status', 'local_subscriptions'), 'status', false, ['class' => 'form-label']) .
    html_writer::select([
        '' => get_string('all', 'moodle'),
        'pending' => get_string('status_pending', 'local_subscriptions'),
        'paid' => get_string('status_paid', 'local_subscriptions'),
        'completed' => get_string('status_completed', 'local_subscriptions'),
        'failed' => get_string('status_failed', 'local_subscriptions'),
        'cancelled' => get_string('status_canceled', 'local_subscriptions'),
    ], 'status', $statusfilter, false, ['class' => 'form-select']),
    'col-md-3'
);

echo html_writer::div(
    html_writer::label(get_string('digital_purchases_campus_account', 'local_subscriptions'), 'campususer', false, ['class' => 'form-label']) .
    html_writer::select([
        'all' => get_string('all', 'moodle'),
        'registered' => get_string('digital_purchases_filter_registered', 'local_subscriptions'),
        'guest' => get_string('digital_purchases_filter_guests', 'local_subscriptions'),
    ], 'campususer', $campususerfilter, false, ['class' => 'form-select']),
    'col-md-3'
);

echo html_writer::div(
    html_writer::label(get_string('perpage', 'local_subscriptions'), 'perpage', false, ['class' => 'form-label']) .
    html_writer::select([25 => 25, 50 => 50, 100 => 100, 200 => 200], 'perpage', $perpage, false, ['class' => 'form-select']),
    'col-md-2'
);

echo html_writer::div(
    html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'checkprovider',
        'value' => $checkprovider ? 1 : 0,
    ]) .
    html_writer::tag('button', get_string('filter', 'local_subscriptions'), [
        'type' => 'submit',
        'class' => 'btn btn-primary',
    ]),
    'col-md-2'
);

echo html_writer::end_tag('form');

echo html_writer::start_div('crm-user-filter-pills mt-3');

foreach (['email_error', 'expired_token'] as $issue) {
    $classes = 'crm-user-filter-pill';

    if ($issue === $issuefilter) {
        $classes .= ' active';
    }

    echo html_writer::link(
        new moodle_url(subscription_config::digital_purchases_admin_page(), array_merge($baseurlparams, [
            'issue' => $issue,
            'status' => '',
            'page' => 0,
        ])),
        DigitalPurchaseAdminFilter::issue_label($issue),
        ['class' => $classes]
    );
}

if ($issuefilter !== '') {
    echo html_writer::link(
        new moodle_url(subscription_config::digital_purchases_admin_page(), array_merge($baseurlparams, [
            'issue' => '',
            'page' => 0,
        ])),
        get_string('digital_purchase_filter_clear_issue', 'local_subscriptions'),
        ['class' => 'crm-user-filter-pill']
    );
}

echo html_writer::end_div();

echo html_writer::start_div('mt-3 d-flex flex-wrap gap-2');

echo html_writer::link(
    new moodle_url(subscription_config::digital_purchases_admin_page(), $baseurlparams + ['download' => 1]),
    get_string('digital_purchases_export_xlsx', 'local_subscriptions'),
    ['class' => 'btn btn-outline-primary']
);

echo html_writer::link(
    new moodle_url(subscription_config::digital_sales_stats_admin_page()),
    get_string('digital_sales_stats_button', 'local_subscriptions'),
    ['class' => 'btn btn-outline-secondary']
);

echo html_writer::start_tag('div', ['class' => 'dropdown']);

echo html_writer::tag('button', get_string('digital_purchases_more_actions', 'local_subscriptions'), [
    'class' => 'btn btn-outline-secondary dropdown-toggle',
    'type' => 'button',
    'data-bs-toggle' => 'dropdown',
    'aria-expanded' => 'false',
]);

echo html_writer::start_tag('ul', ['class' => 'dropdown-menu']);

$actions = [
    [
        'label' => $checkprovider
            ? get_string('digital_purchases_hide_provider_status', 'local_subscriptions')
            : get_string('digital_purchases_check_provider_status', 'local_subscriptions'),
        'url' => new moodle_url(subscription_config::digital_purchases_admin_page(), array_merge($baseurlparams, [
            'checkprovider' => $checkprovider ? 0 : 1,
        ])),
    ],
    [
        'label' => get_string('digital_purchases_show_pending_paid_provider', 'local_subscriptions'),
        'url' => new moodle_url(subscription_config::digital_purchases_admin_page(), [
            'status' => 'pending',
            'checkprovider' => 1,
            'providerpaidonly' => 1,
        ]),
    ],
    [
        'label' => get_string('digital_purchases_reconcile_pending', 'local_subscriptions'),
        'url' => new moodle_url(subscription_config::digital_purchases_admin_page(), [
            'reconcile_pending' => 1,
            'sesskey' => sesskey(),
        ]),
        'danger' => true,
        'confirm' => get_string('digital_purchases_reconcile_confirm', 'local_subscriptions'),
    ],
];

foreach ($actions as $action) {
    $attrs = ['class' => !empty($action['danger']) ? 'dropdown-item text-danger' : 'dropdown-item'];

    if (!empty($action['confirm'])) {
        $attrs['onclick'] = "return confirm('" . addslashes($action['confirm']) . "');";
    }

    echo html_writer::tag('li', html_writer::link($action['url'], $action['label'], $attrs));
}

echo html_writer::end_tag('ul');
echo html_writer::end_tag('div');

echo html_writer::end_div();
echo html_writer::end_div();


if ($checkprovider) {
    echo $OUTPUT->notification(
        get_string('digital_purchases_provider_status_info', 'local_subscriptions'),
        'info'
    );
}

echo html_writer::tag('p',
    get_string('digital_purchases_count', 'local_subscriptions', count($records)),
    ['class' => 'text-muted']
);

$table = new html_table();
$table->head = [
    'ID',
    get_string('digital_success_product', 'local_subscriptions'),
    get_string('digital_success_email', 'local_subscriptions'),
    get_string('digital_purchases_campus_account', 'local_subscriptions'),
    get_string('digital_success_amount', 'local_subscriptions'),
    get_string('digital_success_provider', 'local_subscriptions'),
    get_string('digital_purchases_db_status', 'local_subscriptions'),
    get_string('digital_purchases_provider_status', 'local_subscriptions'),
    get_string('digital_purchases_provider_reason', 'local_subscriptions'),
    get_string('digital_purchases_payment_or_creation_date', 'local_subscriptions'),
    get_string('digital_purchases_emails_status', 'local_subscriptions'),
    get_string('digital_success_download', 'local_subscriptions'),
    get_string('digital_purchases_actions', 'local_subscriptions'),
];

$table->attributes['class'] = 'generaltable table table-striped';
$table->attributes['style'] = 'table-layout:fixed;width:100%;';

$table->colclasses = [
    'col-id',
    'col-product',
    'col-email',
    'col-campus-user',
    'col-amount',
    'col-provider',
    'col-status',
    'col-provider-status',
    'col-reason',
    'col-date',
    'col-emails',
    'col-download',
    'col-actions',
];

$currentlisturl = new moodle_url(
    subscription_config::digital_purchases_admin_page(),
    array_merge($baseurlparams, [
        'page' => $page,
    ])
);

$returnurl = $currentlisturl->out_as_local_url(false);

foreach ($records as $r) {
    $downloadlink = '—';

    if (!empty($r->download_token) && in_array($r->status, ['paid', 'completed'], true)) {
        $links = [];

        $links[] = html_writer::link(
            new moodle_url('/download/pdf/' . $r->download_token),
            get_string('digital_download_classic', 'local_subscriptions'),
            ['target' => '_blank']
        );

        if (!empty($r->mobile_filename)) {
            $links[] = html_writer::link(
                new moodle_url('/download/pdf/' . $r->download_token, ['version' => 'mobile']),
                get_string('digital_download_mobile', 'local_subscriptions'),
                ['target' => '_blank']
            );
        }

        $downloadlink = implode(html_writer::empty_tag('br'), $links);
    }

    $emails = [];
    $emails[] = !empty($r->emailsent) ? '✅' : '❌';
    $emails[] = !empty($r->receipt_sent) ? '✅' : '❌';

    $providerstatus = $providerstatuses[$r->id] ?? [
        'status' => $checkprovider ? 'UNKNOWN' : '—',
        'reason' => '',
    ];

    $statusbadge = local_subscriptions_render_provider_status_badge($providerstatus['status']);

    if (!empty($r->payment_date)) {
        $datecell = AdminFormatter::datetime((int)$r->payment_date);
    } else if (!empty($r->creation_date)) {
        $datecell = '(' . AdminFormatter::datetime((int)$r->creation_date) . ')';
    } else {
        $datecell = '—';
    }

    $productlabel = format_text($r->productname ?? '', FORMAT_HTML, [
        'trusted' => true,
        'noclean' => true,
    ]);

    $productcell = AdminEntityLinks::digital_product(
        (int)$r->productid,
        $productlabel
    );

    $userlabel = s(trim(($r->firstname ?? '') . ' ' . ($r->lastname ?? '')));

    if ($userlabel === '') {
        $userlabel = s($r->email ?? '');
    }

    $userlabel .= html_writer::empty_tag('br') . html_writer::tag('small', s($r->email ?? ''));

    $usercell = !empty($r->crmuserid)
        ? AdminEntityLinks::user((int)$r->crmuserid, $userlabel)
        : $userlabel; 

    $status = strtolower(trim((string)($r->status ?? '')));

    $ispaid = in_array($status, ['paid', 'completed'], true);
    $ispaymentissue = in_array($status, ['pending', 'failed'], true);

    $actionscell = '—';

    if ($canmanagedigital) {
        $actions = [];

        /*
        * Paiement non validé :
        * aucune action d’accès n’est autorisée.
        */
        if ($ispaymentissue) {
            if (!empty($r->crmuserid)) {
                $actions[] = html_writer::link(
                    new moodle_url(
                        subscription_config::admin_user_email_page(),
                        [
                            'id' => (int)$r->crmuserid,
                            'preset' => UserEmailPresetBuilder::DIGITAL_PAYMENT_HELP,
                            'purchaseid' => (int)$r->id,
                            'returnurl' => $returnurl,
                        ]
                    ),
                    get_string(
                        'digital_purchase_action_contact_buyer',
                        'local_subscriptions'
                    ),
                    [
                        'class' => 'btn btn-sm btn-outline-primary',
                    ]
                );
            }

            $actions[] = html_writer::link(
                new moodle_url(
                    subscription_config::digital_purchase_cancel_admin_page(),
                    [
                        'id' => (int)$r->id,
                        'sesskey' => sesskey(),
                        'returnurl' => $returnurl,
                    ]
                ),
                get_string('digital_purchase_action_cancel', 'local_subscriptions'),
                [
                    'class' => 'btn btn-sm btn-outline-danger',
                    'onclick' => "return confirm('" .
                        addslashes(
                            get_string(
                                'digital_purchase_action_cancel_confirm',
                                'local_subscriptions'
                            )
                        ) .
                        "');",
                ]
            );
        }

        /*
        * Paiement confirmé :
        * les actions d’accès sont alors autorisées.
        */
        if ($ispaid) {
            if (empty($r->emailsent) || !empty($r->last_error)) {
                $actions[] = html_writer::link(
                    new moodle_url(
                        subscription_config::digital_purchase_resend_email_admin_page(),
                        [
                            'id' => (int)$r->id,
                            'sesskey' => sesskey(),
                            'returnurl' => $returnurl,
                        ]
                    ),
                    get_string('digital_purchase_action_resend_email', 'local_subscriptions'),
                    [
                        'class' => 'btn btn-sm btn-outline-primary',
                        'onclick' => "return confirm('" .
                            addslashes(
                                get_string(
                                    'digital_purchase_action_resend_email_confirm',
                                    'local_subscriptions'
                                )
                            ) .
                            "');",
                    ]
                );
            }

            $tokenexpired = !empty($r->download_token_expires)
                && (int)$r->download_token_expires < time();

            if (empty($r->download_token) || $tokenexpired) {
                $actions[] = html_writer::link(
                    new moodle_url(
                        subscription_config::digital_purchase_regenerate_token_admin_page(),
                        [
                            'id' => (int)$r->id,
                            'sesskey' => sesskey(),
                            'returnurl' => $returnurl,
                        ]
                    ),
                    get_string('digital_purchase_action_regenerate_token', 'local_subscriptions'),
                    [
                        'class' => 'btn btn-sm btn-outline-secondary',
                        'onclick' => "return confirm('" .
                            addslashes(
                                get_string(
                                    'digital_purchase_action_regenerate_token_confirm',
                                    'local_subscriptions'
                                )
                            ) .
                            "');",
                    ]
                );
            }

            if (!empty($r->download_token) && !$tokenexpired) {
                $actions[] = html_writer::link(
                    new moodle_url(
                        subscription_config::digital_purchase_extend_token_admin_page(),
                        [
                            'id' => (int)$r->id,
                            'sesskey' => sesskey(),
                            'returnurl' => $returnurl,
                        ]
                    ),
                    get_string('digital_purchase_action_extend_token', 'local_subscriptions'),
                    [
                        'class' => 'btn btn-sm btn-outline-secondary',
                        'onclick' => "return confirm('" .
                            addslashes(
                                get_string(
                                    'digital_purchase_action_extend_token_confirm',
                                    'local_subscriptions'
                                )
                            ) .
                            "');",
                    ]
                );
            }
        }

        if ($actions) {
            $actionscell = html_writer::div(
                implode('', $actions),
                'digital-purchase-row-actions'
            );
        }
    }
        
    $table->data[] = [
        html_writer::link(
            new moodle_url(subscription_config::digital_purchase_view_admin_page(), ['id' => $r->id]),
            '#' . $r->id,
            ['class' => 'crm-entity-link']
        ),
        $productcell,
        $usercell,
        !empty($r->hascampususer)
            ? html_writer::tag('span', get_string('yes'), ['class' => 'badge bg-success'])
            : html_writer::tag('span', get_string('no'), ['class' => 'badge bg-warning text-dark']),
        AdminFormatter::price($r->price ?? 0, $r->currency ?? ''),
        DigitalPresenter::render_provider_icon($r->payment_provider ?? ''),
        local_subscriptions_render_db_status_badge($r->status ?? ''),
        $statusbadge,
        html_writer::tag('span', s($providerstatus['reason'] ?? ''), [
            'style' => 'font-size:12px;line-height:1.3;display:block;',
        ]),
        $datecell,
        implode(html_writer::empty_tag('br'), $emails),
        $downloadlink,
        $actionscell,
    ];
}

echo html_writer::tag('style', '
    .col-id { width: 55px; }
    .col-product { width: 150px; }
    .col-email { width: 230px; }
    .col-campus-user { width: 95px; text-align: center; }
    .col-amount { width: 90px; }
    .col-provider { width: 70px; text-align: center; }
    .col-status { width: 90px; }
    .col-provider-status { width: 95px; }
    .col-reason { width: 210px; word-break: break-word; }
    .col-date { width: 115px; }
    .col-emails { width: 65px; text-align:center; font-size:18px; }
    .col-download { width: 100px; }
    .col-actions {
        width: 170px;
    }

    .digital-purchase-row-actions {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 0.35rem;
    }

    .digital-purchase-row-actions .btn {
        width: 100%;
        white-space: normal;
        line-height: 1.2;
    }

    .generaltable td,
    .generaltable th {
        vertical-align: middle;
    }

    .generaltable .badge {
        white-space: nowrap;
    }

    .digital-purchase-user-link {
        text-decoration: none;
    }

    .digital-purchase-user-link:hover {
        text-decoration: underline;
    }

');

echo html_writer::div(
    html_writer::table($table),
    'table-responsive'
);

echo $OUTPUT->paging_bar(
    $totalcount,
    $page,
    $perpage,
    new moodle_url(subscription_config::digital_purchases_admin_page(), $baseurlparams)
);

echo $OUTPUT->footer();


function local_subscriptions_render_provider_status_badge(string $status): string {
    $status = strtoupper($status);

    $class = 'badge bg-secondary';

    if ($status === 'PAID') {
        $class = 'badge bg-success';
    } else if ($status === 'DECLINED') {
        $class = 'badge bg-danger';
    } else if ($status === 'PENDING') {
        $class = 'badge bg-warning text-dark';
    } else if ($status === 'ERROR') {
        $class = 'badge bg-dark';
    }

    return html_writer::tag('span', s($status), ['class' => $class]);
}

function local_subscriptions_render_db_status_badge(string $status): string {
    $status = strtolower(trim($status));

    $labels = [
        'paid' => 'PAID',
        'completed' => 'COMPLETED',
        'pending' => 'PENDING',
        'failed' => 'FAILED',
        'cancelled' => 'CANCELLED',
        'canceled' => 'CANCELED',
    ];

    $classes = [
        'paid' => 'badge bg-success',
        'completed' => 'badge bg-success',
        'pending' => 'badge bg-warning text-dark',
        'failed' => 'badge bg-danger',
        'cancelled' => 'badge bg-secondary',
        'canceled' => 'badge bg-secondary',
    ];

    $label = $labels[$status] ?? strtoupper($status ?: 'UNKNOWN');
    $class = $classes[$status] ?? 'badge bg-secondary';

    return html_writer::tag('span', s($label), ['class' => $class]);
}

function local_subscriptions_check_digital_provider_status(stdClass $pr): array {
    if (empty($pr->sessionid)) {
        return [
            'status' => 'UNKNOWN',
            'reason' => 'No sessionid/orderId in database.',
        ];
    }

    if ($pr->payment_provider === 'stripe') {
        return local_subscriptions_check_stripe_provider_status($pr);
    }

    if ($pr->payment_provider === 'alfa') {
        return local_subscriptions_check_alfa_provider_status($pr);
    }

    return [
        'status' => 'UNKNOWN',
        'reason' => 'Unsupported provider: ' . ($pr->payment_provider ?? ''),
    ];
}


function local_subscriptions_check_stripe_provider_status(stdClass $pr): array {
    global $CFG;

    $env = get_config('local_subscriptions', 'stripe_env') ?: 'test';
    $env = ($env === 'live') ? 'live' : 'test';

    $secret = get_config('local_subscriptions', "stripe_{$env}_secret") ?: '';

    if ($secret === '') {
        return [
            'status' => 'ERROR',
            'reason' => 'Missing Stripe secret key for env: ' . $env,
        ];
    }

    $autoload = $CFG->dirroot . '/local/subscriptions/vendor/autoload.php';
    if (!file_exists($autoload)) {
        return [
            'status' => 'ERROR',
            'reason' => 'Stripe SDK autoload not found.',
        ];
    }

    require_once($autoload);

    \Stripe\Stripe::setApiKey($secret);

    $session = \Stripe\Checkout\Session::retrieve($pr->sessionid);

    $paymentstatus = $session->payment_status ?? '';
    $status = $session->status ?? '';

    if ($paymentstatus === 'paid') {
        return [
            'status' => 'PAID',
            'reason' => '',
        ];
    }

    if ($status === 'expired') {
        return [
            'status' => 'DECLINED',
            'reason' => 'Stripe Checkout session expired.',
        ];
    }

    return [
        'status' => 'PENDING',
        'reason' => 'Stripe status: ' . $status . ' / payment_status: ' . $paymentstatus,
    ];
}


function local_subscriptions_reconcile_pending_digital_payments(): array {
    global $DB;

    $records = $DB->get_records('subscription_digital_payment_request', [
        'status' => 'pending',
    ], 'creation_date DESC, id DESC');

    $result = [
        'reconciled' => 0,
        'failed' => 0,
        'skipped' => 0,
        'errors' => 0,
    ];

    foreach ($records as $pr) {
        try {
            $providerstatus = local_subscriptions_check_digital_provider_status($pr);

            $status = strtoupper($providerstatus['status'] ?? 'UNKNOWN');
            $reason = $providerstatus['reason'] ?? '';

            if (
                $status === 'DECLINED'
                || ($status === 'UNKNOWN' && stripos($reason, 'No sessionid') !== false)
                || ($status === 'PENDING' && stripos($reason, 'payment_status: unpaid') !== false)
            ) {
                $DB->update_record('subscription_digital_payment_request', (object)[
                    'id' => $pr->id,
                    'status' => 'failed',
                    'last_error' => '[manual_reconcile] Provider failed/unpaid: ' . $reason,
                    'last_update' => time(),
                ]);

                $result['failed'] = ($result['failed'] ?? 0) + 1;
                continue;
            }

            if ($status !== 'PAID') {
                $DB->update_record('subscription_digital_payment_request', (object)[
                    'id' => $pr->id,
                    'last_error' => '[manual_reconcile] Provider status: ' . $status .
                        ($reason !== '' ? ' - ' . $reason : ''),
                    'last_update' => time(),
                ]);

                $result['skipped']++;
                continue;
            }

            $event = new \local_subscriptions\payment\dto\InternalEvent('checkout_completed', [
                'payment_request_id' => (string)$pr->id,
                'currency' => $pr->currency,
                'amount_minor' => (int)$pr->amount_minor,
                'meta' => [
                    'payment_context' => 'digital_product',
                    'provider' => $pr->payment_provider,
                    'session' => $pr->sessionid,
                    'orderId' => $pr->sessionid,
                ],
            ]);

            \local_subscriptions\digital\digital_payment_service::on_checkout_completed($event);

            $result['reconciled']++;
        } catch (\Throwable $e) {
            $result['errors']++;

            $DB->update_record('subscription_digital_payment_request', (object)[
                'id' => $pr->id,
                'last_error' => '[manual_reconcile] ' . $e->getMessage(),
                'last_update' => time(),
            ]);
        }
    }

    return $result;
}

function local_subscriptions_check_alfa_provider_status(stdClass $pr): array {
    $env = get_config('local_subscriptions', 'alfa_env') ?: 'test';
    $env = ($env === 'live') ? 'live' : 'test';

    $base = rtrim((string)(get_config('local_subscriptions', "alfa_{$env}_api_base") ?: ''), '/');
    $username = get_config('local_subscriptions', "alfa_{$env}_username") ?: '';
    $password = get_config('local_subscriptions', "alfa_{$env}_password") ?: '';
    $token = get_config('local_subscriptions', "alfa_{$env}_token") ?: '';

    if ($base === '') {
        return [
            'status' => 'ERROR',
            'reason' => 'Missing Alfa API base for env: ' . $env,
        ];
    }

    $payload = [
        'orderId' => $pr->sessionid,
    ];

    if ($token !== '') {
        $payload['token'] = $token;
    } else {
        if ($username !== '') {
            $payload['userName'] = $username;
        }

        if ($password !== '') {
            $payload['password'] = $password;
        }
    }

    $url = $base . '/payment/rest/getOrderStatusExtended.do';

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => http_build_query($payload, '', '&', PHP_QUERY_RFC3986),
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    ]);

    $raw = curl_exec($ch);

    if ($raw === false) {
        $err = curl_error($ch);
        curl_close($ch);

        return [
            'status' => 'ERROR',
            'reason' => 'CURL error: ' . $err,
        ];
    }

    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($raw, true);

    if (!is_array($data)) {
        parse_str($raw, $data);
    }

    if (!is_array($data)) {
        return [
            'status' => 'ERROR',
            'reason' => "Invalid Alfa response. HTTP {$httpcode}",
        ];
    }

    $orderstatus = isset($data['orderStatus']) ? (int)$data['orderStatus'] : null;

    $reason = $data['actionCodeDescription']
        ?? $data['errorMessage']
        ?? $data['error']
        ?? '';

    if ($orderstatus === 2) {
        return [
            'status' => 'PAID',
            'reason' => '',
        ];
    }

    if ($orderstatus === 6) {
        return [
            'status' => 'DECLINED',
            'reason' => $reason !== '' ? $reason : 'Payment declined.',
        ];
    }

    if ($orderstatus === 0) {
        return [
            'status' => 'PENDING',
            'reason' => $reason !== '' ? $reason : 'Registered but not paid.',
        ];
    }

    return [
        'status' => 'PENDING',
        'reason' => $reason !== '' ? $reason : 'Alfa orderStatus: ' . var_export($orderstatus, true),
    ];
}