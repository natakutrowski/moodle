<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\customer\reconciliation\CommerceCustomerIdentityReconciliationResult;
use local_subscriptions\commerce\customer\reconciliation\CommerceCustomerIdentityReconciliationService;

AdminSecurity::require(Capabilities::MANAGE_CRM_ADMIN_TOOLS);
require_sesskey();

$id = required_param('id', PARAM_INT);
$action = required_param('action', PARAM_ALPHA);
$returnurl = new moodle_url('/local/subscriptions/admin/commerce/customer-identities/index.php');

if ($action !== 'reconcile') {
    throw new moodle_exception('invalidparameter');
}

$result = (new CommerceCustomerIdentityReconciliationService($DB))->reconcile_purchase($id, true);

if ($result->status === CommerceCustomerIdentityReconciliationResult::STATUS_RECONCILED) {
    redirect(
        $returnurl,
        get_string('commerce_identity_reconcile_success', 'local_subscriptions', $result->purchasereference ?? ('#' . $id)),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

redirect(
    $returnurl,
    get_string('commerce_identity_reconcile_not_applied', 'local_subscriptions', $result->status),
    null,
    \core\output\notification::NOTIFY_WARNING
);
