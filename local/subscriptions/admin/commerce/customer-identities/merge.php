<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\customer\identity\CommerceCustomerIdentityNavigationRenderer;
use local_subscriptions\commerce\customer\merge\CommerceCustomerMergePlanner;
use local_subscriptions\commerce\customer\merge\CommerceCustomerMergeExecutionService;
use local_subscriptions\commerce\customer\merge\CommerceCustomerManualMergeCandidateService;
use local_subscriptions\commerce\customer\merge\CommerceCustomerLearningMergeService;
use local_subscriptions\commerce\customer\merge\CommerceCustomerLegacyConsolidationService;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_USERS);

/**
 * Render a Moodle fullname safely even when a domain service returned
 * a deliberately partial user projection.
 *
 * Moodle 5 core_user::get_fullname() requires every configured name field
 * to exist on the object. For UI rendering we therefore reload the canonical
 * {user} record whenever one of those fields is absent.
 */
$mergefullname = static function(object $user) use ($DB): string {
    static $cache = [];

    $required = [
        'firstname',
        'lastname',
        'firstnamephonetic',
        'lastnamephonetic',
        'middlename',
        'alternatename',
    ];

    $complete = true;
    foreach ($required as $field) {
        if (!property_exists($user, $field)) {
            $complete = false;
            break;
        }
    }

    if (!$complete && !empty($user->id)) {
        $userid = (int)$user->id;
        if (!array_key_exists($userid, $cache)) {
            $cache[$userid] = $DB->get_record(
                'user',
                ['id' => $userid],
                '*',
                IGNORE_MISSING
            ) ?: null;
        }
        if ($cache[$userid] !== null) {
            $user = $cache[$userid];
        }
    }

    // Synthetic/non-persisted objects are normalised as a final fallback.
    foreach ($required as $field) {
        if (!property_exists($user, $field)) {
            $user->{$field} = '';
        }
    }

    return fullname($user);
};


$userids = optional_param_array('userids', [], PARAM_INT);
$ids = trim(optional_param('ids', '', PARAM_RAW_TRIMMED));
$targetuserid = optional_param('targetuserid', 0, PARAM_INT);
$preferredidentityuserid = optional_param('preferredidentityuserid', 0, PARAM_INT);
$q = trim(optional_param('q', '', PARAM_RAW_TRIMMED));
$adduserid = optional_param('adduserid', 0, PARAM_INT);
$removeuserid = optional_param('removeuserid', 0, PARAM_INT);
$learningresolutions = optional_param_array('learningresolution', [], PARAM_ALPHA);
$learningresolutions = array_filter($learningresolutions, static fn($v) => in_array($v, ['source', 'target'], true));

if ($ids !== '') {
    foreach (preg_split('/[\s,;]+/', $ids) ?: [] as $candidate) {
        $candidate = (int)$candidate;
        if ($candidate > 0) {
            $userids[] = $candidate;
        }
    }
}
if ($adduserid > 1) {
    $userids[] = $adduserid;
}
if ($removeuserid > 1) {
    $userids = array_values(array_filter(
        $userids,
        static fn(int $userid): bool => (int)$userid !== $removeuserid
    ));
}
$userids = array_values(array_unique(array_filter(
    array_map('intval', $userids),
    static fn(int $userid): bool => $userid > 1
)));
if (count($userids) > CommerceCustomerMergePlanner::MAX_ACCOUNTS) {
    $userids = array_slice($userids, 0, CommerceCustomerMergePlanner::MAX_ACCOUNTS);
}

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
$PAGE->requires->css(new moodle_url('/local/subscriptions/styles/customer_identity_merge.css'));

