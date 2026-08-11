<?php
require_once(__DIR__ . '/../../../../../config.php');
use local_subscriptions\admin\AdminSecurity; use local_subscriptions\admin\Capabilities; use local_subscriptions\commerce\mail\admin\CommerceMailAdminService;
AdminSecurity::require(Capabilities::MANAGE_SUBSCRIPTIONS);
require_sesskey();

global $USER;

$id = required_param('id', PARAM_INT);
$action = required_param('action', PARAM_ALPHA);
$service = new CommerceMailAdminService();

if ($action === 'retry') {
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
