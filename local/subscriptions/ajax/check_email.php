<?php
// local/subscriptions/ajax/check_email.php
define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');

header('Content-Type: application/json; charset=utf-8');

$email = optional_param('email', '', PARAM_RAW_TRIMMED);

// A5.7.1: this legacy endpoint must never disclose whether an account exists.
// A5.7.2 will replace email ownership resolution with an OTP challenge whose
// public response is deliberately uniform.
echo json_encode([
    'ok' => true,
    'accepted' => $email !== '',
]);
