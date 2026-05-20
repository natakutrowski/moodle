<?php
require_once(__DIR__ . '/../../../config.php');

require_login();
require_sesskey();

$context = context_system::instance();
require_capability('moodle/site:config', $context);

$id = required_param('id', PARAM_INT);

$product = $DB->get_record('subscription_digital_product', ['id' => $id], '*', MUST_EXIST);

$purchases = $DB->count_records('subscription_digital_payment_request', [
    'productid' => $id,
]);

if ($purchases > 0) {
    redirect(
        new moodle_url('/local/subscriptions/admin/digital_products.php'),
        get_string('digital_products_delete_has_purchases', 'local_subscriptions'),
        5,
        \core\output\notification::NOTIFY_ERROR
    );
}

$DB->delete_records('subscription_digital_product_lang', ['productid' => $id]);
$DB->delete_records('subscription_digital_product', ['id' => $id]);

redirect(
    new moodle_url('/local/subscriptions/admin/digital_products.php'),
    get_string('digital_products_deleted', 'local_subscriptions'),
    1,
    \core\output\notification::NOTIFY_SUCCESS
);