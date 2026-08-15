<?php

declare(strict_types=1);

require_once dirname(__DIR__, 5) . '/config.php';

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\checkout\guest\CommerceUnfinishedGuestCheckoutCrmService;
use local_subscriptions\commerce\purchase\presentation\CommercePurchasePresentation;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\payment\Provider;

$context = AdminSecurity::require(Capabilities::VIEW_PAYMENTS);
$useridfilter = optional_param('userid', 0, PARAM_INT);
$classfilter = optional_param('class', '', PARAM_ALPHANUMEXT);
$query = trim(optional_param('q', '', PARAM_RAW_TRIMMED));

$url = new moodle_url('/local/subscriptions/admin/commerce/unfinished-checkouts/index.php');
CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $url,
    get_string('commerce_guest_crm_title', 'local_subscriptions'),
    'local-subscriptions-commerce-unfinished-checkouts-page'
);

$service = CommerceUnfinishedGuestCheckoutCrmService::create();
$rows = $service->queue();

if ($useridfilter > 0) {
    $rows = array_values(array_filter(
        $rows,
        static fn(array $row): bool => (int)$row['userid'] === $useridfilter
    ));
}
if ($classfilter !== '') {
    $rows = array_values(array_filter(
        $rows,
        static fn(array $row): bool => $row['classification'] === $classfilter
    ));
}
if ($query !== '') {
    $needle = \core_text::strtolower($query);
    $rows = array_values(array_filter($rows, static function(array $row) use ($needle): bool {
        $haystacks = [
            (string)$row['email'],
            (string)$row['username'],
            (string)$row['purchase_reference'],
            (string)$row['payment_reference'],
        ];
        foreach ($row['purchases'] as $purchase) {
            $haystacks[] = (string)$purchase->reference;
            foreach ($purchase->productlabels ?? [] as $label) {
                $haystacks[] = (string)$label;
            }
        }
        foreach ($haystacks as $haystack) {
            if (\core_text::strpos(\core_text::strtolower($haystack), $needle) !== false) {
                return true;
            }
        }
        return false;
    }));
}

$counts = [
    'total' => count($rows),
    'pending_purchase' => 0,
    'multiple_pending' => 0,
    'provider_paid_pending' => 0,
    'provisional_no_purchase' => 0,
    'stuck_identity' => 0,
];
foreach ($rows as $row) {
    if (isset($counts[$row['classification']])) {
        $counts[$row['classification']]++;
    }
}

$age = static function(int $seconds): string {
    if ($seconds < HOURSECS) {
        return get_string(
            'commerce_guest_crm_age_minutes',
            'local_subscriptions',
            max(1, (int)floor($seconds / MINSECS))
        );
    }
    if ($seconds < DAYSECS) {
        return get_string(
            'commerce_guest_crm_age_hours',
            'local_subscriptions',
            max(1, (int)floor($seconds / HOURSECS))
        );
    }
    return get_string(
        'commerce_guest_crm_age_days',
        'local_subscriptions',
        max(1, (int)floor($seconds / DAYSECS))
    );
};

$providerstatuslabel = static function(string $status): string {
    $status = strtolower(trim($status));
    $key = 'commerce_guest_crm_provider_status_' . preg_replace(
        '/[^a-z0-9_]+/',
        '_',
        $status
    );
    return get_string_manager()->string_exists($key, 'local_subscriptions')
        ? get_string($key, 'local_subscriptions')
        : CommercePurchasePresentation::technical_status_label(
            'payment',
            $status
        );
};

$providerlogo = static function(string $provider): string {
    $provider = strtolower(trim($provider));
    $name = Provider::get($provider);
    $url = Provider::icon_url($provider);
    if ($url !== null) {
        return html_writer::empty_tag('img', [
            'src' => $url->out(false),
            'alt' => $name,
            'title' => $name,
            'aria-label' => $name,
            'class' => 'crm-unfinished-provider-logo',
            'width' => 24,
            'height' => 24,
            'loading' => 'lazy',
        ]);
    }

    return html_writer::span(
        html_writer::tag('i', '', [
            'class' => 'fa fa-credit-card',
            'aria-hidden' => 'true',
        ]),
        'crm-unfinished-provider-logo-fallback',
        [
            'title' => $name,
            'aria-label' => $name,
        ]
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
        'label' => get_string('commerce_purchases_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/purchases/index.php'),
    ],
    [
        'label' => get_string('commerce_guest_crm_title', 'local_subscriptions'),
        'url' => null,
    ],
]);
echo CrmPageHeader::render(
    get_string('commerce_guest_crm_title', 'local_subscriptions'),
    get_string('commerce_guest_crm_help', 'local_subscriptions'),
    HelpContext::COMMERCE
);
echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::PURCHASES,
    $context
);

