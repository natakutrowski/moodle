<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\customer\identity\CommerceCustomerIdentityNavigationRenderer;
use local_subscriptions\commerce\customer\identity\CommerceCustomerIdentitySimilarityService;
use local_subscriptions\commerce\customer\provisioning\CommerceLegacyDigitalBulkProvisioningService;
use local_subscriptions\commerce\customer\provisioning\CommerceLegacyDigitalProvisioningPlan;
use local_subscriptions\commerce\customer\provisioning\CommerceLegacyDigitalProvisioningService;
use local_subscriptions\commerce\customer\reconciliation\CommerceCustomerIdentityReconciliationService;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_USERS);

$q = trim(optional_param('q', '', PARAM_RAW_TRIMMED));
$status = trim(
    optional_param('status', '', PARAM_ALPHANUMEXT)
);
$page = max(0, optional_param('page', 0, PARAM_INT));
$perpage = 50;

$validstatuses = [
    '',
    CommerceLegacyDigitalProvisioningPlan::STATUS_CREATABLE,
    CommerceLegacyDigitalProvisioningPlan::STATUS_EXISTING_ACCOUNT,
    CommerceLegacyDigitalProvisioningPlan::STATUS_AMBIGUOUS_ACCOUNT,
    CommerceLegacyDigitalProvisioningPlan::STATUS_SIMILAR_ACCOUNT,
    CommerceLegacyDigitalProvisioningPlan::STATUS_INVALID_EMAIL,
];
if (!in_array($status, $validstatuses, true)) {
    $status = '';
}

$pageurl = new moodle_url(
    '/local/subscriptions/admin/commerce/customer-identities/provisioning.php',
    array_filter(
        [
            'q' => $q,
            'status' => $status,
            'page' => $page,
        ],
        static fn($value): bool =>
            $value !== ''
            && $value !== 0
    )
);

$title = get_string(
    'commerce_identity_provisioning_title',
    'local_subscriptions'
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $title,
    'local-subscriptions-commerce-customer-identities-provisioning-page'
);

$reconciliation =
    new CommerceCustomerIdentityReconciliationService($DB);
$service =
    new CommerceLegacyDigitalProvisioningService(
        $DB,
        new CommerceCustomerIdentitySimilarityService($DB),
        $reconciliation
    );
$bulk =
    new CommerceLegacyDigitalBulkProvisioningService(
        $service
    );

$selectedemails = optional_param_array(
    'emails',
    [],
    PARAM_EMAIL
);
$forcedemails = optional_param_array(
    'forceemails',
    [],
    PARAM_EMAIL
);
$action = optional_param(
    'action',
    '',
    PARAM_ALPHA
);

$previewplans = [];
$executionresults = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    if ($action === 'preview') {
        $previewplans =
            $bulk->preview($selectedemails);
    } elseif ($action === 'execute') {
        $confirm = optional_param(
            'confirmprovisioning',
            0,
            PARAM_BOOL
        );
        if (!$confirm) {
            throw new moodle_exception(
                'commerce_identity_provisioning_confirmation_required',
                'local_subscriptions'
            );
        }

        $executionresults =
            $bulk->execute(
                $selectedemails,
                (int)$USER->id,
                $forcedemails
            );
    }
}

$search = $service->search(
    [
        'q' => $q,
        'status' => $status,
    ],
    $page * $perpage,
    $perpage
);

$statuslabels = [
    CommerceLegacyDigitalProvisioningPlan::STATUS_CREATABLE =>
        'commerce_identity_provisioning_status_creatable',
    CommerceLegacyDigitalProvisioningPlan::STATUS_EXISTING_ACCOUNT =>
        'commerce_identity_provisioning_status_existing',
    CommerceLegacyDigitalProvisioningPlan::STATUS_AMBIGUOUS_ACCOUNT =>
        'commerce_identity_provisioning_status_ambiguous',
    CommerceLegacyDigitalProvisioningPlan::STATUS_SIMILAR_ACCOUNT =>
        'commerce_identity_provisioning_status_similar',
    CommerceLegacyDigitalProvisioningPlan::STATUS_INVALID_EMAIL =>
        'commerce_identity_provisioning_status_invalid',
];

