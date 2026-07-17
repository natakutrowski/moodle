<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\success\plans\rendering\CustomerSuccessPlanPresentation;
use local_subscriptions\crm\success\plans\repositories\CustomerSuccessPlanReadRepository;
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

$PAGE->set_context(
    $context
);

$PAGE->set_url(
    $pageurl
);

$PAGE->set_pagelayout(
    'admin'
);

$PAGE->set_title(
    get_string(
        'csplanconfirmtitle',
        'local_subscriptions'
    )
);

$PAGE->set_heading(
    get_string(
        'csplanconfirmtitle',
        'local_subscriptions'
    )
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

echo $OUTPUT->confirm(
    $question,
    $confirmurl,
    $planurl
);

echo $OUTPUT->footer();