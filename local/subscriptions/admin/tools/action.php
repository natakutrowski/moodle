<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\admin_tools\AdminToolRegistry;
use local_subscriptions\crm\admin_tools\rendering\AdminToolsSectionNavigationRenderer;
use local_subscriptions\crm\admin_tools\AdminToolParameterPolicy;
use local_subscriptions\crm\admin_tools\AdminToolRiskLevels;
use local_subscriptions\crm\admin_tools\services\AdminToolRunner;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\subscription_config;

global $USER;

$context = AdminSecurity::require(
    Capabilities::MANAGE_CRM_ADMIN_TOOLS
);

$toolkey = required_param(
    'tool',
    PARAM_ALPHANUMEXT
);

$registry = new AdminToolRegistry();
$tool = $registry->require($toolkey);
$parameterpolicy = new AdminToolParameterPolicy();

require_capability(
    $tool->required_capability(),
    $context
);

$pageurl = new moodle_url(
    subscription_config::
        admin_crm_tool_action_page(),
    [
        'tool' => $tool->key(),
    ]
);

$returnurl = new moodle_url(
    subscription_config::
        admin_crm_tools_page()
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $tool->title(),
    [
        'local-subscriptions-admin-tools-page',
        'local-subscriptions-admin-tool-action-page',
    ]
);

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {
    require_sesskey();

    if (
        $tool->requires_confirmation() &&
        !optional_param(
            'confirmed',
            0,
            PARAM_BOOL
        )
    ) {
        throw new moodle_exception(
            'crm_admin_tool_confirmation_required',
            'local_subscriptions'
        );
    }

    $parameters =
        $parameterpolicy
            ->read_request_parameters(
                $tool->key()
            );

    $result =
        (new AdminToolRunner())->run(
            $tool,
            (int)$USER->id,
            $parameters
        );

    if (
        $result->status ===
        \local_subscriptions\crm\admin_tools\AdminToolStatuses::SUCCESS
    ) {
        $messagetype =
            \core\output\notification::
                NOTIFY_SUCCESS;
    } else if (
        $result->status ===
        \local_subscriptions\crm\admin_tools\AdminToolStatuses::BUSY
    ) {
        $messagetype =
            \core\output\notification::
                NOTIFY_WARNING;
    } else {
        $messagetype =
            \core\output\notification::
                NOTIFY_ERROR;
    }

    redirect(
        $returnurl,
        $result->message,
        null,
        $messagetype
    );
}

echo $OUTPUT->header();

echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::TOOLS,
    $context
);

echo CrmBreadcrumbRenderer::render(
    [
        [
            'label' =>
                get_string(
                    'crm_admin_tools_title',
                    'local_subscriptions'
                ),

            'url' =>
                new moodle_url(
                    subscription_config::
                        admin_crm_tools_page()
                ),
        ],
        [
            'label' =>
                $tool->title(),

            'url' =>
                null,
        ],
    ]
);


echo CrmPageHeader::render(
    $tool->title(),
    $tool->description(),
    HelpContext::ADMIN_TOOLS
);


echo AdminToolsSectionNavigationRenderer::render(
    AdminToolsSectionNavigationRenderer::TOOLS
);
echo html_writer::start_div(
    'crm-admin-tool-execution-grid'
);

echo html_writer::start_div(
    'crm-admin-tool-execution-context'
);

echo html_writer::div(
    html_writer::span(
        s($tool->icon()),
        'crm-admin-tool-execution-icon',
        ['aria-hidden' => 'true']
    )
    . html_writer::div(
        html_writer::tag(
            'h2',
            s($tool->title()),
            ['class' => 'crm-admin-tool-execution-title']
        )
        . html_writer::tag(
            'p',
            s($tool->description()),
            ['class' => 'crm-admin-tool-execution-description']
        ),
        'crm-admin-tool-execution-copy'
    ),
    'crm-admin-tool-execution-heading'
);

echo html_writer::div(
    get_string(
        'crm_admin_tool_risk_' .
            $tool->risk_level(),
        'local_subscriptions'
    ),
    'crm-admin-tool-risk is-' .
        $tool->risk_level()
);

echo html_writer::div(
    get_string(
        'crm_admin_tool_execution_context_n128a',
        'local_subscriptions'
    ),
    'crm-admin-tool-execution-note'
);

