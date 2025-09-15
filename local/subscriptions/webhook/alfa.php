<?php
require_once(__DIR__.'/../../config.php');

$PAGE->set_context(\context_system::instance());
$raw = file_get_contents('php://input');
$headers = function_exists('getallheaders') ? getallheaders() : [];

$gateway = new \local_subscriptions\payment\alfa\AlfaGateway();
$event = $gateway->parse_webhook($raw, $headers);

\local_subscriptions\payment\EventRouter::handle($event);

http_response_code(200);
echo 'ok';
