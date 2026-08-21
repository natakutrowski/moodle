<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\inbox\ai\repositories\InboxAiUsageRepository;
use local_subscriptions\crm\inbox\ai\services\InboxAiDiagnosticsService;
use local_subscriptions\crm\inbox\ai\services\InboxAiQuotaService;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\crm\navigation\CrmBackLinkRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\subscription_config;

$context = AdminSecurity::require(
    Capabilities::MANAGE_CONFIGURATION
);

$usage = new InboxAiUsageRepository();

$result = (
    new InboxAiDiagnosticsService(
        $usage,
        new InboxAiQuotaService($usage)
    )
)->diagnose(
    (int)$USER->id
);

$pageurl = new moodle_url(
    subscription_config::
        admin_inbox_ai_diagnostics_page()
);

$pagetitle = get_string(
    'crm_inbox_ai_diagnostics',
    'local_subscriptions'
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    [
        'local-subscriptions-inbox-page',
        'local-subscriptions-inbox-ai-diagnostics-page',
    ]
);

$PAGE->requires->js_call_amd(
    'local_subscriptions/inbox_ui',
    'init'
);

echo $OUTPUT->header();

echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::INBOX,
    $context
);

echo CrmBreadcrumbRenderer::render(
    [
        [
            'label' =>
                get_string(
                    'crm_inbox_title',
                    'local_subscriptions'
                ),

            'url' =>
                new moodle_url(
                    subscription_config::
                        admin_inbox_page()
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
            admin_inbox_page()
    ),
    get_string(
        'crm_inbox_back',
        'local_subscriptions'
    )
);

echo CrmPageHeader::render(
    get_string(
        'crm_inbox_ai_diagnostics',
        'local_subscriptions'
    ),
    get_string(
        'crm_inbox_ai_diagnostics_help_subtitle',
        'local_subscriptions'
    ),
    HelpContext::INBOX_AI
);

$checksok = 0;

foreach ($result['checks'] as $check) {
    if (!empty($check['success'])) {
        $checksok++;
    }
}

$checktotal = count(
    $result['checks']
);

$failures = (int)$result['failures'];

$healthclass =
    $checksok === $checktotal && $failures === 0
        ? 'crm-ai-diagnostics-health--ok'
        : 'crm-ai-diagnostics-health--warning';

echo html_writer::start_div(
    'crm-ai-diagnostics'
);

echo html_writer::start_div(
    'crm-ai-diagnostics-summary'
);

echo html_writer::div(
    html_writer::span(
        '✓',
        'crm-ai-diagnostics-summary__icon'
    )
    . html_writer::div(
        html_writer::tag(
            'strong',
            get_string(
                'crm_inbox_ai_diag_health_title_n129d',
                'local_subscriptions'
            )
        )
        . html_writer::div(
            get_string(
                'crm_inbox_ai_diag_health_detail_n129d',
                'local_subscriptions',
                (object)[
                    'ok' => $checksok,
                    'total' => $checktotal,
                ]
            ),
            'crm-ai-diagnostics-summary__detail'
        )
    ),
    'crm-ai-diagnostics-summary__health ' .
        $healthclass
);

echo html_writer::div(
    html_writer::div(
        (string)$result['usage']['global']
        . ' / '
        . (string)$result['usage']['globallimit'],
        'crm-ai-diagnostics-kpi__value'
    )
    . html_writer::div(
        get_string(
            'crm_inbox_ai_usage_global_label_n129d',
            'local_subscriptions'
        ),
        'crm-ai-diagnostics-kpi__label'
    ),
    'crm-ai-diagnostics-kpi'
);

echo html_writer::div(
    html_writer::div(
        (string)($result['usage']['user'] ?? 0)
        . ' / '
        . (string)$result['usage']['userlimit'],
        'crm-ai-diagnostics-kpi__value'
    )
    . html_writer::div(
        get_string(
            'crm_inbox_ai_usage_user_label_n129d',
            'local_subscriptions'
        ),
        'crm-ai-diagnostics-kpi__label'
    ),
    'crm-ai-diagnostics-kpi'
);

echo html_writer::div(
    html_writer::div(
        (string)$failures,
        'crm-ai-diagnostics-kpi__value'
    )
    . html_writer::div(
        get_string(
            'crm_inbox_ai_failures_label_n129d',
            'local_subscriptions'
        ),
        'crm-ai-diagnostics-kpi__label'
    ),
    'crm-ai-diagnostics-kpi'
);

echo html_writer::end_div();

echo html_writer::start_div(
    'crm-ai-diagnostics-grid'
);

echo html_writer::start_tag(
    'section',
    [
        'class' =>
            'crm-ai-diagnostics-card',
    ]
);

echo html_writer::tag(
    'h2',
    get_string(
        'crm_inbox_ai_diag_checks_title_n129d',
        'local_subscriptions'
    ),
    [
        'class' =>
            'crm-ai-diagnostics-card__title',
    ]
);

echo html_writer::div(
    get_string(
        'crm_inbox_ai_diag_checks_help_n129d',
        'local_subscriptions'
    ),
    'crm-ai-diagnostics-card__subtitle'
);

echo html_writer::start_div(
    'crm-ai-diagnostics-checks'
);

foreach ($result['checks'] as $check) {
    $success = !empty(
        $check['success']
    );

    echo html_writer::div(
        html_writer::span(
            $success ? '✓' : '!',
            'crm-ai-diagnostics-check__icon'
        )
        . html_writer::span(
            s((string)$check['message']),
            'crm-ai-diagnostics-check__message'
        ),
        'crm-ai-diagnostics-check '
        . (
            $success
                ? 'crm-ai-diagnostics-check--ok'
                : 'crm-ai-diagnostics-check--error'
        )
    );
}

echo html_writer::end_div();
echo html_writer::end_tag('section');

echo html_writer::start_tag(
    'section',
    [
        'class' =>
            'crm-ai-diagnostics-card',
    ]
);

echo html_writer::tag(
    'h2',
    get_string(
        'crm_inbox_ai_usage_today',
        'local_subscriptions'
    ),
    [
        'class' =>
            'crm-ai-diagnostics-card__title',
    ]
);

echo html_writer::div(
    get_string(
        'crm_inbox_ai_diag_usage_help_n129d',
        'local_subscriptions'
    ),
    'crm-ai-diagnostics-card__subtitle'
);

$globalpercent =
    (int)$result['usage']['globallimit'] > 0
        ? min(
            100,
            (int)round(
                ((int)$result['usage']['global']
                    / (int)$result['usage']['globallimit'])
                * 100
            )
        )
        : 0;

$userpercent =
    (int)$result['usage']['userlimit'] > 0
        ? min(
            100,
            (int)round(
                ((int)($result['usage']['user'] ?? 0)
                    / (int)$result['usage']['userlimit'])
                * 100
            )
        )
        : 0;

foreach (
    [
        [
            'label' =>
                get_string(
                    'crm_inbox_ai_usage_global_label_n129d',
                    'local_subscriptions'
                ),
            'value' =>
                (string)$result['usage']['global']
                . ' / '
                . (string)$result['usage']['globallimit'],
            'percent' => $globalpercent,
        ],
        [
            'label' =>
                get_string(
                    'crm_inbox_ai_usage_user_label_n129d',
                    'local_subscriptions'
                ),
            'value' =>
                (string)($result['usage']['user'] ?? 0)
                . ' / '
                . (string)$result['usage']['userlimit'],
            'percent' => $userpercent,
        ],
    ]
    as $meter
) {
    echo html_writer::start_div(
        'crm-ai-diagnostics-meter'
    );

    echo html_writer::div(
        html_writer::span(
            $meter['label']
        )
        . html_writer::tag(
            'strong',
            $meter['value']
        ),
        'crm-ai-diagnostics-meter__header'
    );

    echo html_writer::div(
        html_writer::div(
            '',
            'crm-ai-diagnostics-meter__bar',
            [
                'style' =>
                    'width: '
                    . $meter['percent']
                    . '%',
            ]
        ),
        'crm-ai-diagnostics-meter__track'
    );

    echo html_writer::end_div();
}

echo html_writer::div(
    html_writer::span(
        get_string(
            'crm_inbox_ai_failures_label_n129d',
            'local_subscriptions'
        )
    )
    . html_writer::tag(
        'strong',
        (string)$failures
    ),
    'crm-ai-diagnostics-failures '
    . (
        $failures === 0
            ? 'crm-ai-diagnostics-failures--ok'
            : 'crm-ai-diagnostics-failures--error'
    )
);

echo html_writer::end_tag('section');
echo html_writer::end_div();
echo html_writer::end_div();

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();