echo html_writer::start_tag('form', [
    'method' => 'get',
    'action' => $url->out(false),
    'class' => 'crm-sales-filter-form crm-unfinished-filter-form',
]);
echo html_writer::div(
    html_writer::tag('i', '', ['class' => 'fa fa-filter', 'aria-hidden' => 'true'])
        . html_writer::tag(
            'strong',
            get_string(
                'commerce_guest_crm_search_filters_title',
                'local_subscriptions'
            )
        ),
    'crm-sales-filter-title'
);
echo html_writer::start_div('crm-unfinished-filter-grid');
echo html_writer::div(
    html_writer::tag('label', get_string('search'), [
        'for' => 'unfinished-q',
        'class' => 'form-label',
    ])
    . html_writer::empty_tag('input', [
        'id' => 'unfinished-q',
        'type' => 'search',
        'name' => 'q',
        'value' => $query,
        'class' => 'form-control',
        'placeholder' => get_string(
            'commerce_guest_crm_search_placeholder',
            'local_subscriptions'
        ),
    ]),
    'crm-sales-filter-field'
);
$classoptions = [
    '' => get_string('commerce_guest_crm_filter_all', 'local_subscriptions'),
    'provider_paid_pending' => get_string(
        'commerce_guest_crm_class_provider_paid_pending',
        'local_subscriptions'
    ),
    'multiple_pending' => get_string(
        'commerce_guest_crm_class_multiple_pending',
        'local_subscriptions'
    ),
    'pending_purchase' => get_string(
        'commerce_guest_crm_class_pending_purchase',
        'local_subscriptions'
    ),
    'stuck_identity' => get_string(
        'commerce_guest_crm_class_stuck_identity',
        'local_subscriptions'
    ),
    'provisional_no_purchase' => get_string(
        'commerce_guest_crm_class_provisional_no_purchase',
        'local_subscriptions'
    ),
];
echo html_writer::div(
    html_writer::tag(
        'label',
        get_string('commerce_guest_crm_filter_status', 'local_subscriptions'),
        ['for' => 'unfinished-class', 'class' => 'form-label']
    )
    . html_writer::select(
        $classoptions,
        'class',
        $classfilter,
        false,
        ['id' => 'unfinished-class', 'class' => 'form-select']
    ),
    'crm-sales-filter-field'
);
echo html_writer::div(
    html_writer::tag(
        'button',
        html_writer::tag('i', '', [
            'class' => 'fa fa-filter me-1',
            'aria-hidden' => 'true',
        ]) . get_string('filter'),
        ['type' => 'submit', 'class' => 'btn btn-primary']
    )
    . html_writer::link(
        $url,
        get_string('reset'),
        ['class' => 'btn btn-outline-secondary']
    ),
    'crm-unfinished-filter-actions'
);
echo html_writer::end_div();
echo html_writer::end_tag('form');

$kpis = [
    ['fa-hourglass-half', 'commerce_guest_crm_kpi_total', $counts['total'], 'is-neutral'],
    ['fa-shopping-cart', 'commerce_guest_crm_kpi_pending', $counts['pending_purchase'], 'is-warning'],
    ['fa-clone', 'commerce_guest_crm_kpi_multiple', $counts['multiple_pending'], 'is-attention'],
    ['fa-credit-card', 'commerce_guest_crm_kpi_provider_paid', $counts['provider_paid_pending'], 'is-danger'],
    ['fa-user-o', 'commerce_guest_crm_kpi_provisional', $counts['provisional_no_purchase'], 'is-muted'],
];
echo html_writer::start_div('crm-unfinished-kpis');
foreach ($kpis as [$icon, $label, $value, $class]) {
    echo html_writer::div(
        html_writer::span(
            html_writer::tag('i', '', [
                'class' => 'fa ' . $icon,
                'aria-hidden' => 'true',
            ]),
            'crm-unfinished-kpi-icon ' . $class
        )
        . html_writer::div(
            html_writer::div(
                get_string($label, 'local_subscriptions'),
                'crm-unfinished-kpi-label'
            )
            . html_writer::div((string)$value, 'crm-unfinished-kpi-value'),
            'crm-unfinished-kpi-copy'
        ),
        'crm-unfinished-kpi'
    );
}
echo html_writer::end_div();

