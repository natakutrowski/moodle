<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\mail\admin\CommerceMailHealthRenderer;
use local_subscriptions\commerce\mail\admin\CommerceMailSectionNavigationRenderer;
use local_subscriptions\commerce\mail\certification\CommerceMailEngineCertificationService;
use local_subscriptions\commerce\mail\CommerceMailQueueRepository;
use local_subscriptions\crm\commerce\rendering\CommerceSectionNavigationRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;

$context = AdminSecurity::require(Capabilities::MANAGE_SUBSCRIPTIONS);
$pageurl = new moodle_url('/local/subscriptions/admin/commerce/mail/configuration.php');
$title = get_string('commerce_mail_configuration_title', 'local_subscriptions');

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $title,
    'local-subscriptions-commerce-mail-configuration-page'
);
$PAGE->requires->css('/local/subscriptions/styles/commerce_mail_admin.css');

$configbool = static function(string $key, bool $default = true): bool {
    $value = get_config('local_subscriptions', $key);
    if ($value === false || $value === '') {
        return $default;
    }
    return (string)$value !== '0';
};
$configint = static function(
    string $key,
    int $default,
    int $minimum,
    int $maximum
): int {
    $value = get_config('local_subscriptions', $key);
    $value = $value === false || $value === '' ? $default : (int)$value;
    return max($minimum, min($maximum, $value));
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();

    $settings = [
        'commerce_mail_transactional_enabled' => optional_param(
            'transactional_enabled',
            0,
            PARAM_BOOL
        ) ? 1 : 0,
        'commerce_mail_transactional_batch_size' => max(
            1,
            min(500, optional_param('transactional_batch', 50, PARAM_INT))
        ),
        'commerce_mail_transactional_hourly_limit' => max(
            0,
            min(5000, optional_param('transactional_hourly', 0, PARAM_INT))
        ),
        'personal_offer_mail_enabled' => optional_param(
            'personal_offer_enabled',
            0,
            PARAM_BOOL
        ) ? 1 : 0,
        'personal_offer_mail_batch_size' => max(
            1,
            min(500, optional_param('personal_offer_batch', 20, PARAM_INT))
        ),
        'personal_offer_mail_hourly_limit' => max(
            1,
            min(5000, optional_param('personal_offer_hourly', 100, PARAM_INT))
        ),
        'commerce_mail_marketing_enabled' => optional_param(
            'marketing_enabled',
            0,
            PARAM_BOOL
        ) ? 1 : 0,
        'commerce_mail_marketing_batch_size' => max(
            1,
            min(500, optional_param('marketing_batch', 50, PARAM_INT))
        ),
        'commerce_mail_marketing_hourly_limit' => max(
            0,
            min(5000, optional_param('marketing_hourly', 250, PARAM_INT))
        ),
        'commerce_mail_audit_enabled' => optional_param(
            'audit_enabled',
            0,
            PARAM_BOOL
        ) ? 1 : 0,
        'commerce_mail_audit_batch_size' => max(
            1,
            min(200, optional_param('audit_batch', 10, PARAM_INT))
        ),
        'commerce_mail_audit_hourly_limit' => max(
            1,
            min(2000, optional_param('audit_hourly', 50, PARAM_INT))
        ),
        'commerce_mail_global_hourly_limit' => max(
            0,
            min(10000, optional_param('global_hourly', 0, PARAM_INT))
        ),
        'legacy_auto_mail_enabled' => optional_param(
            'legacy_auto_enabled',
            0,
            PARAM_BOOL
        ) ? 1 : 0,
        'legacy_auto_payment_reminders_enabled' => optional_param(
            'legacy_payment_reminders',
            0,
            PARAM_BOOL
        ) ? 1 : 0,
        'legacy_auto_expiry_reminders_enabled' => optional_param(
            'legacy_expiry_reminders',
            0,
            PARAM_BOOL
        ) ? 1 : 0,
        'legacy_auto_lifecycle_emails_enabled' => optional_param(
            'legacy_lifecycle_emails',
            0,
            PARAM_BOOL
        ) ? 1 : 0,
    ];

    foreach ($settings as $key => $value) {
        set_config($key, $value, 'local_subscriptions');
    }

    redirect(
        $pageurl,
        get_string('commerce_mail_configuration_saved', 'local_subscriptions'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$values = [
    'transactional_enabled' => $configbool('commerce_mail_transactional_enabled'),
    'transactional_batch' => $configint(
        'commerce_mail_transactional_batch_size',
        50,
        1,
        500
    ),
    'transactional_hourly' => $configint(
        'commerce_mail_transactional_hourly_limit',
        0,
        0,
        5000
    ),
    'personal_offer_enabled' => $configbool('personal_offer_mail_enabled'),
    'personal_offer_batch' => $configint(
        'personal_offer_mail_batch_size',
        20,
        1,
        500
    ),
    'personal_offer_hourly' => $configint(
        'personal_offer_mail_hourly_limit',
        100,
        1,
        5000
    ),
    'marketing_enabled' => $configbool('commerce_mail_marketing_enabled'),
    'marketing_batch' => $configint(
        'commerce_mail_marketing_batch_size',
        50,
        1,
        500
    ),
    'marketing_hourly' => $configint(
        'commerce_mail_marketing_hourly_limit',
        250,
        0,
        5000
    ),
    'audit_enabled' => $configbool('commerce_mail_audit_enabled'),
    'audit_batch' => $configint(
        'commerce_mail_audit_batch_size',
        10,
        1,
        200
    ),
    'audit_hourly' => $configint(
        'commerce_mail_audit_hourly_limit',
        50,
        1,
        2000
    ),
    'global_hourly' => $configint(
        'commerce_mail_global_hourly_limit',
        0,
        0,
        10000
    ),
    'legacy_auto_enabled' => $configbool(
        'legacy_auto_mail_enabled',
        false
    ),
    'legacy_payment_reminders' => $configbool(
        'legacy_auto_payment_reminders_enabled',
        false
    ),
    'legacy_expiry_reminders' => $configbool(
        'legacy_auto_expiry_reminders_enabled',
        false
    ),
    'legacy_lifecycle_emails' => $configbool(
        'legacy_auto_lifecycle_emails_enabled',
        false
    ),
];

$now = time();
$repository = new CommerceMailQueueRepository();
$sentlasthour = $repository->count_all_sent_since($now - HOURSECS);
$healthreport = (new CommerceMailEngineCertificationService($DB))->certify();

$taskclasses = [
    'transactional' => '\\local_subscriptions\\task\\process_commerce_mail_queue_task',
    'personaloffer' => '\\local_subscriptions\\task\\process_personal_offer_mail_queue_task',
    'marketing' => '\\local_subscriptions\\task\\process_marketing_mail_queue_task',
    'audit' => '\\local_subscriptions\\task\\process_commerce_mail_audit_queue_task',
    'legacyfollowup' => '\\local_subscriptions\\task\\followup_task',
    'legacyexpiry' => '\\local_subscriptions\\task\\send_expiry_reminders_task',
    'legacylifecycle' => '\\local_subscriptions\\task\\expire_user_enrolments_task',
];
$taskrecords = [];
foreach ($taskclasses as $key => $classname) {
    $taskrecords[$key] = $DB->get_record(
        'task_scheduled',
        ['classname' => $classname],
        '*',
        IGNORE_MISSING
    ) ?: null;
}

$taskstatus = static function(?\stdClass $record): string {
    if ($record === null) {
        return html_writer::span(
            get_string('commerce_mail_configuration_task_missing', 'local_subscriptions'),
            'commerce-mail-config-task-status is-danger'
        );
    }
    if (!empty($record->disabled)) {
        return html_writer::span(
            get_string('commerce_mail_configuration_task_disabled', 'local_subscriptions'),
            'commerce-mail-config-task-status is-warning'
        );
    }
    return html_writer::span(
        get_string('commerce_mail_configuration_task_active', 'local_subscriptions'),
        'commerce-mail-config-task-status is-success'
    );
};

$taskmeta = static function(?\stdClass $record): string {
    if ($record === null) {
        return '';
    }
    $parts = [];
    if (!empty($record->lastruntime)) {
        $parts[] = get_string(
            'commerce_mail_configuration_last_run',
            'local_subscriptions',
            userdate((int)$record->lastruntime, get_string('strftimedatetimeshort', 'langconfig'))
        );
    }
    if (!empty($record->nextruntime)) {
        $parts[] = get_string(
            'commerce_mail_configuration_next_run',
            'local_subscriptions',
            userdate((int)$record->nextruntime, get_string('strftimedatetimeshort', 'langconfig'))
        );
    }
    return implode(' · ', $parts);
};

$numberfield = static function(
    string $name,
    int $value,
    int $minimum,
    int $maximum,
    string $label,
    string $help
): string {
    return html_writer::div(
        html_writer::tag('label', $label, [
            'for' => 'mail-config-' . $name,
            'class' => 'form-label',
        ])
        . html_writer::empty_tag('input', [
            'type' => 'number',
            'id' => 'mail-config-' . $name,
            'name' => $name,
            'value' => $value,
            'min' => $minimum,
            'max' => $maximum,
            'class' => 'form-control',
        ])
        . html_writer::div($help, 'form-text'),
        'commerce-mail-config-field'
    );
};

$toggle = static function(
    string $name,
    bool $checked,
    string $label,
    string $help
): string {
    return html_writer::div(
        html_writer::div(
            html_writer::empty_tag('input', [
                'type' => 'checkbox',
                'id' => 'mail-config-' . $name,
                'name' => $name,
                'value' => '1',
                'checked' => $checked ? 'checked' : null,
                'class' => 'form-check-input',
            ])
            . html_writer::tag('label', $label, [
                'for' => 'mail-config-' . $name,
                'class' => 'form-check-label fw-semibold',
            ]),
            'form-check form-switch'
        )
        . html_writer::div($help, 'form-text mt-1'),
        'commerce-mail-config-toggle'
    );
};

$worker = static function(
    string $title,
    string $description,
    string $icon,
    string $togglehtml,
    string $fields,
    ?\stdClass $taskrecord
) use ($taskstatus, $taskmeta): string {
    return html_writer::tag(
        'section',
        html_writer::div(
            html_writer::span(
                html_writer::tag('i', '', [
                    'class' => 'fa ' . $icon,
                    'aria-hidden' => 'true',
                ]),
                'commerce-mail-config-worker-icon'
            )
            . html_writer::div(
                html_writer::tag('h3', $title, ['class' => 'h6 mb-1'])
                . html_writer::div(
                    $description,
                    'commerce-mail-config-worker-description'
                ),
                'commerce-mail-config-worker-heading-copy'
            )
            . html_writer::div(
                $taskstatus($taskrecord)
                . html_writer::div(
                    $taskmeta($taskrecord),
                    'commerce-mail-config-task-meta'
                ),
                'commerce-mail-config-task'
            ),
            'commerce-mail-config-worker-heading'
        )
        . $togglehtml
        . html_writer::div($fields, 'commerce-mail-config-worker-fields'),
        ['class' => 'commerce-mail-config-worker']
    );
};

$transactionalfields =
    $numberfield(
        'transactional_batch',
        $values['transactional_batch'],
        1,
        500,
        get_string('commerce_mail_configuration_batch_size', 'local_subscriptions'),
        get_string('commerce_mail_configuration_transactional_batch_help', 'local_subscriptions')
    )
    . $numberfield(
        'transactional_hourly',
        $values['transactional_hourly'],
        0,
        5000,
        get_string('commerce_mail_configuration_hourly_limit', 'local_subscriptions'),
        get_string('commerce_mail_configuration_hourly_zero_help', 'local_subscriptions')
    );

$personalfields =
    $numberfield(
        'personal_offer_batch',
        $values['personal_offer_batch'],
        1,
        500,
        get_string('commerce_mail_configuration_batch_size', 'local_subscriptions'),
        get_string('commerce_mail_configuration_personal_batch_help', 'local_subscriptions')
    )
    . $numberfield(
        'personal_offer_hourly',
        $values['personal_offer_hourly'],
        1,
        5000,
        get_string('commerce_mail_configuration_hourly_limit', 'local_subscriptions'),
        get_string('commerce_mail_configuration_personal_hourly_help', 'local_subscriptions')
    );

$marketingfields =
    $numberfield(
        'marketing_batch',
        $values['marketing_batch'],
        1,
        500,
        get_string('commerce_mail_configuration_batch_size', 'local_subscriptions'),
        get_string('commerce_mail_configuration_marketing_batch_help', 'local_subscriptions')
    )
    . $numberfield(
        'marketing_hourly',
        $values['marketing_hourly'],
        0,
        5000,
        get_string('commerce_mail_configuration_hourly_limit', 'local_subscriptions'),
        get_string('commerce_mail_configuration_marketing_hourly_help', 'local_subscriptions')
    );

$auditfields =
    $numberfield(
        'audit_batch',
        $values['audit_batch'],
        1,
        200,
        get_string('commerce_mail_configuration_batch_size', 'local_subscriptions'),
        get_string('commerce_mail_configuration_audit_batch_help', 'local_subscriptions')
    )
    . $numberfield(
        'audit_hourly',
        $values['audit_hourly'],
        1,
        2000,
        get_string('commerce_mail_configuration_hourly_limit', 'local_subscriptions'),
        get_string('commerce_mail_configuration_audit_hourly_help', 'local_subscriptions')
    );

$form = html_writer::start_tag('form', [
    'method' => 'post',
    'action' => $pageurl->out(false),
    'class' => 'commerce-mail-config-form',
]);
$form .= html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'sesskey',
    'value' => sesskey(),
]);

$form .= html_writer::tag(
    'section',
    html_writer::div(
        html_writer::div(
            html_writer::tag(
                'h2',
                get_string('commerce_mail_configuration_smtp_title', 'local_subscriptions'),
                ['class' => 'h6 mb-1']
            )
            . html_writer::div(
                get_string('commerce_mail_configuration_smtp_help', 'local_subscriptions'),
                'commerce-mail-config-global-description'
            ),
            'commerce-mail-config-global-copy'
        )
        . html_writer::div(
            html_writer::div(
                format_float($sentlasthour, 0),
                'commerce-mail-config-load-value'
            )
            . html_writer::div(
                get_string('commerce_mail_configuration_sent_last_hour', 'local_subscriptions'),
                'commerce-mail-config-load-label'
            ),
            'commerce-mail-config-load'
        ),
        'commerce-mail-config-global-heading'
    )
    . $numberfield(
        'global_hourly',
        $values['global_hourly'],
        0,
        10000,
        get_string('commerce_mail_configuration_global_hourly', 'local_subscriptions'),
        get_string('commerce_mail_configuration_global_hourly_help', 'local_subscriptions')
    ),
    ['class' => 'commerce-mail-config-global']
);

$form .= html_writer::tag(
    'h2',
    get_string('commerce_mail_configuration_workers_title', 'local_subscriptions'),
    ['class' => 'h5 commerce-mail-config-section-title']
);
$form .= html_writer::div(
    get_string('commerce_mail_configuration_workers_help', 'local_subscriptions'),
    'commerce-mail-config-section-help'
);

$form .= html_writer::div(
    $worker(
        get_string('commerce_mail_configuration_transactional_title', 'local_subscriptions'),
        get_string('commerce_mail_configuration_transactional_description', 'local_subscriptions'),
        'fa-envelope-o',
        $toggle(
            'transactional_enabled',
            $values['transactional_enabled'],
            get_string('commerce_mail_configuration_processing_enabled', 'local_subscriptions'),
            get_string('commerce_mail_configuration_processing_enabled_help', 'local_subscriptions')
        ),
        $transactionalfields,
        $taskrecords['transactional']
    )
    . $worker(
        get_string('commerce_mail_configuration_personal_title', 'local_subscriptions'),
        get_string('commerce_mail_configuration_personal_description', 'local_subscriptions'),
        'fa-tag',
        $toggle(
            'personal_offer_enabled',
            $values['personal_offer_enabled'],
            get_string('commerce_mail_configuration_processing_enabled', 'local_subscriptions'),
            get_string('commerce_mail_configuration_processing_enabled_help', 'local_subscriptions')
        ),
        $personalfields,
        $taskrecords['personaloffer']
    )
    . $worker(
        get_string('commerce_mail_configuration_marketing_title', 'local_subscriptions'),
        get_string('commerce_mail_configuration_marketing_description', 'local_subscriptions'),
        'fa-bullhorn',
        $toggle(
            'marketing_enabled',
            $values['marketing_enabled'],
            get_string('commerce_mail_configuration_processing_enabled', 'local_subscriptions'),
            get_string('commerce_mail_configuration_processing_enabled_help', 'local_subscriptions')
        ),
        $marketingfields,
        $taskrecords['marketing']
    )
    . $worker(
        get_string('commerce_mail_configuration_audit_title', 'local_subscriptions'),
        get_string('commerce_mail_configuration_audit_description', 'local_subscriptions'),
        'fa-shield',
        $toggle(
            'audit_enabled',
            $values['audit_enabled'],
            get_string('commerce_mail_configuration_processing_enabled', 'local_subscriptions'),
            get_string('commerce_mail_configuration_processing_enabled_help', 'local_subscriptions')
        ),
        $auditfields,
        $taskrecords['audit']
    ),
    'commerce-mail-config-workers'
);

$form .= html_writer::tag(
    'h2',
    get_string('commerce_mail_configuration_legacy_title', 'local_subscriptions'),
    ['class' => 'h5 commerce-mail-config-section-title']
);
$form .= html_writer::div(
    get_string('commerce_mail_configuration_legacy_help', 'local_subscriptions'),
    'commerce-mail-config-section-help'
);

$legacytask = static function(
    string $label,
    ?\stdClass $record
) use ($taskstatus, $taskmeta): string {
    return html_writer::div(
        html_writer::div(
            html_writer::tag('strong', $label)
            . html_writer::div($taskmeta($record), 'commerce-mail-config-task-meta'),
            'commerce-mail-config-legacy-task-copy'
        )
        . $taskstatus($record),
        'commerce-mail-config-legacy-task'
    );
};

$form .= html_writer::tag(
    'section',
    html_writer::div(
        html_writer::tag(
            'i',
            '',
            ['class' => 'fa fa-exclamation-triangle', 'aria-hidden' => 'true']
        )
        . html_writer::div(
            html_writer::tag(
                'strong',
                get_string(
                    'commerce_mail_configuration_legacy_warning_title',
                    'local_subscriptions'
                )
            )
            . html_writer::div(
                get_string(
                    'commerce_mail_configuration_legacy_warning',
                    'local_subscriptions'
                ),
                'commerce-mail-config-legacy-warning-copy'
            ),
            'commerce-mail-config-legacy-warning-text'
        ),
        'commerce-mail-config-legacy-warning'
    )
    . $toggle(
        'legacy_auto_enabled',
        $values['legacy_auto_enabled'],
        get_string(
            'commerce_mail_configuration_legacy_master',
            'local_subscriptions'
        ),
        get_string(
            'commerce_mail_configuration_legacy_master_help',
            'local_subscriptions'
        )
    )
    . html_writer::div(
        $toggle(
            'legacy_payment_reminders',
            $values['legacy_payment_reminders'],
            get_string(
                'commerce_mail_configuration_legacy_payment_reminders',
                'local_subscriptions'
            ),
            get_string(
                'commerce_mail_configuration_legacy_payment_reminders_help',
                'local_subscriptions'
            )
        )
        . $toggle(
            'legacy_expiry_reminders',
            $values['legacy_expiry_reminders'],
            get_string(
                'commerce_mail_configuration_legacy_expiry_reminders',
                'local_subscriptions'
            ),
            get_string(
                'commerce_mail_configuration_legacy_expiry_reminders_help',
                'local_subscriptions'
            )
        )
        . $toggle(
            'legacy_lifecycle_emails',
            $values['legacy_lifecycle_emails'],
            get_string(
                'commerce_mail_configuration_legacy_lifecycle',
                'local_subscriptions'
            ),
            get_string(
                'commerce_mail_configuration_legacy_lifecycle_help',
                'local_subscriptions'
            )
        ),
        'commerce-mail-config-legacy-toggles'
    )
    . html_writer::div(
        $legacytask(
            get_string(
                'commerce_mail_configuration_legacy_task_followup',
                'local_subscriptions'
            ),
            $taskrecords['legacyfollowup']
        )
        . $legacytask(
            get_string(
                'commerce_mail_configuration_legacy_task_expiry',
                'local_subscriptions'
            ),
            $taskrecords['legacyexpiry']
        )
        . $legacytask(
            get_string(
                'commerce_mail_configuration_legacy_task_lifecycle',
                'local_subscriptions'
            ),
            $taskrecords['legacylifecycle']
        ),
        'commerce-mail-config-legacy-tasks'
    ),
    ['class' => 'commerce-mail-config-legacy']
);

$form .= html_writer::div(
    html_writer::div(
        html_writer::tag('i', '', [
            'class' => 'fa fa-info-circle',
            'aria-hidden' => 'true',
        ])
        . html_writer::span(
            get_string('commerce_mail_configuration_cron_note', 'local_subscriptions')
        ),
        'commerce-mail-config-cron-note-copy'
    )
    . html_writer::link(
        new moodle_url('/admin/tool/task/scheduledtasks.php'),
        get_string('commerce_mail_configuration_open_scheduled_tasks', 'local_subscriptions')
            . ' '
            . html_writer::tag('i', '', [
                'class' => 'fa fa-external-link',
                'aria-hidden' => 'true',
            ]),
        [
            'class' => 'btn btn-sm btn-outline-secondary',
            'target' => '_blank',
            'rel' => 'noopener noreferrer',
        ]
    ),
    'commerce-mail-config-cron-note'
);

$form .= html_writer::div(
    html_writer::tag(
        'button',
        html_writer::tag('i', '', [
            'class' => 'fa fa-save me-1',
            'aria-hidden' => 'true',
        ])
            . get_string('savechanges'),
        ['type' => 'submit', 'class' => 'btn btn-primary']
    ),
    'commerce-mail-config-actions'
);
$form .= html_writer::end_tag('form');

echo $OUTPUT->header();
echo CrmWorkspaceRenderer::start(CrmNavigationKeys::COMMERCE, $context);
echo CrmBreadcrumbRenderer::render([
    [
        'label' => get_string('crm_commerce_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/index.php'),
    ],
    [
        'label' => get_string('commerce_mail_admin_title', 'local_subscriptions'),
        'url' => new moodle_url('/local/subscriptions/admin/commerce/mail/index.php'),
    ],
    ['label' => $title, 'url' => null],
]);
echo CrmPageHeader::render(
    $title,
    get_string('commerce_mail_configuration_description', 'local_subscriptions'),
    HelpContext::COMMERCE
);
echo CommerceSectionNavigationRenderer::render(CommerceSectionNavigationRenderer::MAIL);
echo html_writer::div(
    CommerceMailSectionNavigationRenderer::render(
        CommerceMailSectionNavigationRenderer::CONFIGURATION
    )
    . CommerceMailHealthRenderer::render_compact($healthreport),
    'commerce-mail-workspace-nav-row'
);
echo $form;
echo CrmWorkspaceRenderer::end();
echo $OUTPUT->footer();
