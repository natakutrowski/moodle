<?php

declare(strict_types=1);

require_once dirname(__DIR__, 5) . '/config.php';

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\checkout\guest\CommerceUnfinishedGuestCheckoutCrmService;

$context = AdminSecurity::require(Capabilities::MANAGE_CRM_ADMIN_TOOLS);
require_sesskey();

$action = required_param('action', PARAM_ALPHA);
$userid = required_param('userid', PARAM_INT);
$returnurl = new moodle_url(
    '/local/subscriptions/admin/commerce/unfinished-checkouts/index.php',
    ['userid' => $userid]
);

$service = CommerceUnfinishedGuestCheckoutCrmService::create();

try {
    if ($action === 'repair') {
        $service->repair_user($userid);
        $message = get_string('commerce_guest_crm_action_repaired', 'local_subscriptions');
    } elseif ($action === 'selectpurchase') {
        $reference = required_param('reference', PARAM_ALPHANUMEXT);
        $service->select_resume_purchase($userid, $reference);
        $message = get_string('commerce_guest_crm_action_resume_selected', 'local_subscriptions');
    } elseif ($action === 'reconcile') {
        $paymentid = required_param('paymentid', PARAM_INT);
        $service->reconcile_payment($userid, $paymentid);
        $message = get_string('commerce_guest_crm_action_reconciled', 'local_subscriptions');
    } else {
        throw new \invalid_parameter_exception('Unknown checkout administration action.');
    }

    redirect($returnurl, $message, null, \core\output\notification::NOTIFY_SUCCESS);
} catch (\Throwable $exception) {
    redirect(
        $returnurl,
        $exception->getMessage(),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}
