<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\customer\identity\CommerceCustomerIdentityNavigationRenderer;
use local_subscriptions\commerce\customer\merge\CommerceCustomerMergePlanner;
use local_subscriptions\commerce\customer\merge\CommerceCustomerMergeExecutionService;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_USERS);

$userids = optional_param_array('userids', [], PARAM_INT);
$ids = trim(optional_param('ids', '', PARAM_RAW_TRIMMED));
$targetuserid = optional_param('targetuserid', 0, PARAM_INT);

if ($ids !== '') {
    foreach (preg_split('/[\s,;]+/', $ids) ?: [] as $candidate) {
        $candidate = (int)$candidate;
        if ($candidate > 0) {
            $userids[] = $candidate;
        }
    }
}
$userids = array_values(array_unique(array_filter(
    array_map('intval', $userids),
    static fn(int $userid): bool => $userid > 1
)));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
}

$pageurl = new moodle_url(
    '/local/subscriptions/admin/commerce/customer-identities/merge.php'
);
$title = get_string('commerce_identity_merge_title', 'local_subscriptions');

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $title,
    'local-subscriptions-commerce-customer-identities-merge-page'
);

$plan = null;
$error = null;
$executionresult = null;
$blockers = [];
$planner = new CommerceCustomerMergePlanner($DB);
$executor = new CommerceCustomerMergeExecutionService($DB, $planner);
$action = optional_param('action', 'preview', PARAM_ALPHA);

if ($userids !== []) {
    try {
        $plan = $planner->build(
            $userids,
            $targetuserid > 0 ? $targetuserid : null
        );
        $blockers = $executor->blockers($plan);

        if ($action === 'execute') {
            require_sesskey();
            $confirmed = optional_param('confirmmerge', 0, PARAM_BOOL);
            if (!$confirmed) {
                throw new \moodle_exception(
                    'commerce_identity_merge_confirmation_required',
                    'local_subscriptions'
                );
            }

            $executionresult = $executor->execute(
                $userids,
                $plan->targetuserid,
                (int)$USER->id
            );
        }
    } catch (\Throwable $exception) {
        $error = $exception->getMessage();
    }
}

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    [
        'label' => get_string('crm_commerce_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php'),
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
    get_string('commerce_identity_merge_description', 'local_subscriptions'),
    HelpContext::COMMERCE
);
echo CommerceSectionNavigationRenderer::render(
    CommerceSectionNavigationRenderer::IDENTITIES,
    $context
);
echo CommerceCustomerIdentityNavigationRenderer::render(
    CommerceCustomerIdentityNavigationRenderer::MERGE
);

echo html_writer::div(
    get_string('commerce_identity_merge_dryrun_only', 'local_subscriptions'),
    'alert alert-info'
);

echo html_writer::start_tag('form', [
    'method' => 'get',
    'action' => $pageurl->out(false),
    'class' => 'card card-body mb-4',
]);
echo html_writer::tag(
    'label',
    get_string('commerce_identity_merge_ids', 'local_subscriptions'),
    ['for' => 'merge-ids', 'class' => 'form-label']
);
echo html_writer::empty_tag('input', [
    'id' => 'merge-ids',
    'name' => 'ids',
    'type' => 'text',
    'value' => implode(',', $userids),
    'class' => 'form-control mb-3',
    'placeholder' => '123, 456, 789',
]);
echo html_writer::tag(
    'button',
    get_string('commerce_identity_merge_preview', 'local_subscriptions'),
    ['type' => 'submit', 'class' => 'btn btn-primary align-self-start']
);
echo html_writer::end_tag('form');

if ($executionresult !== null) {
    echo html_writer::div(
        get_string(
            'commerce_identity_merge_execution_success',
            'local_subscriptions',
            (object)[
                'mergeuuid' => $executionresult->mergeuuid,
                'targetuserid' => $executionresult->targetuserid,
                'sources' => count($executionresult->sourceuserids),
            ]
        ),
        'alert alert-success'
    );
} elseif ($error !== null) {
    echo html_writer::div(s($error), 'alert alert-danger');
}

