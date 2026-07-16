<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\work\services\WorkItemService;
use local_subscriptions\subscription_config;

AdminSecurity::require(Capabilities::MANAGE_WORK_ITEMS);
require_sesskey();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    throw new moodle_exception('invalidrequest');
}

$itemid = required_param('id', PARAM_INT);
$action = required_param('action', PARAM_ALPHANUMEXT);
$service = new WorkItemService();

switch ($action) {
    case 'status':
        $service->change_status(
            $itemid,
            required_param('status', PARAM_ALPHANUMEXT),
            (int)$USER->id
        );
        break;

    case 'priority':
        $service->change_priority(
            $itemid,
            required_param('priority', PARAM_ALPHANUMEXT),
            (int)$USER->id
        );
        break;

    case 'assign':
        $assigneduserid = optional_param('assigneduserid', 0, PARAM_INT);
        $assignedteamid = optional_param('assignedteamid', 0, PARAM_INT);
        $service->assign(
            $itemid,
            $assigneduserid > 0 ? $assigneduserid : null,
            $assignedteamid > 0 ? $assignedteamid : null,
            (int)$USER->id
        );
        break;

    case 'comment':
        $service->add_comment(
            $itemid,
            (int)$USER->id,
            required_param('body', PARAM_TEXT)
        );
        break;

    default:
        throw new moodle_exception('invalidrequest');
}

redirect(
    new moodle_url(subscription_config::admin_work_item_view_page(), ['id' => $itemid]),
    get_string('changessaved'),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);