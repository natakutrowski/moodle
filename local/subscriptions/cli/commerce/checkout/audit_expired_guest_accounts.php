<?php

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../config.php');

use local_subscriptions\commerce\checkout\guest\CommerceGuestCheckoutSessionRepository;

$repository = new CommerceGuestCheckoutSessionRepository($DB);
$sessions = $repository->find_expired(time());

echo "== Commerce 7.95 H5.2 — Expired Guest Checkout audit ==\n";
echo 'Expired sessions: ' . count($sessions) . "\n\n";
foreach ($sessions as $session) {
    echo implode(' | ', [
        $session->get_reference(),
        $session->get_status(),
        $session->get_email() ?? '-',
        $session->get_user_id() === null ? '-' : (string) $session->get_user_id(),
    ]) . "\n";
}
echo "\nDry audit only: no account or session was modified.\n";