echo html_writer::tag(
    'details',
    html_writer::tag(
        'summary',
        html_writer::tag('i', '', [
            'class' => 'fa fa-info-circle',
            'aria-hidden' => 'true',
        ])
        . html_writer::tag(
            'strong',
            get_string(
                'commerce_guest_crm_help_legend_title',
                'local_subscriptions'
            )
        )
        . html_writer::span(
            get_string(
                'commerce_guest_crm_help_legend_hint',
                'local_subscriptions'
            ),
            'crm-unfinished-help-hint'
        ),
        ['class' => 'crm-unfinished-help-summary']
    )
    . html_writer::div(
        html_writer::div(
            html_writer::tag(
                'strong',
                get_string(
                    'commerce_guest_crm_help_checkout_title',
                    'local_subscriptions'
                )
            )
            . html_writer::tag(
                'p',
                get_string(
                    'commerce_guest_crm_help_checkout_text',
                    'local_subscriptions'
                )
            ),
            'crm-unfinished-help-item'
        )
        . html_writer::div(
            html_writer::tag(
                'strong',
                get_string(
                    'commerce_guest_crm_help_resume_title',
                    'local_subscriptions'
                )
            )
            . html_writer::tag(
                'p',
                get_string(
                    'commerce_guest_crm_help_resume_text',
                    'local_subscriptions'
                )
            ),
            'crm-unfinished-help-item'
        )
        . html_writer::div(
            html_writer::tag(
                'strong',
                get_string(
                    'commerce_guest_crm_help_provider_title',
                    'local_subscriptions'
                )
            )
            . html_writer::tag(
                'p',
                get_string(
                    'commerce_guest_crm_help_provider_text',
                    'local_subscriptions'
                )
            ),
            'crm-unfinished-help-item'
        )
        . html_writer::div(
            html_writer::tag(
                'strong',
                get_string(
                    'commerce_guest_crm_help_situations_title',
                    'local_subscriptions'
                )
            )
            . html_writer::tag(
                'p',
                get_string(
                    'commerce_guest_crm_help_situations_text',
                    'local_subscriptions'
                )
            ),
            'crm-unfinished-help-item'
        ),
        'crm-unfinished-help-grid'
    ),
    ['class' => 'crm-unfinished-help']
);

