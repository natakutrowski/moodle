<?php
require_once(__DIR__ . '/../../../../../config.php');
use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commerce\personaloffer\admin\CommercePersonalOfferAdminService;
use local_subscriptions\commerce\personaloffer\repository\MoodleCommercePersonalOfferRepository;
use local_subscriptions\commerce\personaloffer\mail\CommercePersonalOfferMailService;

$context = AdminSecurity::require(Capabilities::MANAGE_CRM_ADMIN_TOOLS);
require_sesskey();
$id = required_param('id', PARAM_INT);
$action = required_param('action', PARAM_ALPHA);
$repo = new MoodleCommercePersonalOfferRepository($DB);
$offer = $repo->get_by_id($id) ?? throw new moodle_exception('commerce_personal_offer_not_found', 'local_subscriptions');
$admin = new CommercePersonalOfferAdminService($DB);
if ($action === 'revoke') {
    $admin->revoke($offer, (int)$USER->id, trim(optional_param('reason', '', PARAM_TEXT)) ?: null);
    redirect(new moodle_url('/local/subscriptions/admin/commerce/personal-offers/view.php', ['id' => $id]), get_string('commerce_personal_offer_revoked_success', 'local_subscriptions'));
}
if ($action === 'reissue') {
    $new = $admin->reissue($offer, (int)$USER->id, max(1, required_param('validitydays', PARAM_INT)));
    redirect(new moodle_url('/local/subscriptions/admin/commerce/personal-offers/view.php', ['id' => $new->get_id()]), get_string('commerce_personal_offer_reissued_success', 'local_subscriptions'));
}


if ($action === 'delete') {
    $admin->delete($offer);
    redirect(new moodle_url('/local/subscriptions/admin/commerce/personal-offers/index.php'), get_string('commerce_personal_offer_deleted_success', 'local_subscriptions'));
}

if ($action === 'sendmail') {
    CommercePersonalOfferMailService::create($DB)->queue_offer($id);
    redirect(new moodle_url('/local/subscriptions/admin/commerce/personal-offers/view.php', ['id' => $id]), get_string('commerce_personal_offer_mail_queued_success', 'local_subscriptions'));
}
throw new moodle_exception('invalidaction');
