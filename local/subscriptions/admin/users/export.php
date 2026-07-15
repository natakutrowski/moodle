<?php

require_once(
    __DIR__ .
    '/../../../../config.php'
);

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\user\explorer\UserExplorerCriteria;
use local_subscriptions\crm\user\explorer\UserExplorerColumnService;
use local_subscriptions\crm\user\explorer\UserExplorerExportService;

global $USER;

AdminSecurity::require(
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

$criteria =
    UserExplorerCriteria::from_request();

if (!$canviewinbox) {
    $criteria =
        $criteria->without_inbox();
}

$columns =
    (new UserExplorerColumnService())
        ->get_columns(
            (int)$USER->id,
            $canviewinbox
        );

(new UserExplorerExportService())->export(
    $criteria,
    $columns,
    5000,
    $canviewinbox
);