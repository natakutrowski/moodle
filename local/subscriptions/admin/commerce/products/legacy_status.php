<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\catalog\navigation\CommerceCatalogIdentity;

AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
require_sesskey();

$origin = required_param('origin', PARAM_ALPHANUMEXT);
$id = required_param('id', PARAM_INT);
$action = required_param('action', PARAM_ALPHA);
$return = optional_param('return', 'index', PARAM_ALPHA);

if (!in_array($origin, ['legacy_plan', 'legacy_digital'], true)
        || !in_array($action, ['activate', 'deactivate'], true)
        || $id <= 0) {
    throw new moodle_exception('invalidparameter');
}

$active = $action === 'activate' ? 1 : 0;
$now = time();

if ($origin === 'legacy_plan') {
    $record = $DB->get_record('subscription_plan', ['id' => $id], 'id', MUST_EXIST);
    $DB->update_record('subscription_plan', (object)[
        'id' => (int)$record->id,
        'is_active' => $active,
        'last_update' => $now,
    ]);
} else {
    $record = $DB->get_record('subscription_digital_product', ['id' => $id], 'id', MUST_EXIST);
    $DB->update_record('subscription_digital_product', (object)[
        'id' => (int)$record->id,
        'enabled' => $active,
        'last_update' => $now,
    ]);
}

$returnurl = $return === 'view'
    ? new moodle_url('/local/subscriptions/admin/commerce/products/view.php', [
        'catalogkey' => (new CommerceCatalogIdentity($origin, $id))->to_string(),
    ])
    : new moodle_url('/local/subscriptions/admin/commerce/products/index.php');

redirect(
    $returnurl,
    get_string(
        $active ? 'commerce_product_activated' : 'commerce_product_deactivated',
        'local_subscriptions'
    ),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