$statusbadge = static function(string $status) use ($statuslabels): string {
    $classes = [
        CommerceLegacyDigitalProvisioningPlan::STATUS_CREATABLE =>
            'badge bg-success',
        CommerceLegacyDigitalProvisioningPlan::STATUS_EXISTING_ACCOUNT =>
            'badge bg-secondary',
        CommerceLegacyDigitalProvisioningPlan::STATUS_AMBIGUOUS_ACCOUNT =>
            'badge bg-warning text-dark',
        CommerceLegacyDigitalProvisioningPlan::STATUS_SIMILAR_ACCOUNT =>
            'badge bg-warning text-dark',
        CommerceLegacyDigitalProvisioningPlan::STATUS_INVALID_EMAIL =>
            'badge bg-danger',
    ];

    return html_writer::span(
        get_string(
            $statuslabels[$status]
                ?? 'commerce_identity_provisioning_status_invalid',
            'local_subscriptions'
        ),
        $classes[$status] ?? 'badge bg-secondary'
    );
};

$rendercandidate = static function(
    \local_subscriptions\commerce\customer\identity\CommerceCustomerIdentitySimilarityMatch $match,
    string $legacyemail
): string {
    $account = $match->second;

    $name = trim(
        (string)($account->firstname ?? '')
        . ' '
        . (string)($account->lastname ?? '')
    );

    $label = html_writer::span(
        s($name !== '' ? $name : (string)$account->email),
        'crm-identity-provisioning-candidate-name'
    )
    . html_writer::span(
        '#' . (int)$account->id,
        'crm-identity-provisioning-candidate-id'
    )
    . html_writer::span(
        (int)$match->score . '%',
        'crm-identity-provisioning-candidate-score'
    );

    return html_writer::div(
        html_writer::link(
            new moodle_url(
                '/local/subscriptions/admin/users/view.php',
                ['id' => (int)$account->id]
            ),
            $label,
            [
                'class' =>
                    'crm-identity-provisioning-candidate-user',
            ]
        )
        . html_writer::link(
            new moodle_url(
                '/local/subscriptions/admin/commerce/customer-identities/legacy-link.php',
                [
                    'email' => $legacyemail,
                    'targetuserid' => (int)$account->id,
                ]
            ),
            get_string(
                'commerce_identity_legacy_link_action',
                'local_subscriptions'
            ),
            [
                'class' =>
                    'btn btn-sm btn-outline-primary '
                    . 'crm-identity-provisioning-link-action',
            ]
        ),
        'crm-identity-provisioning-candidate'
    );
};


echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::COMMERCE,
    $context
);
echo CrmBreadcrumbRenderer::render([
    [
        'label' => get_string(
            'crm_commerce_title',
            'local_subscriptions'
        ),
        'url' => new moodle_url(
            '/local/subscriptions/admin/commerce/index.php'
        ),
    ],
    [
        'label' => get_string(
            'commerce_identity_reconciliation_title',
            'local_subscriptions'
        ),
        'url' => new moodle_url(
            '/local/subscriptions/admin/commerce/customer-identities/index.php'
        ),
    ],
    [
        'label' => $title,
        'url' => null,
    ],
]);

echo CrmPageHeader::render(
    $title,
    get_string(
        'commerce_identity_provisioning_description',
        'local_subscriptions'
    ),
    HelpContext::COMMERCE
);

echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::IDENTITIES,
    $context
);

echo CommerceCustomerIdentityNavigationRenderer::render(
    CommerceCustomerIdentityNavigationRenderer::PROVISIONING
);

echo html_writer::div(
    get_string(
        'commerce_identity_provisioning_safety',
        'local_subscriptions'
    ),
    'alert alert-info'
);

