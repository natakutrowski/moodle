<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\subscription_config;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\help\HelpRegistry;
use local_subscriptions\crm\help\HelpSearchService;
use local_subscriptions\crm\help\HelpRenderer;
use local_subscriptions\crm\help\onboarding\HelpOnboardingService;
use local_subscriptions\crm\help\onboarding\HelpOnboardingRenderer;
use local_subscriptions\crm\help\guides\HelpGuideRegistry;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

global $PAGE, $OUTPUT, $USER;

$context = AdminSecurity::require(
    Capabilities::VIEW_DASHBOARD
);

$query = optional_param(
    'q',
    '',
    PARAM_RAW_TRIMMED
);

$categoryid = optional_param(
    'category',
    '',
    PARAM_ALPHANUMEXT
);

$registry = new HelpRegistry();

if (
    $categoryid !== '' &&
    !$registry->category_exists($categoryid)
) {
    $categoryid = '';
}

$urlparams = [];

if ($query !== '') {
    $urlparams['q'] = $query;
}

if ($categoryid !== '') {
    $urlparams['category'] = $categoryid;
}

$url = new moodle_url(
    subscription_config::admin_help_page(),
    $urlparams
);

/*
 * Search has priority over category filtering.
 */
if ($query !== '') {
    $articles = (new HelpSearchService($registry))
        ->search($query);
} else if ($categoryid !== '') {
    $articles = $registry->articles_by_category(
        $categoryid
    );
} else {
    $articles = $registry->articles();
}

$guides = (new HelpGuideRegistry())->guides();

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_title(
    get_string(
        'crm_help_title',
        'local_subscriptions'
    )
);
$PAGE->set_heading(
    get_string(
        'crm_help_title',
        'local_subscriptions'
    )
);

$PAGE->add_body_class(
    'local-subscriptions-crm-workspace'
);
$PAGE->add_body_class(
    'local-subscriptions-help-page'
);

$PAGE->requires->css(
    new moodle_url(
        subscription_config::plugin_stylesheet_page()
    )
);

echo $OUTPUT->header();

echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::HELP,
    $context
);

/*
 * Toolbar containing Help Center administrative actions.
 */
$helpactions = html_writer::link(
    new moodle_url(
        subscription_config::
            admin_help_diagnostics_page()
    ),
    get_string(
        'crm_help_open_diagnostics',
        'local_subscriptions'
    ),
    [
        'class' =>
            'btn btn-sm btn-outline-secondary',
    ]
);

$helpactions .= ' ' .
    html_writer::link(
        new moodle_url(
            subscription_config::
                admin_help_article_page(),
            [
                'id' =>
                    'crm_inbox_diagnostics',
            ]
        ),
        get_string(
            'crm_help_open_inbox_help',
            'local_subscriptions'
        ),
        [
            'class' =>
                'btn btn-sm btn-outline-primary',
        ]
    );

if (
    AdminSecurity::can(
        Capabilities::MANAGE_CONFIGURATION
    )
) {
    $helpactions .= ' ' .
        html_writer::link(
            new moodle_url(
                subscription_config::
                    admin_inbox_diagnostics_page()
            ),
            get_string(
                'crm_help_open_inbox_diagnostics',
                'local_subscriptions'
            ),
            [
                'class' =>
                    'btn btn-sm btn-outline-secondary',
            ]
        );

    if (
        AdminSecurity::can(
            Capabilities::USE_INBOX_AI
        )
    ) {
        $helpactions .= ' ' .
            html_writer::link(
                new moodle_url(
                    subscription_config::
                        admin_inbox_ai_diagnostics_page()
                ),
                get_string(
                    'crm_help_open_inbox_ai_diagnostics',
                    'local_subscriptions'
                ),
                [
                    'class' =>
                        'btn btn-sm btn-outline-secondary',
                ]
            );
    }
}

echo html_writer::div(
    $helpactions,
    'crm-help-toolbar'
);

/*
 * Personal administrator onboarding.
 */
$currenturl = new moodle_url(
    subscription_config::admin_help_page(),
    $urlparams
);

$onboardingstate = (new HelpOnboardingService())
    ->get_state((int)$USER->id);

echo HelpOnboardingRenderer::render(
    $onboardingstate,
    $currenturl->out_as_local_url(false)
);

/*
 * Practical guides.
 *
 * We only show the complete guide catalogue from the Help Center
 * homepage. On a category or search result page, we keep the
 * interface focused on the requested content.
 */
if ($query === '' && $categoryid === '') {
    echo HelpRenderer::render_guides($guides);
}

/*
 * Categories, category contents or search results.
 */
echo HelpRenderer::render_home(
    $registry,
    $articles,
    $query,
    $categoryid
);

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();