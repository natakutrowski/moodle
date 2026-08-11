<?php

require_once(__DIR__ . '/../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;

AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);

$tab = optional_param('tab', 'scopes', PARAM_ALPHANUMEXT);
$id = optional_param('edit', 0, PARAM_INT);

if ($tab === 'plans') {
    $target = $id > 0
        ? new moodle_url('/local/subscriptions/admin/commerce/plans/edit.php', ['id' => $id])
        : new moodle_url('/local/subscriptions/admin/commerce/plans/index.php');
} else {
    $target = $id > 0
        ? new moodle_url('/local/subscriptions/admin/commerce/accessscopes/edit.php', ['id' => $id])
        : new moodle_url('/local/subscriptions/admin/commerce/accessscopes/index.php');
}

redirect($target);
