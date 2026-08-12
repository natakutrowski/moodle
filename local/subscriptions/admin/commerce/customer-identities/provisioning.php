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
            'class' => 'card card-body mb-4',
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
        'generaltable table table-hover align-middle';
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
            $account = $match->second;
            $similar[] =
                '#'
                . (int)$account->id
                . ' · '
                . s((string)$account->email)
                . ' · '
                . (int)$match->score
                . '%';
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
                        implode('<br>', $similar),
                        'small text-warning mt-1'
                    )
                    : ''
                ),
            $plan->purchase_count(),
            get_string(
                $statuslabels[$plan->status]
                    ?? 'commerce_identity_provisioning_status_invalid',
                'local_subscriptions'
            ),
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
        'class' => 'card card-body mb-4',
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
    'col-12 col-md-2 d-flex align-items-end'
);
echo html_writer::tag(
    'button',
    get_string('filter'),
    [
        'type' => 'submit',
        'class' => 'btn btn-primary w-100',
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
    'generaltable table table-hover align-middle';
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
        'commerce_identity_provisioning_status',
        'local_subscriptions'
    ),
    get_string(
        'commerce_identity_provisioning_details',
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

    $details = '';
    if (
        $plan->status ===
        CommerceLegacyDigitalProvisioningPlan::STATUS_EXISTING_ACCOUNT
    ) {
        $details = get_string(
            'commerce_identity_provisioning_existing_user',
            'local_subscriptions',
            implode(', ', $plan->exactuserids)
        );
    } elseif (
        $plan->status ===
        CommerceLegacyDigitalProvisioningPlan::STATUS_AMBIGUOUS_ACCOUNT
    ) {
        $details = get_string(
            'commerce_identity_provisioning_ambiguous_users',
            'local_subscriptions',
            implode(', ', $plan->exactuserids)
        );
    } elseif (
        $plan->status ===
        CommerceLegacyDigitalProvisioningPlan::STATUS_SIMILAR_ACCOUNT
    ) {
        $candidateparts = [];
        foreach ($plan->similaraccounts as $match) {
            $account = $match->second;
            $candidateparts[] =
                '#'
                . (int)$account->id
                . ' '
                . s((string)$account->email)
                . ' ('
                . (int)$match->score
                . '%)';
        }
        $details = implode('<br>', $candidateparts);
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
        get_string(
            $statuslabels[$plan->status]
                ?? 'commerce_identity_provisioning_status_invalid',
            'local_subscriptions'
        ),
        $details,
    ];
}

echo html_writer::table($table);

echo html_writer::tag(
    'button',
    get_string(
        'commerce_identity_provisioning_preview_selected',
        'local_subscriptions'
    ),
    [
        'type' => 'submit',
        'class' => 'btn btn-primary',
    ]
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
