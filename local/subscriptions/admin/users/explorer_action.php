<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\subscription_config;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\user\explorer\UserExplorerCriteria;
use local_subscriptions\crm\user\explorer\UserExplorerColumnService;
use local_subscriptions\crm\user\explorer\UserExplorerSavedViewService;

global $USER, $PAGE;

$context = AdminSecurity::require(
    Capabilities::VIEW_USERS
);

if (
    $_SERVER['REQUEST_METHOD'] !==
    'POST'
) {
    throw new moodle_exception(
        'invalidrequest',
        'error'
    );
}

require_sesskey();

$canviewinbox = AdminSecurity::can(
    Capabilities::VIEW_INBOX
);

$action = required_param(
    'action',
    PARAM_ALPHANUMEXT
);

$returnurl = optional_param(
    'returnurl',
    '',
    PARAM_LOCALURL
);

$redirecturl = $returnurl !== ''
    ? new moodle_url($returnurl)
    : new moodle_url(
        subscription_config::admin_users_page()
    );

$PAGE->set_context($context);
$PAGE->set_url(
    new moodle_url(
        subscription_config::admin_user_explorer_action_page()
    )
);

if ($action === 'save_columns') {
    $columns = optional_param_array(
        'columns',
        [],
        PARAM_ALPHANUMEXT
    );

    (new UserExplorerColumnService())
        ->save_columns(
            (int)$USER->id,
            $columns,
            $canviewinbox
        );

    redirect(
        $redirecturl,
        get_string(
            'crm_user_columns_saved',
            'local_subscriptions'
        ),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

if ($action === 'reset_columns') {
    (new UserExplorerColumnService())->reset(
        (int)$USER->id
    );

    redirect(
        $redirecturl,
        get_string(
            'crm_user_columns_reset',
            'local_subscriptions'
        ),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

if ($action === 'save_view') {
    $name = required_param(
        'name',
        PARAM_TEXT
    );

    $criteria =
        UserExplorerCriteria::from_request();

    if (!$canviewinbox) {
        $criteria =
            $criteria->without_inbox();
    }

    (new UserExplorerSavedViewService())
        ->save(
            (int)$USER->id,
            $name,
            $criteria,
            $canviewinbox
        );

    redirect(
        $redirecturl,
        get_string(
            'crm_user_view_saved',
            'local_subscriptions'
        ),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

if ($action === 'delete_view') {
    $viewid = required_param(
        'view',
        PARAM_ALPHANUMEXT
    );

    (new UserExplorerSavedViewService())->delete(
        (int)$USER->id,
        $viewid
    );

    redirect(
        $redirecturl,
        get_string(
            'crm_user_view_deleted',
            'local_subscriptions'
        ),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

throw new moodle_exception(
    'crm_user_explorer_invalid_action',
    'local_subscriptions'
);