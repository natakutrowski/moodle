<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\subscription_config;
use local_subscriptions\crm\help\validation\HelpCenterValidator;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\help\HelpInternalNavigationRenderer;

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


echo CrmPageHeader::render(
    $pagetitle,
    get_string(
        'crm_help_diagnostics_description',
        'local_subscriptions'
    ),
    HelpContext::HELP_CENTER
);

echo HelpInternalNavigationRenderer::render(
    'diagnostics'
);

$successcount = $result->success_count();
$warningcount = $result->warning_count();
$errorcount = $result->error_count();
$totalcount =
    $successcount
    + $warningcount
    + $errorcount;

$healthclass =
    $errorcount > 0
        ? 'crm-help-diagnostics-health--error'
        : (
            $warningcount > 0
                ? 'crm-help-diagnostics-health--warning'
                : 'crm-help-diagnostics-health--ok'
        );

echo html_writer::start_div(
    'crm-help-diagnostics'
);

echo html_writer::start_div(
    'crm-help-diagnostics-overview'
);

echo html_writer::div(
    html_writer::div(
        html_writer::span(
            $errorcount > 0
                ? '!'
                : '✓',
            'crm-help-diagnostics-health__icon'
        )
        . html_writer::div(
            html_writer::tag(
                'strong',
                $result->is_valid()
                    ? get_string(
                        'crm_help_diagnostics_health_ok_n1210a',
                        'local_subscriptions'
                    )
                    : get_string(
                        'crm_help_diagnostics_health_attention_n1210a',
                        'local_subscriptions'
                    )
            )
            . html_writer::div(
                get_string(
                    'crm_help_diagnostics_health_detail_n1210a',
                    'local_subscriptions',
                    (object)[
                        'total' => $totalcount,
                        'errors' => $errorcount,
                        'warnings' => $warningcount,
                    ]
                ),
                'crm-help-diagnostics-health__detail'
            )
        ),
        'crm-help-diagnostics-health '
        . $healthclass
    )
    . html_writer::div(
        html_writer::tag(
            'strong',
            (string)$successcount
        )
        . html_writer::span(
            get_string(
                'crm_help_diagnostics_successes',
                'local_subscriptions'
            )
        ),
        'crm-help-diagnostics-kpi '
        . 'crm-help-diagnostics-kpi--success'
    )
    . html_writer::div(
        html_writer::tag(
            'strong',
            (string)$warningcount
        )
        . html_writer::span(
            get_string(
                'crm_help_diagnostics_warnings',
                'local_subscriptions'
            )
        ),
        'crm-help-diagnostics-kpi '
        . 'crm-help-diagnostics-kpi--warning'
    )
    . html_writer::div(
        html_writer::tag(
            'strong',
            (string)$errorcount
        )
        . html_writer::span(
            get_string(
                'crm_help_diagnostics_errors',
                'local_subscriptions'
            )
        ),
        'crm-help-diagnostics-kpi '
        . 'crm-help-diagnostics-kpi--error'
    ),
    'crm-help-diagnostics-overview-grid'
);

echo html_writer::end_div();

echo html_writer::start_div(
    'crm-help-diagnostics-content'
);

$sections = [
    [
        'title' =>
            get_string(
                'crm_help_diagnostics_errors',
                'local_subscriptions'
            ),
        'description' =>
            get_string(
                'crm_help_diagnostics_errors_help_n1210a',
                'local_subscriptions'
            ),
        'items' => $result->errors(),
        'class' => 'error',
        'open' => true,
    ],
    [
        'title' =>
            get_string(
                'crm_help_diagnostics_warnings',
                'local_subscriptions'
            ),
        'description' =>
            get_string(
                'crm_help_diagnostics_warnings_help_n1210a',
                'local_subscriptions'
            ),
        'items' => $result->warnings(),
        'class' => 'warning',
        'open' => $errorcount === 0,
    ],
    [
        'title' =>
            get_string(
                'crm_help_diagnostics_successes',
                'local_subscriptions'
            ),
        'description' =>
            get_string(
                'crm_help_diagnostics_successes_help_n1210a',
                'local_subscriptions'
            ),
        'items' => $result->successes(),
        'class' => 'success',
        'open' => false,
    ],
];

foreach ($sections as $section) {
    if (!$section['items']) {
        continue;
    }

    $items = '';

    foreach ($section['items'] as $message) {
        $items .= html_writer::tag(
            'li',
            html_writer::span(
                $section['class'] === 'success'
                    ? '✓'
                    : (
                        $section['class'] === 'warning'
                            ? '!'
                            : '×'
                    ),
                'crm-help-diagnostics-item__icon'
            )
            . html_writer::span(
                s($message),
                'crm-help-diagnostics-item__message'
            ),
            [
                'class' =>
                    'crm-help-diagnostics-item',
            ]
        );
    }

    $summary = html_writer::div(
        html_writer::div(
            html_writer::div(
                html_writer::tag(
                    'strong',
                    s($section['title'])
                )
                . html_writer::span(
                    (string)count(
                        $section['items']
                    ),
                    'crm-help-diagnostics-section__count'
                ),
                'crm-help-diagnostics-section__heading'
            )
            . html_writer::div(
                s($section['description']),
                'crm-help-diagnostics-section__description'
            ),
            'crm-help-diagnostics-section__summary-copy'
        )
        . html_writer::span(
            html_writer::tag(
                'i',
                '',
                [
                    'class' =>
                        'fa fa-chevron-down',
                    'aria-hidden' => 'true',
                ]
            ),
            'crm-help-diagnostics-section__chevron',
            [
                'aria-hidden' => 'true',
            ]
        ),
        'crm-help-diagnostics-section__summary'
    );

    echo html_writer::tag(
        'details',
        html_writer::tag(
            'summary',
            $summary
        )
        . html_writer::tag(
            'ul',
            $items,
            [
                'class' =>
                    'crm-help-diagnostics-section__list',
            ]
        ),
        [
            'class' =>
                'crm-help-diagnostics-section '
                . 'crm-help-diagnostics-section--'
                . $section['class'],
            'open' =>
                $section['open']
                    ? 'open'
                    : null,
        ]
    );
}

echo html_writer::end_div();
echo html_writer::end_div();

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();