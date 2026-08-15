<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\mail\transactional\CommerceTransactionalMailStudioBridge;

AdminSecurity::require(Capabilities::MANAGE_CONFIGURATION);
require_sesskey();

$bridge = CommerceTransactionalMailStudioBridge::create($DB);
$migrated = 0;
$already = 0;

foreach (CommerceTransactionalMailStudioBridge::supported_types() as $mailtype) {
    if ($bridge->template($mailtype) !== null) {
        $already++;
        continue;
    }
    $bridge->migrate($mailtype, (int)$USER->id);
    $migrated++;
}

redirect(
    new moodle_url(
        '/local/subscriptions/admin/commerce/mail/templates/index.php',
        ['category' => 'transactional']
    ),
    get_string(
        'commerce_mail_transactional_migrate_all_done',
        'local_subscriptions',
        (object)['migrated' => $migrated, 'already' => $already]
    ),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