if ($rows === []) {
    echo html_writer::div(
        get_string('commerce_guest_crm_empty', 'local_subscriptions'),
        'alert alert-success'
    );
} else {
    $table = new html_table();
    $table->attributes['class'] =
        'generaltable table table-hover align-middle crm-sales-table crm-unfinished-table';
    $table->head = [
        get_string('commerce_guest_crm_column_client', 'local_subscriptions'),
        get_string('commerce_guest_crm_column_checkout', 'local_subscriptions'),
        get_string('commerce_guest_crm_column_purchase', 'local_subscriptions'),
        get_string('commerce_guest_crm_column_payment', 'local_subscriptions'),
        get_string('commerce_guest_crm_column_status', 'local_subscriptions'),
        get_string('commerce_guest_crm_column_age', 'local_subscriptions'),
        get_string('actions'),
    ];

    foreach ($rows as $row) {
        $pending = array_values(array_filter(
            $row['purchases'],
            static fn($purchase): bool =>
                (string)$purchase->status === 'payment_pending'
        ));
        $primarypurchase = $pending[0] ?? ($row['purchases'][0] ?? null);

        $client = html_writer::link(
            new moodle_url('/local/subscriptions/admin/users/view.php', [
                'id' => (int)$row['userid'],
            ]),
            s((string)$row['email']),
            ['class' => 'crm-sales-customer-name']
        ) . html_writer::div(
            get_string(
                'commerce_guest_crm_account_id',
                'local_subscriptions',
                (int)$row['userid']
            ),
            'crm-sales-customer-email'
        );

        $checkout = html_writer::div(
            get_string(
                'commerce_guest_crm_checkout_id',
                'local_subscriptions',
                (int)$row['source_session_id']
            ),
            'crm-unfinished-reference'
        ) . html_writer::div(
            s(CommercePurchasePresentation::technical_status_label(
                'payment',
                (string)$row['source_status']
            )),
            'crm-unfinished-secondary'
        );

        $purchasehtml = '—';
        if ($primarypurchase !== null) {
            $purchaseblocks = [];
            $displaypurchases = $pending !== [] ? $pending : [$primarypurchase];
            foreach ($displaypurchases as $candidatepurchase) {
                $labels = $candidatepurchase->productlabels ?? [];
                $productlabel = $labels !== []
                    ? implode(', ', array_slice($labels, 0, 2))
                    : get_string(
                        'commerce_guest_crm_unknown_product',
                        'local_subscriptions'
                    );
                $block = html_writer::link(
                    new moodle_url('/local/subscriptions/admin/commerce/purchases/view.php', [
                        'id' => (int)$candidatepurchase->id,
                    ]),
                    s($productlabel),
                    ['class' => 'crm-unfinished-product-link']
                );
                $block .= html_writer::div(
                    format_float(((int)$candidatepurchase->totalminor) / 100, 2)
                        . ' ' . s((string)$candidatepurchase->currency),
                    'crm-unfinished-secondary fw-semibold'
                );

                if ((string)$row['purchase_reference']
                        === (string)$candidatepurchase->reference) {
                    $block .= html_writer::span(
                        get_string(
                            'commerce_guest_crm_resume_active',
                            'local_subscriptions'
                        ),
                        'badge text-bg-success mt-1'
                    );
                } elseif ((string)$candidatepurchase->status === 'payment_pending'
                        && has_capability(
                            Capabilities::MANAGE_CRM_ADMIN_TOOLS,
                            $context
                        )) {
                    $block .= html_writer::tag(
                        'form',
                        html_writer::empty_tag('input', [
                            'type' => 'hidden',
                            'name' => 'sesskey',
                            'value' => sesskey(),
                        ])
                        . html_writer::empty_tag('input', [
                            'type' => 'hidden',
                            'name' => 'action',
                            'value' => 'selectpurchase',
                        ])
                        . html_writer::empty_tag('input', [
                            'type' => 'hidden',
                            'name' => 'userid',
                            'value' => (int)$row['userid'],
                        ])
                        . html_writer::empty_tag('input', [
                            'type' => 'hidden',
                            'name' => 'reference',
                            'value' => (string)$candidatepurchase->reference,
                        ])
                        . html_writer::tag(
                            'button',
                            get_string(
                                'commerce_guest_crm_use_for_resume',
                                'local_subscriptions'
                            ),
                            [
                                'type' => 'submit',
                                'class' => 'btn btn-link btn-sm p-0 mt-1',
                            ]
                        ),
                        [
                            'method' => 'post',
                            'action' => (new moodle_url(
                                '/local/subscriptions/admin/commerce/unfinished-checkouts/action.php'
                            ))->out(false),
                        ]
                    );
                }

                $purchaseblocks[] = html_writer::div(
                    $block,
                    'crm-unfinished-purchase-block'
                );
            }
            $purchasehtml = implode('', $purchaseblocks);
        }

        $payments = [];
        foreach ($row['payments'] as $payment) {
            if ($primarypurchase !== null
                    && (string)$payment->purchasereference
                        !== (string)$primarypurchase->reference) {
                continue;
            }
            $provider = strtolower((string)$payment->provider);
            $line = $providerlogo($provider)
                . html_writer::div(
                    html_writer::span(
                        s(CommercePurchasePresentation::technical_status_label(
                            'payment',
                            (string)$payment->status
                        )),
                        'badge text-bg-light border'
                    )
                    . (!empty($payment->providerlivechecked)
                        ? html_writer::div(
                            get_string(
                                'commerce_guest_crm_provider_state',
                                'local_subscriptions',
                                $providerstatuslabel(
                                    (string)$payment->providerlivestatus
                                )
                            ),
                            'crm-unfinished-provider-state'
                        )
                        : ''),
                    'crm-unfinished-provider-copy'
                );
            $payments[] = html_writer::div(
                $line,
                'crm-unfinished-payment-line'
            );
        }
        $paymenthtml = $payments !== [] ? implode('', $payments) : '—';

        $statusclass = match ($row['classification']) {
            'provider_paid_pending' => 'text-bg-danger',
            'multiple_pending' => 'text-bg-warning',
            'pending_purchase' => 'text-bg-warning',
            'stuck_identity' => 'text-bg-secondary',
            default => 'text-bg-light border',
        };
        $statushtml = html_writer::span(
            get_string(
                'commerce_guest_crm_class_' . $row['classification'],
                'local_subscriptions'
            ),
            'badge ' . $statusclass
        );

        $actions = [];
        if ($primarypurchase !== null) {
            $actions[] = html_writer::link(
                new moodle_url('/local/subscriptions/admin/commerce/purchases/view.php', [
                    'id' => (int)$primarypurchase->id,
                ]),
                html_writer::tag('i', '', [
                    'class' => 'fa fa-eye me-1',
                    'aria-hidden' => 'true',
                ]) . get_string('view'),
                ['class' => 'btn btn-sm btn-outline-primary']
            );
            if ((string)$primarypurchase->status === 'payment_pending') {
                $actions[] = html_writer::link(
                    new moodle_url(
                        '/local/subscriptions/admin/commerce/purchases/followup_mail.php',
                        ['id' => (int)$primarypurchase->id]
                    ),
                    html_writer::tag('i', '', [
                        'class' => 'fa fa-paper-plane-o me-1',
                        'aria-hidden' => 'true',
                    ]) . get_string(
                        'commerce_sales_followup_action',
                        'local_subscriptions'
                    ),
                    ['class' => 'btn btn-sm btn-outline-secondary']
                );
            }
        }

        if ((int)$row['stuck_sessions'] > 0
                && has_capability(Capabilities::MANAGE_CRM_ADMIN_TOOLS, $context)) {
            $repairurl = new moodle_url(
                '/local/subscriptions/admin/commerce/unfinished-checkouts/action.php'
            );
            $actions[] = html_writer::tag(
                'form',
                html_writer::empty_tag('input', [
                    'type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey(),
                ])
                . html_writer::empty_tag('input', [
                    'type' => 'hidden', 'name' => 'action', 'value' => 'repair',
                ])
                . html_writer::empty_tag('input', [
                    'type' => 'hidden', 'name' => 'userid',
                    'value' => (int)$row['userid'],
                ])
                . html_writer::tag(
                    'button',
                    get_string('commerce_guest_crm_repair', 'local_subscriptions'),
                    ['type' => 'submit', 'class' => 'btn btn-sm btn-outline-warning']
                ),
                [
                    'method' => 'post',
                    'action' => $repairurl->out(false),
                    'class' => 'd-inline',
                ]
            );
        }

        foreach ($row['payments'] as $payment) {
            if ($primarypurchase === null
                    || (string)$payment->purchasereference
                        !== (string)$primarypurchase->reference) {
                continue;
            }
            if (in_array(strtolower((string)$payment->provider), ['alfa', 'stripe'], true)
                    && in_array((string)$payment->status, ['created', 'redirected', 'pending'], true)
                    && has_capability(Capabilities::MANAGE_CRM_ADMIN_TOOLS, $context)) {
                $actions[] = html_writer::tag(
                    'form',
                    html_writer::empty_tag('input', [
                        'type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey(),
                    ])
                    . html_writer::empty_tag('input', [
                        'type' => 'hidden', 'name' => 'action', 'value' => 'reconcile',
                    ])
                    . html_writer::empty_tag('input', [
                        'type' => 'hidden', 'name' => 'userid',
                        'value' => (int)$row['userid'],
                    ])
                    . html_writer::empty_tag('input', [
                        'type' => 'hidden', 'name' => 'paymentid',
                        'value' => (int)$payment->id,
                    ])
                    . html_writer::tag(
                        'button',
                        get_string(
                            'commerce_guest_crm_check_provider',
                            'local_subscriptions'
                        ),
                        ['type' => 'submit', 'class' => 'btn btn-sm btn-outline-secondary']
                    ),
                    [
                        'method' => 'post',
                        'action' => (new moodle_url(
                            '/local/subscriptions/admin/commerce/unfinished-checkouts/action.php'
                        ))->out(false),
                        'class' => 'd-inline',
                    ]
                );
                break;
            }
        }

        $table->data[] = [
            $client,
            $checkout,
            $purchasehtml,
            $paymenthtml,
            $statushtml,
            s($age((int)$row['age'])),
            html_writer::div(
                implode('', $actions),
                'crm-unfinished-actions'
            ),
        ];
    }

    echo html_writer::div(
        html_writer::table($table),
        'crm-unfinished-table-wrap'
    );
}

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
