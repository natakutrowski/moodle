<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\mail\transactional\CommerceTransactionalMailStudioBridge;

AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
require_sesskey();

$mailtype = required_param('mailtype', PARAM_ALPHANUMEXT);
$bridge = CommerceTransactionalMailStudioBridge::create($DB);

try {
    $template = $bridge->migrate($mailtype, (int)$USER->id);
    redirect(
        new moodle_url(
            '/local/subscriptions/admin/commerce/mail/templates/library_edit.php',
            ['id' => (int)$template->id]
        ),
        get_string('commerce_mail_transactional_migrated', 'local_subscriptions'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
} catch (\Throwable $exception) {
    redirect(
        new moodle_url(
            '/local/subscriptions/admin/commerce/mail/templates/index.php',
            ['category' => 'transactional']
        ),
        $exception->getMessage(),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}