echo html_writer::end_div();

echo html_writer::start_tag(
    'form',
    [
        'method' => 'post',
        'action' => $pageurl,
        'class' =>
            'crm-admin-tool-action-form',
    ]
);

echo html_writer::empty_tag(
    'input',
    [
        'type' => 'hidden',
        'name' => 'sesskey',
        'value' => sesskey(),
    ]
);

echo html_writer::tag(
    'h2',
    get_string(
        'crm_admin_tool_execution_settings_n128a',
        'local_subscriptions'
    ),
    ['class' => 'crm-admin-tool-form-title']
);


if (
    $parameterpolicy->has_limit(
        $tool->key()
    )
) {
    $defaultlimit =
        $parameterpolicy->default_limit(
            $tool->key()
        );

    $maximumlimit =
        $parameterpolicy->maximum_limit(
            $tool->key()
        );

    echo html_writer::start_div(
        'form-group mt-3'
    );

    echo html_writer::tag(
        'label',
        get_string(
            'crm_admin_tool_limit',
            'local_subscriptions'
        ),
        [
            'for' =>
                'crm-admin-tool-limit',
        ]
    );

    echo html_writer::empty_tag(
        'input',
        [
            'type' => 'number',
            'id' =>
                'crm-admin-tool-limit',
            'name' => 'limit',
            'min' => 1,
            'max' => $maximumlimit,
            'value' => $defaultlimit,
            'class' =>
                'form-control',
            'required' => 'required',
        ]
    );

    echo html_writer::div(
        get_string(
            'crm_admin_tool_limit_help',
            'local_subscriptions',
            (object)[
                'default' => $defaultlimit,
                'maximum' => $maximumlimit,
            ]
        ),
        'form-text text-muted'
    );

    echo html_writer::end_div();
}

if (
    $parameterpolicy->has_reset_cursor(
        $tool->key()
    )
) {
    echo html_writer::start_div(
        'form-check mt-3'
    );

    echo html_writer::empty_tag(
        'input',
        [
            'type' => 'checkbox',
            'id' =>
                'crm-admin-tool-resetcursor',
            'name' => 'resetcursor',
            'value' => 1,
            'class' =>
                'form-check-input',
        ]
    );

    echo html_writer::tag(
        'label',
        get_string(
            'crm_admin_tool_reset_cursor',
            'local_subscriptions'
        ),
        [
            'for' =>
                'crm-admin-tool-resetcursor',
            'class' =>
                'form-check-label',
        ]
    );

    echo html_writer::end_div();
}

if ($tool->requires_confirmation()) {
    echo html_writer::div(
        get_string(
            'crm_admin_tool_confirmation_warning',
            'local_subscriptions'
        ),
        'alert alert-warning mt-3'
    );

    echo html_writer::start_div(
        'form-check mt-3'
    );

    echo html_writer::empty_tag(
        'input',
        [
            'type' => 'checkbox',
            'id' =>
                'crm-admin-tool-confirmed',
            'name' => 'confirmed',
            'value' => 1,
            'class' =>
                'form-check-input',
            'required' => 'required',
        ]
    );

    echo html_writer::tag(
        'label',
        get_string(
            'crm_admin_tool_confirmation_checkbox',
            'local_subscriptions'
        ),
        [
            'for' =>
                'crm-admin-tool-confirmed',
            'class' =>
                'form-check-label',
        ]
    );

    echo html_writer::end_div();
}

echo html_writer::start_div(
    'crm-admin-tool-action-buttons mt-4'
);

echo html_writer::empty_tag(
    'input',
    [
        'type' => 'submit',
        'value' => get_string(
            'crm_admin_tool_execute',
            'local_subscriptions'
        ),
        'class' =>
            $tool->risk_level() ===
            AdminToolRiskLevels::HIGH
                ? 'btn btn-danger'
                : 'btn btn-primary',
    ]
);

echo html_writer::link(
    $returnurl,
    get_string(
        'cancel',
        'core'
    ),
    [
        'class' =>
            'btn btn-outline-secondary',
    ]
);

echo html_writer::end_div();
echo html_writer::end_tag('form');
echo html_writer::end_div();

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();