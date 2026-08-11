<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\purchase\action\CommercePurchaseActionServiceFactory;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseReadRepository;

$context = AdminSecurity::require(Capabilities::MANAGE_SUBSCRIPTIONS);
require_sesskey();

$id = required_param('id', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$returnurl = new moodle_url('/local/subscriptions/admin/commerce/purchases/view.php', ['id' => $id]);
$actionurl = new moodle_url('/local/subscriptions/admin/commerce/purchases/close_without_fulfillment.php', [
    'id' => $id,
    'confirm' => 1,
    'sesskey' => sesskey(),
]);

$purchase = (new CommercePurchaseReadRepository($DB))->find_by_id($id);
if ($purchase === null) {
    throw new moodle_exception('commerce_purchase_not_found', 'local_subscriptions');
}

if (!$confirm) {
    $PAGE->set_context($context);
    $PAGE->set_url(new moodle_url('/local/subscriptions/admin/commerce/purchases/close_without_fulfillment.php', [
        'id' => $id,
        'sesskey' => sesskey(),
    ]));
    $PAGE->set_title(get_string('commerce_purchase_close_without_fulfillment_confirm', 'local_subscriptions'));
    $PAGE->set_heading(get_string('commerce_purchase_close_without_fulfillment_confirm', 'local_subscriptions'));

    echo $OUTPUT->header();
    echo $OUTPUT->confirm(
        get_string('commerce_purchase_close_without_fulfillment_confirm_text', 'local_subscriptions'),
        $actionurl,
        $returnurl
    );
    echo $OUTPUT->footer();
    exit;
}

$result = CommercePurchaseActionServiceFactory::create()
    ->close_without_fulfillment($purchase, (int)$USER->id);

redirect(
    $returnurl,
    get_string('commerce_purchase_closed_without_fulfillment_success', 'local_subscriptions'),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
