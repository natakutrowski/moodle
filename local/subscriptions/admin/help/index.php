<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\help\guides\HelpGuideRegistry;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\help\HelpRegistry;
use local_subscriptions\crm\help\HelpRenderer;
use local_subscriptions\crm\help\HelpInternalNavigationRenderer;
use local_subscriptions\crm\help\HelpSearchService;
use local_subscriptions\crm\help\onboarding\HelpOnboardingRenderer;
use local_subscriptions\crm\help\onboarding\HelpOnboardingService;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\subscription_config;

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

$section = optional_param(
    'section',
    '',
    PARAM_ALPHANUMEXT
);

$registry = new HelpRegistry();

if (
    $categoryid !== '' &&
    !$registry->category_exists(
        $categoryid
    )
) {
    $categoryid = '';
}

if (
    !in_array(
        $section,
        ['guides', 'articles'],
        true
    )
) {
    $section = '';
}

/*
 * A search or a concrete category is already a documentation view.
 */
if (
    $query !== ''
    || $categoryid !== ''
) {
    $section = 'articles';
}

$urlparams = [];

if ($query !== '') {
    $urlparams['q'] = $query;
}

if ($categoryid !== '') {
    $urlparams['category'] =
        $categoryid;
}

if ($section !== '') {
    $urlparams['section'] =
        $section;
}

$url = new moodle_url(
    subscription_config::
        admin_help_page(),
    $urlparams
);

/*
 * Search has priority over category filtering.
 */
if ($query !== '') {
    $articles = (
        new HelpSearchService(
            $registry
        )
    )->search(
        $query
    );
} else if ($categoryid !== '') {
    $articles =
        $registry->articles_by_category(
            $categoryid
        );
} else {
    $articles =
        $registry->articles();
}

$guides = (
    new HelpGuideRegistry()
)->guides();

$pagetitle =
    $section === 'guides'
        ? get_string(
            'crm_help_guides_title',
            'local_subscriptions'
        )
        : (
            $section === 'articles'
                ? get_string(
                    'crm_help_documentation_title_n1210d',
                    'local_subscriptions'
                )
                : get_string(
                    'crm_help_title',
                    'local_subscriptions'
                )
        );

$pagesubtitle =
    $section === 'guides'
        ? get_string(
            'crm_help_guides_description',
            'local_subscriptions'
        )
        : (
            $section === 'articles'
                ? get_string(
                    'crm_help_documentation_description_n1210d',
                    'local_subscriptions'
                )
                : get_string(
                    'crm_help_home_subtitle',
                    'local_subscriptions'
                )
        );

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $url,
    $pagetitle,
    'local-subscriptions-help-page'
);

echo $OUTPUT->header();

echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::HELP,
    $context
);

$breadcrumbs = [
    [
        'label' => get_string(
            'crm_help_title',
            'local_subscriptions'
        ),
        'url' =>
            $section !== ''
                ? new moodle_url(
                    subscription_config::
                        admin_help_page()
                )
                : null,
    ],
];

if ($section !== '') {
    $breadcrumbs[] = [
        'label' => $pagetitle,
        'url' => null,
    ];
}

echo CrmBreadcrumbRenderer::render(
    $breadcrumbs
);

echo CrmPageHeader::render(
    $pagetitle,
    $pagesubtitle,
    HelpContext::HELP_CENTER
);

$helpnavactive =
    $section === 'guides'
        ? 'guides'
        : (
            $section === 'articles'
                ? 'articles'
                : 'home'
        );

echo HelpInternalNavigationRenderer::render(
    $helpnavactive
);

/*
 * Compact Help Center utility navigation.
 */
$quicklinks = [];

$quicklinks[] = [
    'url' =>
        new moodle_url(
            subscription_config::
                admin_help_diagnostics_page()
        ),
    'label' =>
        get_string(
            'crm_help_open_diagnostics',
            'local_subscriptions'
        ),
    'icon' => 'fa-check-circle',
];

$quicklinks[] = [
    'url' =>
        new moodle_url(
            subscription_config::
                admin_help_article_page(),
            [
                'id' =>
                    'crm_inbox_diagnostics',
            ]
        ),
    'label' =>
        get_string(
            'crm_help_open_inbox_help',
            'local_subscriptions'
        ),
    'icon' => 'fa-book',
];

if (
    AdminSecurity::can(
        Capabilities::MANAGE_CONFIGURATION
    )
) {
    $quicklinks[] = [
        'url' =>
            new moodle_url(
                subscription_config::
                    admin_inbox_diagnostics_page()
            ),
        'label' =>
            get_string(
                'crm_help_open_inbox_diagnostics',
                'local_subscriptions'
            ),
        'icon' => 'fa-stethoscope',
    ];

    if (
        AdminSecurity::can(
            Capabilities::USE_INBOX_AI
        )
    ) {
        $quicklinks[] = [
            'url' =>
                new moodle_url(
                    subscription_config::
                        admin_inbox_ai_diagnostics_page()
                ),
            'label' =>
                get_string(
                    'crm_help_open_inbox_ai_diagnostics',
                    'local_subscriptions'
                ),
            'icon' => 'fa-magic',
        ];
    }
}

$quicknav = '';

foreach ($quicklinks as $link) {
    $quicknav .= html_writer::link(
        $link['url'],
        html_writer::tag(
            'i',
            '',
            [
                'class' =>
                    'fa ' . $link['icon'],
                'aria-hidden' => 'true',
            ]
        )
        . html_writer::span(
            s($link['label'])
        ),
        [
            'class' =>
                'crm-help-quick-nav__link',
        ]
    );
}

echo html_writer::tag(
    'nav',
    $quicknav,
    [
        'class' =>
            'crm-help-quick-nav',
        'aria-label' =>
            get_string(
                'crm_help_quick_navigation_n1210b',
                'local_subscriptions'
            ),
    ]
);

echo HelpRenderer::render_search_hero(
    $query
);

/*
 * Home combines onboarding, guides and documentation.
 * Section links deliberately switch to focused Help Center views.
 */
if (
    $query === ''
    && $categoryid === ''
    && $section === ''
) {
    $currenturl = new moodle_url(
        subscription_config::
            admin_help_page()
    );

    $onboardingstate = (
        new HelpOnboardingService()
    )->get_state(
        (int)$USER->id
    );

    echo HelpOnboardingRenderer::render(
        $onboardingstate,
        $currenturl->out_as_local_url(
            false
        )
    );
}

if (
    $query === ''
    && $categoryid === ''
    && $section !== 'articles'
) {
    echo html_writer::div(
        HelpRenderer::render_guides(
            $guides
        ),
        'crm-help-anchor-target',
        ['id' => 'crm-help-guides']
    );
}

if ($section !== 'guides') {
    echo html_writer::div(
        HelpRenderer::render_home(
            $registry,
            $articles,
            $query,
            $categoryid
        ),
        'crm-help-anchor-target',
        ['id' => 'crm-help-articles']
    );
}

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();