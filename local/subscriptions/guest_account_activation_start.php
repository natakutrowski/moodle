<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../config.php');

use local_subscriptions\url\UrlFactory;

use local_subscriptions\commerce\checkout\guest\CommerceGuestAccountActivationService;
use local_subscriptions\commerce\checkout\guest\CommerceGuestAccountActivator;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutSessionRepository;
use local_subscriptions\commerce\order\presentation\CommerceOrderPresentationService;

\local_subscriptions\subscription_config::guard_public_access();

$reference = required_param('reference', PARAM_ALPHANUMEXT);
$repository = new CommerceGuestCheckoutSessionRepository($DB);
$token = trim((string)($SESSION->local_subscriptions_guest_checkout_token ?? ''));
$session = $token !== ''
    ? $repository->find_by_token($token)
    : null;

if (
    $session === null
    || $session->is_expired()
    || $session->get_user_id() === null
) {
    throw new moodle_exception('commerce_public_access_denied', 'local_subscriptions');
}

// H12.9-A5.1: account activation follows the same multi-attempt ownership
// contract as order_result.php. The current guest token identifies the
// provisional user; the purchase reference must then belong to that user.
$sessionmetadata = $session->get_metadata();
$order = CommerceOrderPresentationService::create()->find_for_guest_session(
    $reference,
    (string)($session->get_purchase_reference() ?? ''),
    (string)($sessionmetadata['resume_purchase_reference'] ?? '')
);

if ($order === null || !$order->is_paid()) {
    throw new moodle_exception('commerce_public_access_denied', 'local_subscriptions');
}

// L7.3.10: successful payment is authoritative. Reconcile the provisional
// Moodle account before issuing/consuming an activation link so a missed or
// reordered payment callback cannot leave confirmed access behind a suspended
// user. The activator is idempotent and also repairs the historical partial
// state where the password was stored before complete_user_login() failed.
$session = (new CommerceGuestAccountActivator($DB, $repository))
    ->activate_for_purchase($order->reference)
    ?? $session;

$metadata = $session->get_metadata();
$activationobsolete = ($metadata['account_origin'] ?? '') !== 'guest_checkout'
    || !empty($metadata['password_set_at']);

if ($activationobsolete) {
    $destination = UrlFactory::my_courses();
    if (isloggedin() && !isguestuser()) {
        redirect($destination);
    }
    redirect(new moodle_url('/login/index.php', [
        'wantsurl' => $destination->out(false),
    ]));
}

$service = new CommerceGuestAccountActivationService($DB, $repository);
redirect($service->issue_activation_url($session));
