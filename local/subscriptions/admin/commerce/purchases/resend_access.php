<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\purchase\action\CommercePurchaseCommunicationActionService;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseReadRepository;

$context = AdminSecurity::require(Capabilities::MANAGE_SUBSCRIPTIONS);
require_sesskey();

$id = required_param('id', PARAM_INT);
$confirm = required_param('confirm', PARAM_BOOL);
$returnurl = new moodle_url(
    '/local/subscriptions/admin/commerce/purchases/view.php',
    ['id' => $id]
);

$PAGE->set_context($context);
$PAGE->set_url($returnurl);

if (!$confirm) {
    redirect($returnurl);
}

$purchase = (new CommercePurchaseReadRepository($DB))->find_by_id($id);
if ($purchase === null) {
    throw new moodle_exception('commerce_purchase_not_found', 'local_subscriptions');
}

try {
    $result = (new CommercePurchaseCommunicationActionService())->resend_access(
        $purchase->summary->reference,
        (int)$USER->id
    );

    if ($result['sent']) {
        $message = get_string(
            'commerce_purchase_access_resent_to',
            'local_subscriptions',
            (string)$result['recipientemail']
        );
        $type = \core\output\notification::NOTIFY_SUCCESS;
    } else if ($result['queued']) {
        $message = get_string(
            'commerce_purchase_access_queued_to',
            'local_subscriptions',
            (string)$result['recipientemail']
        );
        $type = \core\output\notification::NOTIFY_WARNING;
    } else {
        $message = get_string('commerce_purchase_access_resend_failed', 'local_subscriptions');
        $type = \core\output\notification::NOTIFY_ERROR;
    }
} catch (Throwable $exception) {
    debugging(
        '[Commerce CRM] Access resend failed: ' . $exception->getMessage(),
        DEBUG_DEVELOPER
    );
    $message = get_string('commerce_purchase_access_resend_failed', 'local_subscriptions');
    $type = \core\output\notification::NOTIFY_ERROR;
}

redirect($returnurl, $message, null, $type);
