<?php

require_once(__DIR__ . '/../../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\mail\CommerceMailType;
use local_subscriptions\commerce\mail\template\studio\CommerceMailHeaderImageService;
use local_subscriptions\commerce\mail\template\studio\CommerceMailTemplateRepository;

AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
require_sesskey();
$mailtype = required_param('mailtype', PARAM_ALPHANUMEXT);
$language = required_param('language', PARAM_ALPHANUMEXT);
if (!in_array($mailtype, CommerceMailType::all(), true) || !in_array($language, ['fr', 'en', 'ru'], true)) {
    throw new moodle_exception('invalidparameter');
}

$repository = new CommerceMailTemplateRepository($DB);
$record = $repository->get($mailtype, $language);
if ($record !== null) {
    get_file_storage()->delete_area_files(
        context_system::instance()->id,
        'local_subscriptions',
        CommerceMailHeaderImageService::FILEAREA,
        (int)$record->id
    );
}
$repository->delete($mailtype, $language);
redirect(
    new moodle_url('/local/subscriptions/admin/commerce/mail/templates/index.php'),
    get_string('commerce_mail_template_reset_done', 'local_subscriptions'),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
