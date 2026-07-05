<?php

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\subscription_config;
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\service\UserSubscriptionEmailService;

global $DB, $PAGE;

$context = AdminSecurity::require(Capabilities::VIEW_USERS);
require_sesskey();

$userid = required_param('userid', PARAM_INT);
$subscriptionid = required_param('subscriptionid', PARAM_INT);
$action = required_param('action', PARAM_ALPHAEXT);
$days = optional_param('days', 30, PARAM_INT);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url(subscription_config::admin_user_subscription_quick_action_page(), [
    'userid' => $userid,
    'subscriptionid' => $subscriptionid,
    'action' => $action,
]));

$DB->get_record('user', [
    'id' => $userid,
    'deleted' => 0,
], '*', MUST_EXIST);

$DB->get_record('user_subscription', [
    'id' => $subscriptionid,
    'userid' => $userid,
], '*', MUST_EXIST);

try {
    switch ($action) {
        case 'welcome':
            UserSubscriptionEmailService::resend_welcome_email($userid, $subscriptionid);
            $message = get_string('crm_welcome_email_resent_success', 'local_subscriptions');
            break;

        case 'receipt':
            UserSubscriptionEmailService::resend_receipt($userid, $subscriptionid);
            $message = get_string('crm_receipt_resent_success', 'local_subscriptions');
            break;

        case 'access':
            UserSubscriptionEmailService::resend_access_email($userid, $subscriptionid);
            $message = get_string('crm_access_email_resent_success', 'local_subscriptions');
            break;

        case 'extend':
            UserSubscriptionEmailService::extend_subscription($userid, $subscriptionid, $days);
            $message = get_string('crm_subscription_extended_success', 'local_subscriptions', $days);
            break;

        default:
            throw new moodle_exception('invalidaction');
    }

    redirect(
        new moodle_url(subscription_config::admin_user_view_page(), ['id' => $userid]),
        $message,
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );

} catch (moodle_exception $e) {
    redirect(
        new moodle_url(subscription_config::admin_user_view_page(), ['id' => $userid]),
        $e->getMessage(),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}