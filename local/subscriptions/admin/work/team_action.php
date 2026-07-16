<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\work\repositories\WorkTeamRepository;
use local_subscriptions\subscription_config;

AdminSecurity::require(Capabilities::MANAGE_WORK_CONFIGURATION);
require_sesskey();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    throw new moodle_exception('invalidrequest');
}

$repository = new WorkTeamRepository();
$action = required_param('action', PARAM_ALPHANUMEXT);

switch ($action) {
    case 'create':
        $repository->create(
            required_param('name', PARAM_TEXT),
            optional_param('description', '', PARAM_TEXT)
        );
        break;
    case 'toggle':
        $repository->toggle(required_param('teamid', PARAM_INT));
        break;
    case 'add_member':
        $repository->add_member(
            required_param('teamid', PARAM_INT),
            required_param('userid', PARAM_INT),
            optional_param('role', 'member', PARAM_ALPHANUMEXT)
        );
        break;
    case 'remove_member':
        $repository->remove_member(
            required_param('teamid', PARAM_INT),
            required_param('userid', PARAM_INT)
        );
        break;
    default:
        throw new moodle_exception('invalidrequest');
}

redirect(new moodle_url(subscription_config::admin_work_teams_page()));