$plan = null;
$error = null;
$executionresult = null;
$blockers = [];
$planner = new CommerceCustomerMergePlanner($DB);
$executor = new CommerceCustomerMergeExecutionService($DB, $planner);
$candidateservice = new CommerceCustomerManualMergeCandidateService($DB);
$learningmergeservice = new CommerceCustomerLearningMergeService($DB);
$legacymergeservice = new CommerceCustomerLegacyConsolidationService($DB);
$selectedusers = $candidateservice->selected($userids);
$searchresults = $q !== '' ? $candidateservice->search($q, $userids) : [];
$defaultaction = count($userids) >= CommerceCustomerMergePlanner::MIN_ACCOUNTS ? 'preview' : 'select';
$action = optional_param('action', $defaultaction, PARAM_ALPHA);

if (count($userids) >= CommerceCustomerMergePlanner::MIN_ACCOUNTS && in_array($action, ['preview', 'execute'], true)) {
    try {
        $plan = $planner->build(
            $userids,
            $targetuserid > 0 ? $targetuserid : null
        );
        $allblockers = $executor->blockers($plan, $learningresolutions);
        $blockers = array_values(array_filter($allblockers, static fn(array $b): bool =>
            $b['type'] !== CommerceCustomerLearningMergeService::BLOCK_UNRESOLVED_CONFLICT
        ));

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
                (int)$USER->id,
                $learningresolutions,
                $preferredidentityuserid > 0 ? $preferredidentityuserid : null
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

echo html_writer::start_div('m734-merge-selector');
echo html_writer::tag(
    'h3',
    get_string('commerce_identity_merge_manual_selection_title', 'local_subscriptions'),
    ['class' => 'm734-selector-title']
);
echo html_writer::div(
    get_string('commerce_identity_merge_manual_selection_help', 'local_subscriptions'),
    'm734-selector-help'
);

if ($selectedusers !== []) {
    echo html_writer::start_div('m734-selected-accounts');
    foreach ($selectedusers as $selecteduser) {
        $removeparams = ['removeuserid' => (int)$selecteduser->id];
        foreach ($userids as $selectedid) {
            if ((int)$selectedid !== (int)$selecteduser->id) {
                $removeparams['userids'][] = (int)$selectedid;
            }
        }
        $accountname = $mergefullname($selecteduser);
        $badges = [];
        $badges[] = (int)$selecteduser->suspended === 0
            ? html_writer::span(
                get_string('commerce_identity_similarity_account_active', 'local_subscriptions'),
                'badge bg-success-subtle text-success-emphasis'
            )
            : html_writer::span(
                get_string('commerce_identity_similarity_account_suspended', 'local_subscriptions'),
                'badge bg-warning-subtle text-warning-emphasis'
            );
        if (!empty($selecteduser->confirmed)) {
            $badges[] = html_writer::span(
                get_string('commerce_identity_merge_confirmed', 'local_subscriptions'),
                'badge bg-light text-dark border'
            );
        }

        echo html_writer::tag(
            'article',
            html_writer::div(
                html_writer::tag('strong', s($accountname !== '' ? $accountname : ('#' . $selecteduser->id))) .
                html_writer::div(
                    s((string)$selecteduser->email) . ' · #' . (int)$selecteduser->id,
                    'm734-account-meta'
                ) .
                html_writer::div(implode(' ', $badges), 'm734-account-badges'),
                'm734-account-copy'
            ) .
            html_writer::div(
                html_writer::link(
                    new moodle_url('/local/subscriptions/admin/users/view.php', ['id' => (int)$selecteduser->id]),
                    get_string('view'),
                    ['class' => 'btn btn-sm btn-outline-secondary']
                ) .
                html_writer::link(
                    new moodle_url('/local/subscriptions/admin/commerce/customer-identities/merge.php', $removeparams),
                    get_string('remove'),
                    ['class' => 'btn btn-sm btn-outline-danger']
                ),
                'm734-account-actions'
            ),
            ['class' => 'm734-selected-account']
        );
    }
    echo html_writer::end_div();
}

echo html_writer::start_tag('form', [
    'method' => 'get',
    'action' => $pageurl->out(false),
    'class' => 'm734-search-form',
]);
foreach ($userids as $userid) {
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'userids[]',
        'value' => $userid,
    ]);
}
echo html_writer::tag(
    'label',
    get_string('commerce_identity_merge_search_label', 'local_subscriptions'),
    ['for' => 'merge-account-search', 'class' => 'form-label']
);
echo html_writer::start_div('input-group');
echo html_writer::empty_tag('input', [
    'id' => 'merge-account-search',
    'name' => 'q',
    'type' => 'search',
    'value' => $q,
    'class' => 'form-control',
    'placeholder' => get_string('commerce_identity_merge_search_placeholder', 'local_subscriptions'),
    'autocomplete' => 'off',
]);
echo html_writer::tag(
    'button',
    get_string('search'),
    ['type' => 'submit', 'class' => 'btn btn-outline-primary']
);
echo html_writer::end_div();
echo html_writer::end_tag('form');

