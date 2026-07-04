<?php
require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\subscription_config;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;

require_sesskey();

$context = AdminSecurity::require(Capabilities::MANAGE_DIGITAL);

$id = required_param('id', PARAM_INT);
$now = time();

$product = $DB->get_record('subscription_digital_product', ['id' => $id], '*', MUST_EXIST);

unset($product->id);

$product->slug = $product->slug . '-copy-' . $now;
$product->name = $product->name . ' copy';
$product->enabled = 0;
$product->creation_date = $now;
$product->last_update = $now;

$newid = $DB->insert_record('subscription_digital_product', $product);

$translations = $DB->get_records('subscription_digital_product_lang', ['productid' => $id]);

foreach ($translations as $translation) {
    unset($translation->id);

    $translation->productid = $newid;
    $translation->title = $translation->title . ' copy';
    $translation->creation_date = $now;
    $translation->last_update = $now;

    $DB->insert_record('subscription_digital_product_lang', $translation);
}

redirect(
    new moodle_url(subscription_config::digital_product_edit_admin_page(), ['id' => $newid]),
    get_string('digital_products_duplicated', 'local_subscriptions'),
    1,
    \core\output\notification::NOTIFY_SUCCESS
);