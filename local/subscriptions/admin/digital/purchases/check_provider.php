<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\subscription_config;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\admin\AdminLog;
use local_subscriptions\admin\AdminEvents;
use local_subscriptions\digital\digital_payment_reconciler;

global $DB, $PAGE;

$context = AdminSecurity::require(Capabilities::MANAGE_DIGITAL);
require_sesskey();

$id = required_param('id', PARAM_INT);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url(subscription_config::digital_purchase_check_provider_admin_page(), ['id' => $id]));

$purchase = $DB->get_record('subscription_digital_payment_request', ['id' => $id], '*', MUST_EXIST);

$result = digital_payment_reconciler::check_one($id);

$targetuserid = !empty($purchase->userid) ? (int)$purchase->userid : 0;

if ($targetuserid <= 0 && !empty($purchase->email)) {
    $targetuserid = (int)$DB->get_field_sql("
        SELECT id
          FROM {user}
         WHERE deleted = 0
           AND " . $DB->sql_compare_text('email') . " = " . $DB->sql_compare_text(':email') . "
      ORDER BY id DESC
    ", ['email' => $purchase->email], IGNORE_MISSING);
}

AdminLog::log(
    AdminEvents::DIGITAL_PROVIDER_CHECKED,
    $targetuserid > 0 ? $targetuserid : null,
    'digital_purchase',
    (int)$purchase->id,
    [
        'email' => $purchase->email ?? '',
        'productid' => $purchase->productid ?? 0,
        'purchaseid' => $purchase->id,
        'provider' => $purchase->payment_provider ?? '',
        'status' => $result['status'] ?? '',
        'reason' => $result['reason'] ?? '',
    ]
);

redirect(
    new moodle_url(subscription_config::digital_purchase_view_admin_page(), ['id' => $id]),
    get_string('digital_provider_check_done', 'local_subscriptions', (object)[
        'status' => $result['status'] ?? 'UNKNOWN',
    ]),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);