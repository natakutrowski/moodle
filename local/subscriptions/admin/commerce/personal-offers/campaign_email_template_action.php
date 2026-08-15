<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\personaloffer\campaign\CommercePersonalOfferMailStudioBridge;

AdminSecurity::require(Capabilities::MANAGE_CRM_ADMIN_TOOLS);
require_sesskey();

$campaignid = required_param('id', PARAM_INT);
$action = required_param('action', PARAM_ALPHA);
$bridge = CommercePersonalOfferMailStudioBridge::create($DB);

$returnurl = new moodle_url(
    '/local/subscriptions/admin/commerce/personal-offers/campaign_email.php',
    ['id' => $campaignid]
);

try {
    if ($action === 'applytemplate') {
        $templateid = required_param('templateid', PARAM_INT);
        $bridge->apply_template($campaignid, $templateid, (int)$USER->id);
        redirect(
            $returnurl,
            get_string('commerce_personal_offer_mailstudio_applied', 'local_subscriptions'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }

    if ($action === 'savetemplate') {
        $name = required_param('templatename', PARAM_TEXT);
        $template = $bridge->save_campaign_as_template(
            $campaignid,
            $name,
            (int)$USER->id
        );
        redirect(
            new moodle_url(
                '/local/subscriptions/admin/commerce/mail/templates/library_edit.php',
                ['id' => (int)$template->id]
            ),
            get_string('commerce_personal_offer_mailstudio_saved', 'local_subscriptions'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }

    throw new \moodle_exception('invalidparameter');
} catch (\Throwable $exception) {
    redirect(
        $returnurl,
        $exception->getMessage(),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}
