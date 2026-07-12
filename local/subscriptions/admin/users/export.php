<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\user\explorer\UserExplorerCriteria;
use local_subscriptions\crm\user\explorer\UserExplorerColumnService;
use local_subscriptions\crm\user\explorer\UserExplorerExportService;

global $USER;

AdminSecurity::require(
    Capabilities::VIEW_USERS
);

require_sesskey();

$criteria = UserExplorerCriteria::from_request();

$columns = (new UserExplorerColumnService())
    ->get_columns((int)$USER->id);

(new UserExplorerExportService())->export(
    $criteria,
    $columns
);