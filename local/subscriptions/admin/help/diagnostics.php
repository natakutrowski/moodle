<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\subscription_config;
use local_subscriptions\crm\help\validation\HelpCenterValidator;

global $PAGE, $OUTPUT;

$context = AdminSecurity::require(
    Capabilities::MANAGE_CONFIGURATION
);

$url = new moodle_url(
    subscription_config::admin_help_diagnostics_page()
);

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_title(
    get_string(
        'crm_help_diagnostics_title',
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

$result = (new HelpCenterValidator())->validate();

echo $OUTPUT->header();

echo html_writer::link(
    new moodle_url(
        subscription_config::admin_help_page()
    ),
    '← ' . get_string(
        'crm_help_home',
        'local_subscriptions'
    ),
    [
        'class' => 'crm-help-back-link',
    ]
);

echo html_writer::start_div(
    'crm-help-diagnostics'
);

echo html_writer::tag(
    'h2',
    get_string(
        'crm_help_diagnostics_title',
        'local_subscriptions'
    ),
    [
        'class' => 'crm-help-diagnostics-title',
    ]
);

echo html_writer::div(
    get_string(
        'crm_help_diagnostics_description',
        'local_subscriptions'
    ),
    'crm-help-diagnostics-description'
);

echo html_writer::start_div(
    'crm-help-diagnostics-summary'
);

echo html_writer::div(
    html_writer::strong(
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
    html_writer::strong(
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
    html_writer::strong(
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

echo $OUTPUT->footer();