if ($executionresults !== []) {
    $created = 0;
    $skipped = 0;
    $errors = 0;

    foreach ($executionresults as $result) {
        if ($result->status === 'created') {
            $created++;
        } elseif ($result->status === 'error') {
            $errors++;
        } else {
            $skipped++;
        }
    }

    echo html_writer::div(
        get_string(
            'commerce_identity_provisioning_execution_summary',
            'local_subscriptions',
            (object)[
                'created' => $created,
                'skipped' => $skipped,
                'errors' => $errors,
            ]
        ),
        $errors > 0
            ? 'alert alert-warning'
            : 'alert alert-success'
    );
}

if ($previewplans !== []) {
    echo html_writer::tag(
        'h3',
        get_string(
            'commerce_identity_provisioning_dryrun_title',
            'local_subscriptions'
        ),
        ['class' => 'h5']
    );

    echo html_writer::start_tag(
        'form',
        [
            'method' => 'post',
            'action' => $pageurl->out(false),
            'class' => 'card card-body mb-4 crm-identity-filter-card',
        ]
    );
    echo html_writer::empty_tag(
        'input',
        [
            'type' => 'hidden',
            'name' => 'sesskey',
            'value' => sesskey(),
        ]
    );
    echo html_writer::empty_tag(
        'input',
        [
            'type' => 'hidden',
            'name' => 'action',
            'value' => 'execute',
        ]
    );

    $previewtable = new html_table();
    $previewtable->attributes['class'] =
        'generaltable table table-hover align-middle crm-identity-table';
    $previewtable->head = [
        get_string(
            'commerce_identity_provisioning_email',
            'local_subscriptions'
        ),
        get_string(
            'commerce_identity_provisioning_identity',
            'local_subscriptions'
        ),
        get_string(
            'commerce_identity_provisioning_purchases',
            'local_subscriptions'
        ),
        get_string(
            'commerce_identity_provisioning_status',
            'local_subscriptions'
        ),
        get_string(
            'commerce_identity_provisioning_override',
            'local_subscriptions'
        ),
    ];

    foreach ($previewplans as $plan) {
        echo html_writer::empty_tag(
            'input',
            [
                'type' => 'hidden',
                'name' => 'emails[]',
                'value' => $plan->email,
            ]
        );

        $similar = [];
        foreach ($plan->similaraccounts as $match) {
            $similar[] = $rendercandidate(
                $match,
                $plan->email
            );
        }

        $override = '';
        if (
            $plan->status ===
            CommerceLegacyDigitalProvisioningPlan::STATUS_SIMILAR_ACCOUNT
        ) {
            $override =
                html_writer::empty_tag(
                    'input',
                    [
                        'type' => 'checkbox',
                        'name' => 'forceemails[]',
                        'value' => $plan->email,
                        'class' => 'form-check-input me-2',
                    ]
                )
                . get_string(
                    'commerce_identity_provisioning_force_similar',
                    'local_subscriptions'
                );
        }

        $previewtable->data[] = [
            s($plan->email),
            s(
                trim(
                    $plan->firstname
                    . ' '
                    . $plan->lastname
                )
            )
                . (
                    $similar !== []
                    ? html_writer::div(
                        implode('', $similar),
                        'crm-identity-provisioning-candidates mt-2'
                    )
                    : ''
                ),
            $plan->purchase_count(),
            $statusbadge($plan->status),
            $override,
        ];
    }

    echo html_writer::table($previewtable);

    echo html_writer::start_div(
        'form-check mb-3'
    );
    echo html_writer::empty_tag(
        'input',
        [
            'id' => 'confirm-provisioning',
            'type' => 'checkbox',
            'name' => 'confirmprovisioning',
            'value' => '1',
            'required' => 'required',
            'class' => 'form-check-input',
        ]
    );
    echo html_writer::tag(
        'label',
        get_string(
            'commerce_identity_provisioning_confirm',
            'local_subscriptions'
        ),
        [
            'for' => 'confirm-provisioning',
            'class' => 'form-check-label',
        ]
    );
    echo html_writer::end_div();

    echo html_writer::tag(
        'button',
        get_string(
            'commerce_identity_provisioning_execute',
            'local_subscriptions'
        ),
        [
            'type' => 'submit',
            'class' => 'btn btn-danger',
        ]
    );

    echo html_writer::end_tag('form');
}

