<?php
require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\subscription_config;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;

require_sesskey();

$context = AdminSecurity::require(Capabilities::MANAGE_DIGITAL);

$id = required_param('id', PARAM_INT);

$product = $DB->get_record('subscription_digital_product', ['id' => $id], '*', MUST_EXIST);

$purchases = $DB->count_records('subscription_digital_payment_request', [
    'productid' => $id,
]);

if ($purchases > 0) {
    redirect(
        new moodle_url(subscription_config::digital_products_admin_page()),
        get_string('digital_products_delete_has_purchases', 'local_subscriptions'),
        5,
        \core\output\notification::NOTIFY_ERROR
    );
}

$DB->delete_records('subscription_digital_product_lang', ['productid' => $id]);
$DB->delete_records('subscription_digital_product', ['id' => $id]);

redirect(
    new moodle_url(subscription_config::digital_products_admin_page()),
    get_string('digital_products_deleted', 'local_subscriptions'),
    1,
    \core\output\notification::NOTIFY_SUCCESS
);