if ($q !== '') {
    echo html_writer::tag(
        'h4',
        get_string('commerce_identity_merge_search_results', 'local_subscriptions'),
        ['class' => 'm734-results-title']
    );
    if ($searchresults === []) {
        echo html_writer::div(
            get_string('commerce_identity_merge_search_empty', 'local_subscriptions'),
            'alert alert-light border'
        );
    } else {
        echo html_writer::start_div('m734-search-results');
        foreach ($searchresults as $candidate) {
            $addparams = ['adduserid' => (int)$candidate->id];
            foreach ($userids as $selectedid) {
                $addparams['userids'][] = (int)$selectedid;
            }
            $candidatefullname = $mergefullname($candidate);
            echo html_writer::tag(
                'article',
                html_writer::div(
                    html_writer::tag('strong', s($candidatefullname !== '' ? $candidatefullname : ('#' . $candidate->id))) .
                    html_writer::div(
                        s((string)$candidate->email) . ' · #' . (int)$candidate->id . ' · ' . s((string)$candidate->username),
                        'm734-account-meta'
                    ),
                    'm734-account-copy'
                ) .
                html_writer::link(
                    new moodle_url('/local/subscriptions/admin/commerce/customer-identities/merge.php', $addparams),
                    get_string('commerce_identity_merge_add_account', 'local_subscriptions'),
                    ['class' => 'btn btn-sm btn-primary']
                ),
                ['class' => 'm734-search-result']
            );
        }
        echo html_writer::end_div();
    }
}

if (count($userids) >= CommerceCustomerMergePlanner::MIN_ACCOUNTS) {
    $previewparams = ['action' => 'preview'];
    foreach ($userids as $userid) {
        $previewparams['userids'][] = $userid;
    }
    echo html_writer::div(
        html_writer::link(
            new moodle_url('/local/subscriptions/admin/commerce/customer-identities/merge.php', $previewparams),
            get_string('commerce_identity_merge_preview', 'local_subscriptions'),
            ['class' => 'btn btn-primary']
        ) .
        html_writer::link(
            $pageurl,
            get_string('commerce_identity_merge_reset_selection', 'local_subscriptions'),
            ['class' => 'btn btn-outline-secondary']
        ),
        'm734-selector-footer'
    );
} else {
    echo html_writer::div(
        get_string('commerce_identity_merge_select_two_hint', 'local_subscriptions'),
        'm734-select-hint'
    );
}

