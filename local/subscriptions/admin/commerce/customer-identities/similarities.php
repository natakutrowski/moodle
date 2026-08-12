<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\customer\identity\CommerceCustomerIdentityNavigationRenderer;
use local_subscriptions\commerce\customer\identity\CommerceCustomerIdentitySimilarityService;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::VIEW_PAYMENTS);

$q = trim(optional_param('q', '', PARAM_RAW_TRIMMED));
$status = trim(optional_param('status', '', PARAM_ALPHA));
if (!in_array($status, ['', 'active', 'suspended'], true)) {
    $status = '';
}
$minscore = max(
    0,
    min(
        100,
        optional_param(
            'minscore',
            CommerceCustomerIdentitySimilarityService::DEFAULT_MIN_SCORE,
            PARAM_INT
        )
    )
);

$pageurl = new moodle_url(
    '/local/subscriptions/admin/commerce/customer-identities/similarities.php',
    array_filter(
        [
            'q' => $q,
            'status' => $status,
            'minscore' => $minscore,
        ],
        static fn($value): bool => $value !== ''
    )
);
$title = get_string(
    'commerce_identity_similarity_title',
    'local_subscriptions'
);
CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $title,
    'local-subscriptions-commerce-customer-identities-similarity-page'
);

$result = (new CommerceCustomerIdentitySimilarityService($DB))->search([
    'q' => $q,
    'status' => $status,
    'minscore' => $minscore,
]);

$reasonlabels = [
    CommerceCustomerIdentitySimilarityService::REASON_EMAIL_EXACT =>
        'commerce_identity_similarity_reason_email_exact',
    CommerceCustomerIdentitySimilarityService::REASON_EMAIL_LOCAL_EXACT =>
        'commerce_identity_similarity_reason_email_local_exact',
    CommerceCustomerIdentitySimilarityService::REASON_EMAIL_LOCAL_CLOSE =>
        'commerce_identity_similarity_reason_email_local_close',
    CommerceCustomerIdentitySimilarityService::REASON_NAME_EXACT =>
        'commerce_identity_similarity_reason_name_exact',
    CommerceCustomerIdentitySimilarityService::REASON_NAME_REVERSED =>
        'commerce_identity_similarity_reason_name_reversed',
    CommerceCustomerIdentitySimilarityService::REASON_FIRSTNAME_CLOSE =>
        'commerce_identity_similarity_reason_firstname_close',
    CommerceCustomerIdentitySimilarityService::REASON_LASTNAME_CLOSE =>
        'commerce_identity_similarity_reason_lastname_close',
    CommerceCustomerIdentitySimilarityService::REASON_PHONE_EXACT =>
        'commerce_identity_similarity_reason_phone_exact',
];

$renderuser = static function(\stdClass $user): string {
    $name = fullname($user);
    $label = $name !== '' ? $name : ('#' . (int)$user->id);
    $link = html_writer::link(
        new moodle_url(
            '/local/subscriptions/admin/users/view.php',
            ['id' => (int)$user->id]
        ),
        s($label)
    );

    $status = (int)$user->suspended === 1
        ? html_writer::span(
            get_string(
                'commerce_identity_similarity_account_suspended',
                'local_subscriptions'
            ),
            'badge bg-warning text-dark ms-2'
        )
        : html_writer::span(
            get_string(
                'commerce_identity_similarity_account_active',
                'local_subscriptions'
            ),
            'badge bg-success ms-2'
        );

    $details = s((string)$user->email)
        . ' · #' . (int)$user->id;

    $selector = html_writer::empty_tag(
        'input',
        [
            'type' => 'checkbox',
            'name' => 'userids[]',
            'value' => (int)$user->id,
            'class' => 'form-check-input me-2',
            'aria-label' => get_string(
                'commerce_identity_merge_select_account',
                'local_subscriptions',
                (int)$user->id
            ),
        ]
    );

    return $selector
        . $link
        . $status
        . html_writer::div($details, 'small text-muted mt-1');
};

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
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
        'commerce_identity_similarity_description',
        'local_subscriptions'
    ),
    HelpContext::COMMERCE
);
echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::IDENTITIES,
    $context
);
echo CommerceCustomerIdentityNavigationRenderer::render(
    CommerceCustomerIdentityNavigationRenderer::SIMILARITIES
);

echo html_writer::div(
    get_string(
        'commerce_identity_similarity_manual_only',
        'local_subscriptions'
    ),
    'alert alert-info'
);

echo html_writer::start_tag(
    'form',
    [
        'method' => 'get',
        'action' => (new moodle_url(
            '/local/subscriptions/admin/commerce/customer-identities/similarities.php'
        ))->out(false),
        'class' => 'card card-body mb-4',
    ]
);
echo html_writer::start_div('row g-3');

