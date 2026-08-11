<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\catalog\domain\CommerceProductStatus;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\commerce\catalog\validation\CommerceCatalogActivationValidator;

AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
require_sesskey();

$sku = required_param('sku', PARAM_RAW_TRIMMED);
$action = required_param('action', PARAM_ALPHA);
$return = optional_param('return', 'index', PARAM_ALPHA);
$factory = new CommerceCatalogFactory($DB);
$manager = $factory->product_manager();
$editor = $manager->get_editor_data($sku);
$product = $editor->get_product();
$returnurl = $return === 'view'
    ? new moodle_url('/local/subscriptions/admin/commerce/products/view.php', ['sku' => $sku])
    : new moodle_url('/local/subscriptions/admin/commerce/products/index.php');

if ($action === 'activate') {
    $validation = (new CommerceCatalogActivationValidator($DB))->validate($product);
    if (!$validation->is_valid()) {
        $message = implode(' ', array_map(static fn($issue): string => $issue->message, $validation->issues));
        redirect($returnurl, $message, null, \core\output\notification::NOTIFY_ERROR);
    }
    $manager->set_status($sku, CommerceProductStatus::ACTIVE);
    redirect($returnurl, get_string('commerce_product_activated', 'local_subscriptions'));
}

if ($action === 'deactivate') {
    $manager->set_status($sku, CommerceProductStatus::INACTIVE);
    redirect($returnurl, get_string('commerce_product_deactivated', 'local_subscriptions'));
}

if ($action === 'archive') {
    $manager->set_status($sku, CommerceProductStatus::ARCHIVED);
    redirect($returnurl, get_string('commerce_product_archived', 'local_subscriptions'));
}

throw new moodle_exception('invalidparameter');