echo html_writer::end_div();

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

    $certsummary = $executionresult->certification['summary'] ?? [];
    echo html_writer::start_div('card card-body border-success mb-4');
    echo html_writer::tag('h3',
        get_string('commerce_identity_merge_certification_title', 'local_subscriptions'),
        ['class' => 'h5 mb-2']);
    echo html_writer::div(get_string('commerce_identity_merge_certification_summary',
        'local_subscriptions', (object)[
            'checks' => (int)($certsummary['passed'] ?? 0),
            'decisions' => (int)($certsummary['manualdecisions'] ?? 0),
        ]), 'text-muted mb-3');
    echo html_writer::start_tag('ul', ['class' => 'list-group list-group-flush']);
    foreach ([
        'primary_account_active', 'merged_account_suspended', 'ownership_transferred',
        'learning_state_transferred', 'manual_learning_decision_applied', 'customer_email_aligned',
    ] as $checktype) {
        $count = count(array_filter($executionresult->certification['checks'] ?? [],
            static fn(array $check): bool => ($check['type'] ?? '') === $checktype && !empty($check['passed'])));
        if ($count === 0 && $checktype === 'manual_learning_decision_applied') {
            continue;
        }
        echo html_writer::tag('li',
            html_writer::span('✓', 'text-success fw-bold me-2')
                . s(get_string('commerce_identity_merge_certification_' . $checktype,
                    'local_subscriptions', $count)),
            ['class' => 'list-group-item px-0']);
    }
    echo html_writer::end_tag('ul');
    echo html_writer::div(get_string('commerce_identity_merge_certification_audit',
        'local_subscriptions', $executionresult->mergeuuid), 'small text-muted mt-3');
    echo html_writer::end_div();
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
        'name' => 'action',
        'value' => 'preview',
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

        $name = $mergefullname($profile->user);
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

    if ($preferredidentityuserid <= 0 || !in_array($preferredidentityuserid, $userids, true)) {
        $preferredidentityuserid = $plan->targetuserid;
    }
    echo html_writer::start_div('card card-body mb-3');
    echo html_writer::tag('h4', get_string('commerce_identity_merge_preferred_identity_title', 'local_subscriptions'), ['class' => 'h6']);
    echo html_writer::div(get_string('commerce_identity_merge_preferred_identity_help', 'local_subscriptions'), 'small text-muted mb-2');
    foreach ($plan->profiles as $identityprofile) {
        $identityuserid = $identityprofile->userid();
        $identityid = 'preferred-identity-' . $identityuserid;
        $identitylabel = get_string(
            'commerce_identity_merge_preferred_identity_choice',
            'local_subscriptions',
            (object)[
                'userid' => $identityuserid,
                'email' => (string)$identityprofile->user->email,
                'username' => (string)$identityprofile->user->username,
            ]
        );
        echo html_writer::start_div('form-check');
        echo html_writer::empty_tag('input', [
            'type' => 'radio', 'class' => 'form-check-input', 'id' => $identityid,
            'name' => 'preferredidentityuserid', 'value' => $identityuserid,
            'checked' => $preferredidentityuserid === $identityuserid ? 'checked' : null,
        ]);
        echo html_writer::tag('label', s($identitylabel), ['for' => $identityid, 'class' => 'form-check-label']);
        echo html_writer::end_div();
    }
    echo html_writer::div(get_string('commerce_identity_merge_preferred_identity_safety', 'local_subscriptions'), 'alert alert-warning py-2 small mt-3 mb-0');
    echo html_writer::end_div();

    echo html_writer::tag(
        'button',
        get_string('commerce_identity_merge_recalculate', 'local_subscriptions'),
        ['type' => 'submit', 'class' => 'btn btn-outline-primary mb-4']
    );
    echo html_writer::end_tag('form');

    $target = $plan->target_profile();
    $sourcenames = array_map(
        static fn($profile): string => '#' . $profile->userid() . ' ' . $mergefullname($profile->user),
        $plan->source_profiles()
    );
    echo html_writer::tag(
        'section',
        html_writer::div(
            html_writer::tag('span', get_string('commerce_identity_merge_direction_sources', 'local_subscriptions'), ['class' => 'm734-direction-label']) .
            html_writer::tag('strong', s(implode(' + ', $sourcenames))),
            'm734-direction-side'
        ) .
        html_writer::div('→', 'm734-direction-arrow') .
        html_writer::div(
            html_writer::tag('span', get_string('commerce_identity_merge_direction_target', 'local_subscriptions'), ['class' => 'm734-direction-label']) .
            html_writer::tag('strong', s('#' . $target->userid() . ' ' . $mergefullname($target->user))),
            'm734-direction-side m734-direction-side--target'
        ),
        ['class' => 'm734-merge-direction']
    );

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
                'name' => $mergefullname($target->user),
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

    $learningpreviewtotal = 0;
    $legacypreviewtotal = 0;
    foreach ($plan->source_profiles() as $sourceprofile) {
        $learningpreviewtotal += array_sum($learningmergeservice->preview($sourceprofile->userid()));
        $legacypreviewtotal += array_sum($legacymergeservice->preview($sourceprofile->userid()));
    }
    echo html_writer::div(
        html_writer::tag(
            'strong',
            get_string('commerce_identity_merge_m756_scope_title', 'local_subscriptions')
        ) .
        html_writer::div(
            get_string(
                'commerce_identity_merge_m756_scope_detail',
                'local_subscriptions',
                (object)[
                    'learning' => $learningpreviewtotal,
                    'commerce' => $legacypreviewtotal,
                ]
            ),
            'small text-muted mt-1'
        ),
        'alert alert-success'
    );

    $learningconflicts = [];
    foreach ($plan->source_profiles() as $sourceprofile) {
        foreach ($learningmergeservice->conflicts($sourceprofile->userid(), $plan->targetuserid) as $conflict) {
            $learningconflicts[] = $conflict;
        }
    }

    if ($learningconflicts !== []) {
        echo html_writer::tag('h3', get_string('commerce_identity_merge_conflicts_title', 'local_subscriptions'), ['class' => 'h5 mt-4']);
        echo html_writer::div(get_string('commerce_identity_merge_conflicts_help', 'local_subscriptions'), 'alert alert-info');
    }

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
                CommerceCustomerMergeExecutionService::BLOCK_PRIVILEGED_ACCOUNT =>
                    'commerce_identity_merge_blocker_privileged',
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
        echo html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'preferredidentityuserid',
            'value' => $preferredidentityuserid > 0 ? $preferredidentityuserid : $plan->targetuserid,
        ]);
        foreach ($userids as $userid) {
            echo html_writer::empty_tag('input', [
                'type' => 'hidden',
                'name' => 'userids[]',
                'value' => $userid,
            ]);
        }

        foreach ($learningconflicts as $conflict) {
            $label = get_string(
                $conflict['type'] === 'grade' ? 'commerce_identity_merge_conflict_grade' : 'commerce_identity_merge_conflict_activity',
                'local_subscriptions',
                (object)['id' => $conflict['itemid']]
            );
            echo html_writer::start_div('card card-body mb-3 commerce-identity-learning-conflict');
            echo html_writer::tag('strong', s($label));
            echo html_writer::div(get_string('commerce_identity_merge_conflict_recommended', 'local_subscriptions',
                strtoupper($conflict['recommended'] === 'source' ? 'A' : 'B')), 'small text-muted mb-2');
            foreach (['source' => 'A', 'target' => 'B'] as $choice => $letter) {
                $userid = $choice === 'source' ? $conflict['sourceuserid'] : $conflict['targetuserid'];
                $value = $choice === 'source' ? $conflict['sourcevalue'] : $conflict['targetvalue'];
                $id = 'learning-' . $conflict['id'] . '-' . $choice;
                echo html_writer::start_div('form-check');
                echo html_writer::empty_tag('input', [
                    'type' => 'radio', 'class' => 'form-check-input', 'id' => $id,
                    'name' => 'learningresolution[' . $conflict['id'] . ']', 'value' => $choice,
                    'required' => 'required',
                    'checked' => ($learningresolutions[$conflict['id']] ?? '') === $choice ? 'checked' : null,
                ]);
                echo html_writer::tag('label', get_string('commerce_identity_merge_conflict_choice', 'local_subscriptions',
                    (object)['letter' => $letter, 'userid' => $userid, 'value' => $value]),
                    ['for' => $id, 'class' => 'form-check-label']);
                echo html_writer::end_div();
            }
            echo html_writer::end_div();
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