echo html_writer::start_tag(
    'form',
    [
        'method' => 'get',
        'action' => (
            new moodle_url(
                '/local/subscriptions/admin/commerce/customer-identities/provisioning.php'
            )
        )->out(false),
        'class' => 'card card-body mb-4 crm-identity-filter-card',
    ]
);
echo html_writer::start_div('row g-3');

echo html_writer::start_div(
    'col-12 col-md-6'
);
echo html_writer::tag(
    'label',
    get_string(
        'commerce_identity_provisioning_filter_query',
        'local_subscriptions'
    ),
    [
        'for' => 'provisioning-q',
        'class' => 'form-label',
    ]
);
echo html_writer::empty_tag(
    'input',
    [
        'id' => 'provisioning-q',
        'name' => 'q',
        'type' => 'text',
        'value' => $q,
        'class' => 'form-control',
    ]
);
echo html_writer::end_div();

echo html_writer::start_div(
    'col-12 col-md-4'
);
echo html_writer::tag(
    'label',
    get_string(
        'commerce_identity_provisioning_filter_status',
        'local_subscriptions'
    ),
    [
        'for' => 'provisioning-status',
        'class' => 'form-label',
    ]
);
echo html_writer::select(
    [
        '' => get_string('all'),
        CommerceLegacyDigitalProvisioningPlan::STATUS_CREATABLE =>
            get_string(
                'commerce_identity_provisioning_status_creatable',
                'local_subscriptions'
            ),
        CommerceLegacyDigitalProvisioningPlan::STATUS_EXISTING_ACCOUNT =>
            get_string(
                'commerce_identity_provisioning_status_existing',
                'local_subscriptions'
            ),
        CommerceLegacyDigitalProvisioningPlan::STATUS_AMBIGUOUS_ACCOUNT =>
            get_string(
                'commerce_identity_provisioning_status_ambiguous',
                'local_subscriptions'
            ),
        CommerceLegacyDigitalProvisioningPlan::STATUS_SIMILAR_ACCOUNT =>
            get_string(
                'commerce_identity_provisioning_status_similar',
                'local_subscriptions'
            ),
    ],
    'status',
    $status,
    false,
    [
        'id' => 'provisioning-status',
        'class' => 'form-select',
    ]
);
echo html_writer::end_div();

echo html_writer::start_div(
    'col-12 col-md-2 d-flex align-items-end gap-2 '
    . 'crm-identity-provisioning-filter-actions'
);
echo html_writer::tag(
    'button',
    html_writer::tag('i', '', [
        'class' => 'fa fa-filter',
        'aria-hidden' => 'true',
    ])
    . html_writer::span(
        get_string(
            'commerce_filters_apply',
            'local_subscriptions'
        )
    ),
    [
        'type' => 'submit',
        'class' => 'btn btn-primary',
    ]
);
echo html_writer::link(
    new moodle_url(
        '/local/subscriptions/admin/commerce/customer-identities/provisioning.php'
    ),
    get_string('reset'),
    [
        'class' => 'btn btn-outline-secondary',
    ]
);
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_tag('form');

if ($search['truncated']) {
    echo html_writer::div(
        get_string(
            'commerce_identity_provisioning_scan_truncated',
            'local_subscriptions',
            CommerceLegacyDigitalProvisioningService::MAX_SCAN_ROWS
        ),
        'alert alert-warning'
    );
}

echo html_writer::start_tag(
    'form',
    [
        'method' => 'post',
        'action' => $pageurl->out(false),
    ]
);
echo html_writer::empty_tag(
    'input',
    [
        'type' => 'hidden',
        'name' => 'sesskey',
        'value' => sesskey(),
    ]
);
echo html_writer::empty_tag(
    'input',
    [
        'type' => 'hidden',
        'name' => 'action',
        'value' => 'preview',
    ]
);

$table = new html_table();
$table->attributes['class'] =
    'generaltable table table-hover align-middle crm-identity-table';