if ($executionresult === null && $error === null && $plan !== null) {
    echo html_writer::tag(
        'h3',
        get_string('commerce_identity_merge_accounts', 'local_subscriptions'),
        ['class' => 'h5 mb-3']
    );

    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => $pageurl->out(false),
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'sesskey',
        'value' => sesskey(),
    ]);
    foreach ($userids as $userid) {
        echo html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'userids[]',
            'value' => $userid,
        ]);
    }

    $table = new html_table();
    $table->attributes['class'] = 'generaltable table table-hover align-middle';
    $table->head = [
        get_string('commerce_identity_merge_keep', 'local_subscriptions'),
        get_string('commerce_identity_merge_account', 'local_subscriptions'),
        get_string('commerce_identity_merge_pedagogy', 'local_subscriptions'),
        get_string('commerce_identity_merge_commerce', 'local_subscriptions'),
        get_string('commerce_identity_merge_account_quality', 'local_subscriptions'),
    ];

    foreach ($plan->profiles as $profile) {
        $userid = $profile->userid();
        $recommended = $userid === $plan->recommendedtargetuserid;
        $selected = $userid === $plan->targetuserid;

        $selector = html_writer::empty_tag('input', [
            'type' => 'radio',
            'name' => 'targetuserid',
            'value' => $userid,
            'checked' => $selected ? 'checked' : null,
            'class' => 'form-check-input',
        ]);

        $name = fullname($profile->user);
        $account = html_writer::link(
            new moodle_url(
                '/local/subscriptions/admin/users/view.php',
                ['id' => $userid]
            ),
            s($name !== '' ? $name : ('#' . $userid))
        );
        $account .= html_writer::div(
            s((string)$profile->user->email) . ' · #' . $userid,
            'small text-muted'
        );
        if ($recommended) {
            $account .= html_writer::span(
                get_string(
                    'commerce_identity_merge_recommended',
                    'local_subscriptions'
                ),
                'badge bg-success mt-1'
            );
        }

        $pedagogy = get_string(
            'commerce_identity_merge_pedagogy_summary',
            'local_subscriptions',
            (object)[
                'courses' => $profile->enrolledcourses,
                'completedcourses' => $profile->completedcourses,
                'activities' => $profile->completedactivities,
                'grades' => $profile->gradecount,
                'average' => number_format($profile->averagegradepercent, 1),
                'score' => $profile->pedagogical_score(),
            ]
        );

        $commerce = get_string(
            'commerce_identity_merge_commerce_summary',
            'local_subscriptions',
            (object)[
                'purchases' => $profile->purchases,
                'grants' => $profile->grants,
                'digital' => $profile->digitalaccesses,
                'score' => $profile->commerce_score(),
            ]
        );

        $quality = [];
        $quality[] = (int)$profile->user->suspended === 0
            ? get_string('commerce_identity_similarity_account_active', 'local_subscriptions')
            : get_string('commerce_identity_similarity_account_suspended', 'local_subscriptions');
        $quality[] = !empty($profile->user->confirmed)
            ? get_string('commerce_identity_merge_confirmed', 'local_subscriptions')
            : get_string('commerce_identity_merge_unconfirmed', 'local_subscriptions');
        if ((int)$profile->user->lastaccess > 0) {
            $quality[] = get_string(
                'commerce_identity_merge_lastaccess',
                'local_subscriptions',
                userdate((int)$profile->user->lastaccess)
            );
        }

        $table->data[] = [
            $selector,
            $account,
            nl2br(s($pedagogy)),
            nl2br(s($commerce)),
            implode('<br>', array_map('s', $quality)),
        ];
    }

    echo html_writer::table($table);
    echo html_writer::tag(
        'button',
        get_string('commerce_identity_merge_recalculate', 'local_subscriptions'),
        ['type' => 'submit', 'class' => 'btn btn-outline-primary mb-4']
    );
    echo html_writer::end_tag('form');

    $target = $plan->target_profile();
    echo html_writer::tag(
        'h3',
        get_string('commerce_identity_merge_virtual_profile', 'local_subscriptions'),
        ['class' => 'h5']
    );
    echo html_writer::div(
        get_string(
            'commerce_identity_merge_virtual_profile_summary',
            'local_subscriptions',
            (object)[
                'userid' => $target->userid(),
                'name' => fullname($target->user),
                'email' => (string)$target->user->email,
            ]
        ),
        'card card-body mb-3'
    );

    $totals = $plan->commerce_transfer_totals();
    echo html_writer::div(
        get_string(
            'commerce_identity_merge_transfer_summary',
            'local_subscriptions',
            (object)[
                'purchases' => $totals['purchases'],
                'grants' => $totals['grants'],
                'digital' => $totals['digitalaccesses'],
                'guests' => $totals['guestsessions'],
            ]
        ),
        'alert alert-secondary'
    );

    if ($plan->warnings !== []) {
        echo html_writer::tag(
            'h3',
            get_string('commerce_identity_merge_warnings', 'local_subscriptions'),
            ['class' => 'h5 mt-4']
        );
        echo html_writer::start_tag('ul', ['class' => 'list-group mb-4']);
        foreach ($plan->warnings as $warning) {
            $key = match ($warning['type']) {
                CommerceCustomerMergePlanner::WARNING_PEDAGOGICAL_HISTORY =>
                    'commerce_identity_merge_warning_pedagogical_history',
                CommerceCustomerMergePlanner::WARNING_SHARED_COURSES =>
                    'commerce_identity_merge_warning_shared_courses',
                CommerceCustomerMergePlanner::WARNING_DIFFERENT_EMAILS =>
                    'commerce_identity_merge_warning_different_emails',
                CommerceCustomerMergePlanner::WARNING_SUSPENDED_TARGET =>
                    'commerce_identity_merge_warning_suspended_target',
                default => 'commerce_identity_merge_warning_generic',
            };
            $message = get_string(
                $key,
                'local_subscriptions',
                (object)[
                    'userid' => $warning['userid'],
                    'count' => $plan->sharedcoursecount,
                ]
            );
            echo html_writer::tag('li', s($message), [
                'class' => 'list-group-item list-group-item-warning',
            ]);
        }
        echo html_writer::end_tag('ul');
    }

    echo html_writer::div(
        get_string(
            'commerce_identity_merge_nonmergeable',
            'local_subscriptions'
        ),
        'alert alert-warning'
    );

    if ($blockers !== []) {
        echo html_writer::tag(
            'h3',
            get_string(
                'commerce_identity_merge_blockers',
                'local_subscriptions'
            ),
            ['class' => 'h5 mt-4']
        );
        echo html_writer::start_tag('ul', ['class' => 'list-group mb-4']);
        foreach ($blockers as $blocker) {
            $key = match ($blocker['type']) {
                CommerceCustomerMergeExecutionService::BLOCK_PEDAGOGICAL_HISTORY =>
                    'commerce_identity_merge_blocker_pedagogy',
                CommerceCustomerMergeExecutionService::BLOCK_LEGACY_SUBSCRIPTION =>
                    'commerce_identity_merge_blocker_legacy_subscription',
                CommerceCustomerMergeExecutionService::BLOCK_ALREADY_MERGED =>
                    'commerce_identity_merge_blocker_already_merged',
                CommerceCustomerMergeExecutionService::BLOCK_SUSPENDED_TARGET =>
                    'commerce_identity_merge_blocker_suspended_target',
                default => 'commerce_identity_merge_blocker_generic',
            };
            echo html_writer::tag(
                'li',
                s(get_string(
                    $key,
                    'local_subscriptions',
                    (object)[
                        'userid' => $blocker['userid'],
                        'count' => $blocker['count'],
                    ]
                )),
                ['class' => 'list-group-item list-group-item-danger']
            );
        }
        echo html_writer::end_tag('ul');
    } else {
        echo html_writer::start_tag('form', [
            'method' => 'post',
            'action' => $pageurl->out(false),
            'class' => 'card card-body border-danger mt-4',
        ]);
        echo html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'sesskey',
            'value' => sesskey(),
        ]);
        echo html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'action',
            'value' => 'execute',
        ]);
        echo html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'targetuserid',
            'value' => $plan->targetuserid,
        ]);
        foreach ($userids as $userid) {
            echo html_writer::empty_tag('input', [
                'type' => 'hidden',
                'name' => 'userids[]',
                'value' => $userid,
            ]);
        }

        echo html_writer::div(
            get_string(
                'commerce_identity_merge_execution_warning',
                'local_subscriptions'
            ),
            'alert alert-danger'
        );

        echo html_writer::start_div('form-check mb-3');
        echo html_writer::empty_tag('input', [
            'id' => 'confirm-identity-merge',
            'type' => 'checkbox',
            'name' => 'confirmmerge',
            'value' => '1',
            'required' => 'required',
            'class' => 'form-check-input',
        ]);
        echo html_writer::tag(
            'label',
            get_string(
                'commerce_identity_merge_execution_confirm',
                'local_subscriptions'
            ),
            [
                'for' => 'confirm-identity-merge',
                'class' => 'form-check-label',
            ]
        );
        echo html_writer::end_div();

        echo html_writer::tag(
            'button',
            get_string(
                'commerce_identity_merge_execute',
                'local_subscriptions'
            ),
            [
                'type' => 'submit',
                'class' => 'btn btn-danger',
            ]
        );
        echo html_writer::end_tag('form');
    }
}

echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
