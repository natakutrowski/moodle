<?php
require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\subscription_config;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;

require_sesskey();

$context = AdminSecurity::require(Capabilities::MANAGE_DIGITAL);

$id = required_param('id', PARAM_INT);

$product = $DB->get_record('subscription_digital_product', ['id' => $id], '*', MUST_EXIST);

$DB->update_record('subscription_digital_product', (object)[
    'id' => $id,
    'enabled' => empty($product->enabled) ? 1 : 0,
    'last_update' => time(),
]);

redirect(
    new moodle_url(subscription_config::digital_products_admin_page()),
    get_string('digital_products_status_updated', 'local_subscriptions'),
    1,
    \core\output\notification::NOTIFY_SUCCESS
);