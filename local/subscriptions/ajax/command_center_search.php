<?php

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commandcenter\CommandCenterService;

AdminSecurity::require(Capabilities::VIEW_DASHBOARD);

header('Content-Type: application/json; charset=utf-8');

$started = microtime(true);
$query = optional_param('q', '', PARAM_RAW_TRIMMED);
$query = trim($query);

try {
    $service = new CommandCenterService();
    $collection = $service->search($query, 20)
        ->with_duration((int)round((microtime(true) - $started) * 1000));

    echo json_encode($collection, JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    debugging(
        'Command Center AJAX failed: ' . $e->getMessage(),
        DEBUG_DEVELOPER
    );

    echo json_encode([
        'success' => false,
        'results' => [],
        'error' => [
            'message' => get_string('command_center_error', 'local_subscriptions'),
        ],
        'meta' => [
            'query' => $query,
            'count' => 0,
            'duration_ms' => (int)round((microtime(true) - $started) * 1000),
        ],
    ], JSON_UNESCAPED_UNICODE);
}

exit;