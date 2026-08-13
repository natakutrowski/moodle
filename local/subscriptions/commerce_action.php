<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/classes/commerce/tracking/CommerceTrackedActionUrl.php');

use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutSessionRepository;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentationAccessDeniedException;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentationService;
use local_subscriptions\commerce\tracking\CommerceTrackedActionUrl;
use local_subscriptions\event\commerce_customer_action_clicked;

\local_subscriptions\subscription_config::guard_public_access();

global $DB, $SESSION, $USER;

$reference = required_param('reference', PARAM_ALPHANUMEXT);
$action = required_param('action', PARAM_ALPHANUMEXT);
$source = required_param('source', PARAM_ALPHANUMEXT);
$destination = required_param('destination', PARAM_RAW_TRIMMED);
$signature = required_param('signature', PARAM_ALPHANUMEXT);

CommerceTrackedActionUrl::validate($reference, $action, $source, $destination, $signature);

try {
    if (isloggedin() && !isguestuser()) {
        $order = CommerceOrderPresentationService::create()->find_for_user(
            $reference,
            (int)$USER->id,
            has_capability('moodle/site:config', context_system::instance()),
            (string)$USER->email
        );
    } else {
        $guestsession = (new CommerceGuestCheckoutSessionRepository($DB))->find_by_purchase_reference($reference);
        $token = trim((string)($SESSION->local_subscriptions_guest_checkout_token ?? ''));
        if ($guestsession === null || $token === '' || !hash_equals($guestsession->get_token(), $token)) {
            throw new CommerceOrderPresentationAccessDeniedException('Guest session does not own this order.');
        }
        $order = CommerceOrderPresentationService::create()->find_for_user(
            $reference,
            (int)$guestsession->get_user_id()
        );
    }
} catch (CommerceOrderPresentationAccessDeniedException $exception) {
    throw new moodle_exception('commerce_public_access_denied', 'local_subscriptions');
}

if ($order === null) {
    throw new moodle_exception('commerce_i2_order_not_found', 'local_subscriptions');
}

$eventdata = [
    'context' => context_system::instance(),
    'objectid' => $order->purchaseid,
    'other' => [
        'action' => $action,
        'source' => $source,
        'reference' => $reference,
    ],
];
if (isloggedin() && !isguestuser()) {
    $eventdata['userid'] = (int)$USER->id;
}
commerce_customer_action_clicked::create($eventdata)->trigger();

redirect(new moodle_url($destination));
