<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\purchase\action\CommercePurchaseActionServiceFactory;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseReadRepository;

$context = AdminSecurity::require(Capabilities::MANAGE_SUBSCRIPTIONS);
require_sesskey();
$id = required_param('id', PARAM_INT);
$confirm = required_param('confirm', PARAM_BOOL);
$returnurl = new moodle_url('/local/subscriptions/admin/commerce/purchases/view.php', ['id' => $id]);

// Moodle enrolment APIs may format course and enrolment labels during fulfillment.
// The action endpoint does not render a page, but these APIs still require a valid PAGE context.
$PAGE->set_context($context);
$PAGE->set_url($returnurl);

if (!$confirm) { redirect($returnurl); }
$purchase = (new CommercePurchaseReadRepository($DB))->find_by_id($id);
if ($purchase === null) { throw new moodle_exception('commerce_purchase_not_found', 'local_subscriptions'); }
$result = CommercePurchaseActionServiceFactory::create()->process_fulfillment($purchase, (int)$USER->id);
if ($result->message === 'missing_grants') {
    $closeurl = new moodle_url('/local/subscriptions/admin/commerce/purchases/close_without_fulfillment.php', [
        'id' => $id,
        'sesskey' => sesskey(),
    ]);
    $message = get_string('commerce_purchase_fulfillment_missing_grants', 'local_subscriptions')
        . html_writer::div(
            html_writer::link(
                $closeurl,
                get_string('commerce_purchase_close_without_fulfillment', 'local_subscriptions'),
                ['class' => 'btn btn-outline-danger btn-sm mt-3']
            )
        );
} else {
    $messagekey = $result->successful
        ? 'commerce_purchase_fulfillment_process_success'
        : 'commerce_purchase_retry_failed';
}

redirect(
    $returnurl,
    isset($message) ? $message : get_string($messagekey, 'local_subscriptions'),
    null,
    $result->successful
        ? \core\output\notification::NOTIFY_SUCCESS
        : \core\output\notification::NOTIFY_ERROR
);
