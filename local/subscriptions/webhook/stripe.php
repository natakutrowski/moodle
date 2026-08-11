<?php
require_once(__DIR__.'/../../../config.php');

$PAGE->set_context(\context_system::instance());
$payload = file_get_contents('php://input');
$headers = function_exists('getallheaders') ? getallheaders() : [];

$profile = optional_param('profile', '', PARAM_ALPHANUMEXT);
$gateway = new \local_subscriptions\payment\stripe\StripeGateway($profile !== '' ? $profile : null);
$event = $gateway->parse_webhook($payload, $headers);

\local_subscriptions\payment\EventRouter::handle($event);

http_response_code(200);
echo 'ok';
