<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\customer\identity\CommerceCustomerIdentityNavigationRenderer;
use local_subscriptions\commerce\customer\merge\CommerceCustomerMergePlanner;
use local_subscriptions\commerce\customer\merge\CommerceCustomerMergeExecutionService;
use local_subscriptions\commerce\customer\merge\CommerceCustomerMergeFinalStateRenderer;
use local_subscriptions\commerce\customer\merge\CommerceCustomerManualMergeCandidateService;
use local_subscriptions\commerce\customer\merge\CommerceCustomerLearningMergeService;
use local_subscriptions\commerce\customer\merge\CommerceCustomerLegacyConsolidationService;
use local_subscriptions\commerce\customer\merge\CommerceCustomerGamificationMergeService;
use local_subscriptions\commerce\customer\merge\CommerceCustomerAdvancedProfileMergeService;
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
$preferredpassworduserid = optional_param('preferredpassworduserid', 0, PARAM_INT);
$advancedmerge = optional_param('advancedmerge', 0, PARAM_BOOL);
$profilechoices = optional_param_array('profilechoice', [], PARAM_INT);
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
$gamificationmergeservice = new CommerceCustomerGamificationMergeService($DB);
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
                $preferredidentityuserid > 0 ? $preferredidentityuserid : null,
                $preferredpassworduserid > 0 ? $preferredpassworduserid : null,
                $profilechoices
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
        $pedagogy .= "\n" . get_string(
            'commerce_identity_merge_gamification_summary',
            'local_subscriptions',
            (object)['xp' => $profile->levelupxp, 'quests' => $profile->levelupquests]
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

    // Password ownership is an explicit, separate decision. Only the retained account and the
    // selected preferred-login account can be valid owners; unrelated merge sources are excluded.
    $passwordchoices = [$plan->targetuserid];
    if ($preferredidentityuserid !== $plan->targetuserid) {
        $passwordchoices[] = $preferredidentityuserid;
    }
    if ($preferredpassworduserid <= 0 || !in_array($preferredpassworduserid, $passwordchoices, true)) {
        $preferredpassworduserid = $plan->targetuserid;
    }

    echo html_writer::tag('h5', get_string('commerce_identity_merge_preferred_password_title', 'local_subscriptions'), ['class' => 'h6 mt-4']);
    echo html_writer::div(get_string('commerce_identity_merge_preferred_password_help', 'local_subscriptions'), 'small text-muted mb-2');
    foreach ($passwordchoices as $passworduserid) {
        $passwordprofile = null;
        foreach ($plan->profiles as $candidateprofile) {
            if ($candidateprofile->userid() === $passworduserid) {
                $passwordprofile = $candidateprofile;
                break;
            }
        }
        if ($passwordprofile === null) {
            continue;
        }
        $passwordid = 'preferred-password-' . $passworduserid;
        $canselectpassword = $passworduserid === $plan->targetuserid
            || ((string)$plan->target_profile()->user->auth === 'manual'
                && (string)$passwordprofile->user->auth === 'manual');
        $passwordlabel = get_string(
            'commerce_identity_merge_preferred_password_choice',
            'local_subscriptions',
            (object)[
                'userid' => $passworduserid,
                'email' => (string)$passwordprofile->user->email,
            ]
        );
        if (!$canselectpassword) {
            $passwordlabel .= ' — ' . get_string('commerce_identity_merge_preferred_password_unavailable', 'local_subscriptions');
        }
        echo html_writer::start_div('form-check');
        echo html_writer::empty_tag('input', [
            'type' => 'radio', 'class' => 'form-check-input', 'id' => $passwordid,
            'name' => 'preferredpassworduserid', 'value' => $passworduserid,
            'checked' => $preferredpassworduserid === $passworduserid ? 'checked' : null,
            'disabled' => !$canselectpassword ? 'disabled' : null,
        ]);
        echo html_writer::tag('label', s($passwordlabel), ['for' => $passwordid, 'class' => 'form-check-label']);
        echo html_writer::end_div();
    }
    echo html_writer::div(get_string('commerce_identity_merge_preferred_password_safety', 'local_subscriptions'), 'alert alert-info py-2 small mt-3 mb-0');
    echo html_writer::end_div();

    echo html_writer::start_div('card card-body mb-3 commerce-identity-advanced-merge');
    echo html_writer::start_div('form-check form-switch mb-2');
    echo html_writer::empty_tag('input', [
        'type' => 'checkbox', 'class' => 'form-check-input', 'id' => 'advancedmerge',
        'name' => 'advancedmerge', 'value' => '1', 'checked' => $advancedmerge ? 'checked' : null,
    ]);
    echo html_writer::tag('label', get_string('commerce_identity_merge_advanced_mode', 'local_subscriptions'), [
        'for' => 'advancedmerge', 'class' => 'form-check-label fw-semibold',
    ]);
    echo html_writer::end_div();
    echo html_writer::div(get_string('commerce_identity_merge_advanced_mode_help', 'local_subscriptions'), 'small text-muted mb-3');

    if ($advancedmerge) {
        $fieldlabels = [
            'firstname' => get_string('firstname'), 'lastname' => get_string('lastname'),
            'middlename' => get_string('middlename'), 'alternatename' => get_string('alternatename'),
            'idnumber' => get_string('idnumber'), 'institution' => get_string('institution'),
            'department' => get_string('department'), 'phone1' => get_string('phone1'),
            'phone2' => get_string('phone2'), 'address' => get_string('address'),
            'city' => get_string('city'), 'country' => get_string('country'),
            'lang' => get_string('preferredlanguage'), 'timezone' => get_string('timezone'),
            'description' => get_string('userdescription'),
        ];
        $visiblefields = [];
        foreach ($fieldlabels as $field => $label) {
            $values = [];
            foreach ($plan->profiles as $profile) {
                $values[(string)($profile->user->{$field} ?? '')] = true;
            }
            if (count($values) > 1) {
                $visiblefields[$field] = $label;
            }
        }
        $customfields = $DB->get_records('user_info_field', null, 'sortorder ASC, id ASC');
        foreach ($customfields as $customfield) {
            $fieldkey = 'custom_' . (int)$customfield->id;
            $values = [];
            foreach ($plan->profiles as $profile) {
                $data = $DB->get_field('user_info_data', 'data', [
                    'userid' => $profile->userid(), 'fieldid' => (int)$customfield->id,
                ]);
                $values[(string)($data === false ? '' : $data)] = true;
            }
            if (count($values) > 1) {
                $visiblefields[$fieldkey] = (string)$customfield->name;
            }
        }

        if ($visiblefields === []) {
            echo html_writer::div(get_string('commerce_identity_merge_advanced_no_profile_conflicts', 'local_subscriptions'), 'small text-muted');
        } else {
            $atable = new html_table();
            $atable->attributes['class'] = 'generaltable table table-sm align-middle commerce-identity-advanced-table';
            $atable->head = [get_string('commerce_identity_merge_advanced_field', 'local_subscriptions')];
            foreach ($plan->profiles as $index => $profile) {
                $atable->head[] = get_string('commerce_identity_merge_advanced_account_column', 'local_subscriptions', (object)[
                    'letter' => chr(65 + $index), 'userid' => $profile->userid(),
                ]);
            }
            foreach ($visiblefields as $field => $label) {
                $row = [s($label)];
                foreach ($plan->profiles as $profile) {
                    $userid = $profile->userid();
                    $selecteduserid = (int)($profilechoices[$field] ?? $plan->targetuserid);
                    if (preg_match('/^custom_(\d+)$/', $field, $custommatch)) {
                        $customvalue = $DB->get_field('user_info_data', 'data', [
                            'userid' => $userid, 'fieldid' => (int)$custommatch[1],
                        ]);
                        $value = trim((string)($customvalue === false ? '' : $customvalue));
                    } else {
                        $value = trim((string)($profile->user->{$field} ?? ''));
                    }
                    if ($field === 'description') {
                        $value = shorten_text(strip_tags($value), 80);
                    }
                    $inputid = 'profilechoice-' . $field . '-' . $userid;
                    $cell = html_writer::empty_tag('input', [
                        'type' => 'radio', 'name' => 'profilechoice[' . $field . ']', 'value' => $userid,
                        'id' => $inputid, 'class' => 'form-check-input me-2',
                        'checked' => $selecteduserid === $userid ? 'checked' : null,
                    ]);
                    $cell .= html_writer::tag('label', s($value !== '' ? $value : '—'), ['for' => $inputid]);
                    $row[] = $cell;
                }
                $atable->data[] = $row;
            }
            echo html_writer::table($atable);
            echo html_writer::div(get_string('commerce_identity_merge_advanced_identity_note', 'local_subscriptions'), 'alert alert-info py-2 small mb-0');
        }
    }
    echo html_writer::end_div();

    echo html_writer::tag(
        'button',
        get_string('commerce_identity_merge_recalculate', 'local_subscriptions'),
        ['type' => 'submit', 'class' => 'btn btn-outline-primary mb-4']
    );
    echo html_writer::end_tag('form');

    $target = $plan->target_profile();
    echo CommerceCustomerMergeFinalStateRenderer::render(
        $plan,
        $preferredidentityuserid,
        $learningmergeservice,
        $legacymergeservice,
        $mergefullname,
        $learningresolutions
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
                    $preferredidentityuserid !== $plan->targetuserid
                        ? 'commerce_identity_merge_warning_different_emails_transfer'
                        : 'commerce_identity_merge_warning_different_emails',
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
    $gamificationpreviewtotal = 0;
    foreach ($plan->source_profiles() as $sourceprofile) {
        $learningpreviewtotal += array_sum($learningmergeservice->preview($sourceprofile->userid()));
        $legacypreviewtotal += array_sum($legacymergeservice->preview($sourceprofile->userid()));
        $gamificationpreviewtotal += array_sum($gamificationmergeservice->preview($sourceprofile->userid()));
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
                    'gamification' => $gamificationpreviewtotal,
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
        echo html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'preferredpassworduserid',
            'value' => $preferredpassworduserid > 0 ? $preferredpassworduserid : $plan->targetuserid,
        ]);
        echo html_writer::empty_tag('input', [
            'type' => 'hidden', 'name' => 'advancedmerge', 'value' => $advancedmerge ? '1' : '0',
        ]);
        foreach ($profilechoices as $field => $sourceuserid) {
            if (in_array($field, CommerceCustomerAdvancedProfileMergeService::FIELDS, true) || preg_match('/^custom_\d+$/', (string)$field)) {
                echo html_writer::empty_tag('input', [
                    'type' => 'hidden', 'name' => 'profilechoice[' . $field . ']', 'value' => (int)$sourceuserid,
                ]);
            }
        }
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

        $confirmationidentity = $target;
        foreach ($plan->profiles as $profile) {
            if ($profile->userid() === $preferredidentityuserid) {
                $confirmationidentity = $profile;
                break;
            }
        }
        $confirmationemail = $preferredidentityuserid !== $plan->targetuserid
            ? (string)$confirmationidentity->user->email
            : (string)$target->user->email;
        echo html_writer::div(
            s(get_string('commerce_identity_merge_final_confirmation', 'local_subscriptions', (object)[
                'userid' => $target->userid(),
                'email' => $confirmationemail,
                'sources' => count($plan->source_profiles()),
            ])),
            'm13d-confirm-banner'
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