$table->head = [
    get_string(
        'commerce_identity_select',
        'local_subscriptions'
    ),
    get_string(
        'commerce_identity_provisioning_email',
        'local_subscriptions'
    ),
    get_string(
        'commerce_identity_provisioning_identity',
        'local_subscriptions'
    ),
    get_string(
        'commerce_identity_provisioning_purchases',
        'local_subscriptions'
    ),
    get_string(
        'crm_identity_provisioning_diagnostic',
        'local_subscriptions'
    ),
    get_string(
        'crm_identity_provisioning_recommendation',
        'local_subscriptions'
    ),
];

foreach ($search['items'] as $plan) {
    $selectable = in_array(
        $plan->status,
        [
            CommerceLegacyDigitalProvisioningPlan::STATUS_CREATABLE,
            CommerceLegacyDigitalProvisioningPlan::STATUS_SIMILAR_ACCOUNT,
        ],
        true
    );

    $selector = $selectable
        ? html_writer::empty_tag(
            'input',
            [
                'type' => 'checkbox',
                'name' => 'emails[]',
                'value' => $plan->email,
                'class' => 'form-check-input',
            ]
        )
        : '';

    $details = html_writer::span(
        get_string(
            'crm_identity_provisioning_ready_help',
            'local_subscriptions'
        ),
        'crm-identity-provisioning-detail-muted'
    );

    if (
        $plan->status ===
        CommerceLegacyDigitalProvisioningPlan::STATUS_EXISTING_ACCOUNT
    ) {
        $links = [];
        foreach ($plan->exactuserids as $existinguserid) {
            $links[] = html_writer::link(
                new moodle_url(
                    '/local/subscriptions/admin/users/view.php',
                    ['id' => (int)$existinguserid]
                ),
                get_string(
                    'crm_identity_provisioning_open_existing',
                    'local_subscriptions',
                    (int)$existinguserid
                )
            );
        }
        $details = html_writer::div(
            implode(' · ', $links),
            'crm-identity-provisioning-existing'
        );
    } elseif (
        $plan->status ===
        CommerceLegacyDigitalProvisioningPlan::STATUS_AMBIGUOUS_ACCOUNT
    ) {
        $details = html_writer::div(
            get_string(
                'crm_identity_provisioning_ambiguous_help',
                'local_subscriptions'
            ),
            'crm-identity-provisioning-detail-warning'
        );
    } elseif (
        $plan->status ===
        CommerceLegacyDigitalProvisioningPlan::STATUS_SIMILAR_ACCOUNT
    ) {
        $candidates = [];
        foreach (
            array_slice(
                $plan->similaraccounts,
                0,
                2
            ) as $match
        ) {
            $candidates[] = $rendercandidate(
                $match,
                $plan->email
            );
        }

        $details = html_writer::div(
            get_string(
                'crm_identity_provisioning_similar_help',
                'local_subscriptions',
                count($plan->similaraccounts)
            ),
            'crm-identity-provisioning-detail-warning'
        )
        . html_writer::div(
            implode('', $candidates),
            'crm-identity-provisioning-candidates'
        );

        if (count($plan->similaraccounts) > 2) {
            $details .= html_writer::div(
                get_string(
                    'crm_identity_provisioning_more_candidates',
                    'local_subscriptions',
                    count($plan->similaraccounts) - 2
                ),
                'crm-identity-provisioning-more'
            );
        }
    }


    $table->data[] = [
        $selector,
        s($plan->email),
        s(
            trim(
                $plan->firstname
                . ' '
                . $plan->lastname
            )
        ),
        $plan->purchase_count(),
        $statusbadge($plan->status),
        $details,
    ];
}

echo html_writer::table($table);

echo html_writer::div(
    html_writer::tag(
        'button',
        html_writer::tag('i', '', [
            'class' => 'fa fa-eye',
            'aria-hidden' => 'true',
        ])
        . html_writer::span(
            get_string(
                'commerce_identity_provisioning_preview_selected',
                'local_subscriptions'
            )
        ),
        [
            'type' => 'submit',
            'class' => 'btn btn-primary',
        ]
    ),
    'crm-identity-provisioning-table-actions'
);

echo html_writer::end_tag('form');

echo $OUTPUT->paging_bar(
    $search['total'],
    $page,
    $perpage,
    $pageurl
);

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
