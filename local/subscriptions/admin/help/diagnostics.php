<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\subscription_config;
use local_subscriptions\crm\help\validation\HelpCenterValidator;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\crm\navigation\CrmBackLinkRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;

global $PAGE, $OUTPUT;

$context = AdminSecurity::require(
    Capabilities::MANAGE_CONFIGURATION
);

$url = new moodle_url(
    subscription_config::admin_help_diagnostics_page()
);

$pagetitle = get_string(
    'crm_help_diagnostics_title',
    'local_subscriptions'
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $url,
    $pagetitle,
    [
        'local-subscriptions-help-page',
        'local-subscriptions-help-diagnostics-page',
    ]
);

$result = (new HelpCenterValidator())->validate();

echo $OUTPUT->header();

echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::HELP,
    $context
);

echo CrmBreadcrumbRenderer::render(
    [
        [
            'label' =>
                get_string(
                    'crm_help_title',
                    'local_subscriptions'
                ),

            'url' =>
                new moodle_url(
                    subscription_config::
                        admin_help_page()
                ),
        ],
        [
            'label' =>
                $pagetitle,

            'url' =>
                null,
        ],
    ]
);

echo CrmBackLinkRenderer::render(
    new moodle_url(
        subscription_config::
            admin_help_page()
    ),
    get_string(
        'crm_help_home',
        'local_subscriptions'
    ),
    [
        'crm-help-back-link',
    ]
);

echo CrmPageHeader::render(
    $pagetitle,
    get_string(
        'crm_help_diagnostics_description',
        'local_subscriptions'
    ),
    HelpContext::HELP_CENTER
);

echo html_writer::start_div(
    'crm-help-diagnostics'
);

echo html_writer::start_div(
    'crm-help-diagnostics-summary'
);

echo html_writer::div(
    html_writer::tag('strong',
        (string)$result->success_count()
    ) .
    html_writer::span(
        get_string(
            'crm_help_diagnostics_successes',
            'local_subscriptions'
        )
    ),
    'crm-help-diagnostics-stat success'
);

echo html_writer::div(
    html_writer::tag('strong',
        (string)$result->warning_count()
    ) .
    html_writer::span(
        get_string(
            'crm_help_diagnostics_warnings',
            'local_subscriptions'
        )
    ),
    'crm-help-diagnostics-stat warning'
);

echo html_writer::div(
    html_writer::tag('strong',
        (string)$result->error_count()
    ) .
    html_writer::span(
        get_string(
            'crm_help_diagnostics_errors',
            'local_subscriptions'
        )
    ),
    'crm-help-diagnostics-stat error'
);

echo html_writer::end_div();

if ($result->is_valid()) {
    echo html_writer::div(
        get_string(
            'crm_help_diagnostics_valid',
            'local_subscriptions'
        ),
        'alert alert-success'
    );
} else {
    echo html_writer::div(
        get_string(
            'crm_help_diagnostics_invalid',
            'local_subscriptions'
        ),
        'alert alert-danger'
    );
}

$sections = [
    [
        'title' => get_string(
            'crm_help_diagnostics_errors',
            'local_subscriptions'
        ),
        'items' => $result->errors(),
        'class' => 'error',
    ],
    [
        'title' => get_string(
            'crm_help_diagnostics_warnings',
            'local_subscriptions'
        ),
        'items' => $result->warnings(),
        'class' => 'warning',
    ],
    [
        'title' => get_string(
            'crm_help_diagnostics_successes',
            'local_subscriptions'
        ),
        'items' => $result->successes(),
        'class' => 'success',
    ],
];

foreach ($sections as $section) {
    if (!$section['items']) {
        continue;
    }

    echo html_writer::start_div(
        'crm-help-diagnostics-section ' .
        $section['class']
    );

    echo html_writer::tag(
        'h3',
        $section['title'],
        [
            'class' =>
                'crm-help-diagnostics-section-title',
        ]
    );

    echo html_writer::start_tag('ul');

    foreach ($section['items'] as $message) {
        echo html_writer::tag(
            'li',
            s($message)
        );
    }

    echo html_writer::end_tag('ul');
    echo html_writer::end_div();
}

echo html_writer::end_div();

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();