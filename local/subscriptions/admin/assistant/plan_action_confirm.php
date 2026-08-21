<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\success\plans\rendering\CustomerSuccessPlanPresentation;
use local_subscriptions\crm\success\plans\repositories\CustomerSuccessPlanReadRepository;
use local_subscriptions\crm\layout\CrmPageConfigurator;
use local_subscriptions\crm\layout\CrmWorkspaceRenderer;
use local_subscriptions\crm\navigation\CrmNavigationKeys;
use local_subscriptions\crm\navigation\CrmBreadcrumbRenderer;
use local_subscriptions\crm\help\CrmPageHeader;
use local_subscriptions\crm\help\HelpContext;
use local_subscriptions\subscription_config;

$context = AdminSecurity::require(
    Capabilities::MANAGE_USERS
);

$planid = required_param(
    'planid',
    PARAM_INT
);

$stepid = optional_param(
    'stepid',
    0,
    PARAM_INT
);

$action = required_param(
    'action',
    PARAM_ALPHANUMEXT
);

if (
    !in_array(
        $action,
        [
            'cancel',
            'skip_step',
        ],
        true
    )
) {
    throw new invalid_parameter_exception(
        'Unsupported confirmation action.'
    );
}

$reader =
    new CustomerSuccessPlanReadRepository();

$plan =
    $reader->get(
        $planid
    );

$step = null;

if ($stepid > 0) {
    $step =
        $reader->get_step(
            $stepid
        );

    if ($step->planid !== $plan->id) {
        throw new invalid_parameter_exception(
            'The Customer Success plan step does not belong to the requested plan.'
        );
    }
}

if (
    $action === 'skip_step' &&
    $step === null
) {
    throw new invalid_parameter_exception(
        'A Customer Success step is required.'
    );
}

$pageurl = new moodle_url(
    subscription_config::
        admin_customer_success_plan_confirm_page(),
    [
        'planid' =>
            $planid,

        'stepid' =>
            $stepid,

        'action' =>
            $action,
    ]
);

$pagetitle = get_string(
    'csplanconfirmtitle',
    'local_subscriptions'
);

CrmPageConfigurator::configure(
    $PAGE,
    $context,
    $pageurl,
    $pagetitle,
    [
        'local-subscriptions-assistant-page',
        'local-subscriptions-customer-success-plan-confirm-page',
    ]
);


$planurl = new moodle_url(
    subscription_config::
        admin_customer_success_plan_page(),
    [
        'id' =>
            $planid,
    ]
);

if ($step !== null) {
    $planurl->set_anchor(
        'cs-step-' .
        $step->id
    );
}

$plantitle =
    CustomerSuccessPlanPresentation::title(
        $plan->objectivekey,
        $plan->title
    );

if ($action === 'cancel') {
    $question =
        get_string(
            'csplanconfirmcancel',
            'local_subscriptions',
            $plantitle
        );
} else {
    $question =
        get_string(
            'csplanconfirmskipstep',
            'local_subscriptions',
            $step->title
        );
}

$confirmurl = new moodle_url(
    subscription_config::
        admin_customer_success_plan_action_page(),
    [
        'sesskey' =>
            sesskey(),

        'planid' =>
            $planid,

        'stepid' =>
            $stepid,

        'action' =>
            $action,
    ]
);

echo $OUTPUT->header();

echo CrmWorkspaceRenderer::start(
    CrmNavigationKeys::ASSISTANT,
    $context
);

echo CrmBreadcrumbRenderer::render(
    [
        [
            'label' =>
                get_string(
                    'crm_assistant_title',
                    'local_subscriptions'
                ),

            'url' =>
                new moodle_url(
                    subscription_config::
                        admin_crm_assistant_page()
                ),
        ],
        [
            'label' =>
                get_string(
                    'csplanpage',
                    'local_subscriptions'
                ),

            'url' =>
                $planurl,
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
        'csplanconfirmhelp_n129b',
        'local_subscriptions'
    ),
    HelpContext::ASSISTANT
);

$confirmclass =
    $action === 'cancel'
        ? 'is-danger'
        : 'is-warning';

$icon =
    $action === 'cancel'
        ? 'fa-times-circle'
        : 'fa-forward';

echo html_writer::div(
    html_writer::div(
        html_writer::tag(
            'i',
            '',
            [
                'class' => 'fa ' . $icon,
                'aria-hidden' => 'true',
            ]
        ),
        'crm-cs-plan-confirm-icon'
    )
    . html_writer::div(
        html_writer::tag(
            'h2',
            s($question),
            [
                'class' =>
                    'crm-cs-plan-confirm-question',
            ]
        )
        . html_writer::div(
            get_string(
                'csplanconfirmwarning_n129b',
                'local_subscriptions'
            ),
            'crm-cs-plan-confirm-description'
        )
        . html_writer::div(
            html_writer::link(
                $confirmurl,
                get_string(
                    'confirm',
                    'core'
                ),
                [
                    'class' =>
                        'btn btn-danger',
                ]
            )
            . html_writer::link(
                $planurl,
                get_string(
                    'cancel',
                    'core'
                ),
                [
                    'class' =>
                        'btn btn-outline-secondary',
                ]
            ),
            'crm-cs-plan-confirm-actions'
        ),
        'crm-cs-plan-confirm-content'
    ),
    'crm-cs-plan-confirm ' . $confirmclass
);

echo CrmWorkspaceRenderer::end();

echo $OUTPUT->footer();
