<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../config.php');

use local_subscriptions\url\UrlFactory;

use local_subscriptions\commerce\checkout\guest\CommerceGuestAccountActivationService;
use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutSessionRepository;

\local_subscriptions\subscription_config::guard_public_access();

$reference = required_param('reference', PARAM_ALPHANUMEXT);
$repository = new CommerceGuestCheckoutSessionRepository($DB);
$session = $repository->find_by_purchase_reference($reference);
$token = trim((string)($SESSION->local_subscriptions_guest_checkout_token ?? ''));

if ($session === null) {
    throw new moodle_exception('commerce_public_access_denied', 'local_subscriptions');
}

$metadata = $session->get_metadata();
$activationobsolete = ($metadata['account_origin'] ?? '') !== 'guest_checkout'
    || !empty($metadata['password_set_at']);

if ($token === '' || !hash_equals($session->get_token(), $token)) {
    if ($activationobsolete) {
        $destination = UrlFactory::my_courses();
        if (isloggedin() && !isguestuser()) {
            redirect($destination);
        }
        redirect(new moodle_url('/login/index.php', [
            'wantsurl' => $destination->out(false),
        ]));
    }

    throw new moodle_exception('commerce_public_access_denied', 'local_subscriptions');
}

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
