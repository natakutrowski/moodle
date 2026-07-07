<?php

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commandcenter\actions\CommandActionRegistry;
use local_subscriptions\commandcenter\actions\CommandActionResult;

AdminSecurity::require(Capabilities::VIEW_DASHBOARD);

require_sesskey();

header('Content-Type: application/json; charset=utf-8');

$actionkey = required_param('action', PARAM_ALPHANUMEXT);
$payloadraw = optional_param('payload', '{}', PARAM_RAW);

try {
    $payload = json_decode($payloadraw, true);

    if (!is_array($payload)) {
        $payload = [];
    }

    $action = CommandActionRegistry::get($actionkey);

    if (!$action) {
        echo json_encode(CommandActionResult::error(
            get_string('command_center_action_unknown', 'local_subscriptions')
        ), JSON_UNESCAPED_UNICODE);
        exit;
    }

    AdminSecurity::require($action->capability());

    echo json_encode($action->execute($payload), JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    debugging('Command Center execute failed: ' . $e->getMessage(), DEBUG_DEVELOPER);

    echo json_encode(CommandActionResult::error(
        get_string('command_center_action_error', 'local_subscriptions')
    ), JSON_UNESCAPED_UNICODE);
}

exit;