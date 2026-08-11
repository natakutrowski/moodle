<?php

require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\purchase\action\CommercePurchaseActionServiceFactory;
use local_subscriptions\commerce\purchase\readmodel\CommercePurchaseReadRepository;

$context = AdminSecurity::require(Capabilities::VIEW_PAYMENTS);
require_sesskey();
$id = required_param('id', PARAM_INT);
$note = required_param('note', PARAM_RAW_TRIMMED);
$returnurl = new moodle_url('/local/subscriptions/admin/commerce/purchases/view.php', ['id' => $id]);
$purchase = (new CommercePurchaseReadRepository($DB))->find_by_id($id);
if ($purchase === null) { throw new moodle_exception('commerce_purchase_not_found', 'local_subscriptions'); }
CommercePurchaseActionServiceFactory::create()->add_note($purchase, (int)$USER->id, $note);
redirect($returnurl, get_string('commerce_purchase_note_added', 'local_subscriptions'), null, \core\output\notification::NOTIFY_SUCCESS);
