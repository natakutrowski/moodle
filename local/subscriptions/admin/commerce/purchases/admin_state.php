<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\purchase\action\CommercePurchaseAdminClosureService;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseReadRepository;

AdminSecurity::require(Capabilities::MANAGE_SUBSCRIPTIONS);
require_sesskey();

$id = required_param('id', PARAM_INT);
$action = required_param('action', PARAM_ALPHA);
$returnurlparam = optional_param('returnurl', '', PARAM_LOCALURL);
$returnurl = $returnurlparam !== ''
    ? new moodle_url($returnurlparam)
    : new moodle_url(
        '/local/subscriptions/admin/commerce/purchases/view.php',
        ['id' => $id]
    );

$repository = new CommercePurchaseReadRepository($DB);
$details = $repository->find_by_id($id);
if ($details === null) {
    throw new \moodle_exception('invalidrecordunknown');
}

$service = new CommercePurchaseAdminClosureService($DB);

if ($action === 'close') {
    if (!$service->can_close($details->summary)) {
        throw new \moodle_exception(
            'commerce_sales_close_not_allowed',
            'local_subscriptions'
        );
    }
    $service->close(
        $id,
        (int)$USER->id,
        get_string('commerce_sales_close_default_reason', 'local_subscriptions')
    );
    redirect(
        $returnurl,
        get_string('commerce_sales_closed_success', 'local_subscriptions'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

if ($action === 'reopen') {
    $service->reopen($id);
    redirect(
        $returnurl,
        get_string('commerce_sales_reopened_success', 'local_subscriptions'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

throw new \moodle_exception('invalidparameter');
