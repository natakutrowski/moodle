<?php
require_once(__DIR__ . '/../../../config.php');

require_login();
require_sesskey();

$context = context_system::instance();
require_capability('moodle/site:config', $context);

$id = required_param('id', PARAM_INT);

$product = $DB->get_record('subscription_digital_product', ['id' => $id], '*', MUST_EXIST);

$DB->update_record('subscription_digital_product', (object)[
    'id' => $id,
    'enabled' => empty($product->enabled) ? 1 : 0,
    'last_update' => time(),
]);

redirect(
    new moodle_url('/local/subscriptions/admin/digital_products.php'),
    get_string('digital_products_status_updated', 'local_subscriptions'),
    1,
    \core\output\notification::NOTIFY_SUCCESS
);