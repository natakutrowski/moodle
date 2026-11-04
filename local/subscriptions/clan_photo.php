<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../config.php');

use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\education\group\CommerceClanPhotoService;

require_login();
if (isguestuser()) {
    throw new moodle_exception('nopermissions', 'error');
}

$groupid = required_param('groupid', PARAM_INT);
$service = CommerceClanPhotoService::create();
$canviewcrm = has_capability(Capabilities::VIEW_PROMOTIONS, context_system::instance());
if (!$service->may_view($groupid, (int)$USER->id, $canviewcrm)) {
    throw new moodle_exception('nopermissions', 'error');
}

$group = $DB->get_record('local_subs_commerce_ped_group', ['id' => $groupid],
    'id,promotionid', MUST_EXIST);
$promotion = $DB->get_record('local_subs_commerce_ped_promo',
    ['id' => (int)$group->promotionid], 'id,courseid', MUST_EXIST);
$storedfile = $service->image($groupid, (int)$promotion->courseid);
if (!$storedfile) {
    throw new moodle_exception('filenotfound', 'error');
}

// Authenticated, private image delivery. Do not offer an open pluginfile endpoint.
send_stored_file($storedfile, 0, 0, false);