echo html_writer::start_div('col-12 col-md-5');
echo html_writer::tag(
    'label',
    get_string(
        'commerce_identity_similarity_filter_query',
        'local_subscriptions'
    ),
    [
        'for' => 'identity-similarity-q',
        'class' => 'form-label',
    ]
);
echo html_writer::empty_tag(
    'input',
    [
        'id' => 'identity-similarity-q',
        'name' => 'q',
        'type' => 'text',
        'value' => $q,
        'class' => 'form-control',
    ]
);
echo html_writer::end_div();

echo html_writer::start_div('col-12 col-md-3');
echo html_writer::tag(
    'label',
    get_string(
        'commerce_identity_similarity_filter_status',
        'local_subscriptions'
    ),
    [
        'for' => 'identity-similarity-status',
        'class' => 'form-label',
    ]
);
echo html_writer::select(
    [
        '' => get_string('all'),
        'active' => get_string(
            'commerce_identity_similarity_account_active',
            'local_subscriptions'
        ),
        'suspended' => get_string(
            'commerce_identity_similarity_account_suspended',
            'local_subscriptions'
        ),
    ],
    'status',
    $status,
    false,
    [
        'id' => 'identity-similarity-status',
        'class' => 'form-select',
    ]
);
echo html_writer::end_div();

echo html_writer::start_div('col-12 col-md-2');
echo html_writer::tag(
    'label',
    get_string(
        'commerce_identity_similarity_filter_minscore',
        'local_subscriptions'
    ),
    [
        'for' => 'identity-similarity-minscore',
        'class' => 'form-label',
    ]
);
echo html_writer::empty_tag(
    'input',
    [
        'id' => 'identity-similarity-minscore',
        'name' => 'minscore',
        'type' => 'number',
        'min' => 0,
        'max' => 100,
        'value' => $minscore,
        'class' => 'form-control',
    ]
);
echo html_writer::end_div();

echo html_writer::start_div('col-12 col-md-2 d-flex align-items-end');
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

echo html_writer::div(
    get_string(
        'commerce_identity_similarity_scan_summary',
        'local_subscriptions',
        (object)[
            'users' => $result['scanned'],
            'matches' => count($result['matches']),
        ]
    ),
    'small text-muted mb-3'
);

if ($result['truncated']) {
    echo html_writer::div(
        get_string(
            'commerce_identity_similarity_truncated',
            'local_subscriptions',
            CommerceCustomerIdentitySimilarityService::MAX_USERS_SCANNED
        ),
        'alert alert-warning'
    );
}

if ($result['matches'] === []) {
    echo html_writer::div(
        get_string(
            'commerce_identity_similarity_empty',
            'local_subscriptions'
        ),
        'alert alert-success'
    );
} else {
    echo html_writer::start_tag(
        'form',
        [
            'method' => 'post',
            'action' => (new moodle_url(
                '/local/subscriptions/admin/commerce/customer-identities/merge.php'
            ))->out(false),
        ]
    );
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'sesskey',
        'value' => sesskey(),
    ]);

    $table = new html_table();
    $table->attributes['class'] =
        'generaltable table table-hover align-middle';
    $table->head = [
        get_string(
            'commerce_identity_similarity_score',
            'local_subscriptions'
        ),
        get_string(
            'commerce_identity_similarity_account_first',
            'local_subscriptions'
        ),
        get_string(
            'commerce_identity_similarity_account_second',
            'local_subscriptions'
        ),
        get_string(
            'commerce_identity_similarity_signals',
            'local_subscriptions'
        ),
    ];

    foreach ($result['matches'] as $match) {
        $scoreclass = $match->score >= 90
            ? 'badge bg-danger'
            : ($match->score >= 75
                ? 'badge bg-warning text-dark'
                : 'badge bg-info text-dark');

        $reasons = [];
        foreach ($match->reasons as $reason) {
            $key = $reasonlabels[$reason] ?? null;
            if ($key !== null) {
                $reasons[] = html_writer::span(
                    get_string($key, 'local_subscriptions'),
                    'badge bg-light text-dark border me-1 mb-1'
                );
            }
        }

        $table->data[] = [
            html_writer::span(
                $match->score . '%',
                $scoreclass
            ),
            $renderuser($match->first),
            $renderuser($match->second),
            implode('', $reasons),
        ];
    }

    echo html_writer::table($table);
    echo html_writer::tag(
        'button',
        get_string(
            'commerce_identity_merge_prepare',
            'local_subscriptions'
        ),
        [
            'type' => 'submit',
            'class' => 'btn btn-primary',
        ]
    );
    echo html_writer::end_tag('form');
}

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
