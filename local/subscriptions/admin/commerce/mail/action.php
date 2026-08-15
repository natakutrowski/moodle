<?php
require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\mail\admin\CommerceMailAdminService;

global $PAGE, $USER;

// This action endpoint does not render a Moodle page, but the Commerce mail
// templates may still call Moodle formatting/rendering helpers that require a
// valid $PAGE->context. Cron execution already has an execution context; the
// direct CRM action must establish one explicitly before dispatching the mail.
$PAGE->set_context(\context_system::instance());
$PAGE->set_url(new moodle_url('/local/subscriptions/admin/commerce/mail/action.php'));

AdminSecurity::require(Capabilities::MANAGE_SUBSCRIPTIONS);
require_sesskey();

$id = required_param('id', PARAM_INT);
$action = required_param('action', PARAM_ALPHA);
$service = new CommerceMailAdminService();

if ($action === 'sendnow') {
    $result = $service->send_now($id);
    $sent = (int)($result['sent'] ?? 0) === 1;
    $message = $sent
        ? get_string('commerce_mail_send_now_success', 'local_subscriptions')
        : get_string('commerce_mail_send_now_failed', 'local_subscriptions');
    redirect(
        new moodle_url('/local/subscriptions/admin/commerce/mail/index.php'),
        $message,
        null,
        $sent ? \core\output\notification::NOTIFY_SUCCESS : \core\output\notification::NOTIFY_WARNING
    );
} else if ($action === 'retry') {
    $service->retry($id);
} else if ($action === 'cancel') {
    $service->cancel($id);
} else if ($action === 'resend') {
    $newrecord = $service->resend($id, (int)$USER->id);
    redirect(
        new moodle_url('/local/subscriptions/admin/commerce/mail/view.php', ['id' => (int)$newrecord->id]),
        get_string('commerce_mail_resend_queued', 'local_subscriptions')
    );
} else {
    throw new moodle_exception('invalidparameter');
}

redirect(new moodle_url('/local/subscriptions/admin/commerce/mail/view.php', ['id' => $id]));
