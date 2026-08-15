<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\mail\library\CommerceMailLibraryRepository;

AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
require_sesskey();
$id = required_param('id', PARAM_INT);
$action = required_param('action', PARAM_ALPHA);
$repository = new CommerceMailLibraryRepository($DB);
if ($action === 'duplicate') {
    $copy = $repository->duplicate($id, (int)$USER->id);
    redirect(new moodle_url('/local/subscriptions/admin/commerce/mail/templates/library_edit.php', ['id' => (int)$copy->id]), get_string('commerce_mail_library_duplicated', 'local_subscriptions'));
}
if ($action === 'archive') {
    $repository->archive($id, (int)$USER->id);
    redirect(new moodle_url('/local/subscriptions/admin/commerce/mail/templates/index.php'), get_string('commerce_mail_library_archived', 'local_subscriptions'));
}
if ($action === 'delete') {
    $repository->delete_archived($id);
    redirect(
        new moodle_url('/local/subscriptions/admin/commerce/mail/templates/index.php'),
        get_string('commerce_mail_library_deleted', 'local_subscriptions')
    );
}
throw new moodle_exception('invalidparameter');
