<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\success\plans\services\CustomerSuccessPlanLifecycleService;
use local_subscriptions\crm\success\plans\repositories\CustomerSuccessPlanReadRepository;
use local_subscriptions\subscription_config;

AdminSecurity::require(
    Capabilities::MANAGE_USERS
);

require_sesskey();

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

$blockreason = optional_param(
    'blockreason',
    '',
    PARAM_TEXT
);

$lifecycle =
    new CustomerSuccessPlanLifecycleService();

$redirecturl = new moodle_url(
    subscription_config::
        admin_customer_success_plan_page(),
    [
        'id' => $planid,
    ]
);

if ($stepid > 0) {
    $redirecturl->set_anchor(
        'cs-step-' .
        $stepid
    );
}

$reader =
    new CustomerSuccessPlanReadRepository();

$plan =
    $reader->get(
        $planid
    );

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

$stepactions = [
    'start_step',
    'complete_step',
    'skip_step',
    'block_step',
    'unblock_step',
];

if (
    in_array(
        $action,
        $stepactions,
        true
    ) &&
    $stepid <= 0
) {
    throw new invalid_parameter_exception(
        'A Customer Success step is required for this action.'
    );
}

if ($action === 'block_step') {
    $blockreason =
        trim(
            $blockreason
        );

    if ($blockreason === '') {
        redirect(
            $redirecturl,
            get_string(
                'csplanblockreasonrequired',
                'local_subscriptions'
            ),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }

    if (
        core_text::strlen(
            $blockreason
        ) > 500
    ) {
        redirect(
            $redirecturl,
            get_string(
                'csplanblockreasontoolong',
                'local_subscriptions'
            ),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
}

try {
    match ($action) {
        'activate' =>
            $lifecycle->activate(
                $planid,
                (int)$USER->id
            ),

        'pause' =>
            $lifecycle->pause(
                $planid,
                (int)$USER->id
            ),

        'cancel' =>
            $lifecycle->cancel(
                $planid,
                (int)$USER->id
            ),

        'complete_plan' =>
            $lifecycle->complete_plan(
                $planid,
                (int)$USER->id
            ),

        'start_step' =>
            $lifecycle->start_step(
                $stepid,
                (int)$USER->id
            ),

        'complete_step' =>
            $lifecycle->complete_step(
                $stepid,
                (int)$USER->id
            ),

        'skip_step' =>
            $lifecycle->skip_step(
                $stepid,
                (int)$USER->id
            ),

        'block_step' =>
            $lifecycle->block_step(
                $stepid,
                $blockreason,
                (int)$USER->id
            ),            

        'unblock_step' =>
            $lifecycle->unblock_step(
                $stepid,
                (int)$USER->id
            ),

        default =>
            throw new invalid_parameter_exception(
                'Unknown Customer Success plan action.'
            ),
    };

    redirect(
        $redirecturl,
        get_string(
            'csplanactioncompleted',
            'local_subscriptions'
        ),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
} catch (\Throwable $exception) {
    debugging(
        sprintf(
            'Customer Success plan action failed: %s',
            $exception->getMessage()
        ),
        DEBUG_DEVELOPER
    );

    redirect(
        $redirecturl,
        get_string(
            'csplanactionfailed',
            'local_subscriptions'
        ),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}