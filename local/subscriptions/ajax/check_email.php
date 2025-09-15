<?php
// local/subscriptions/ajax/check_email.php
define('AJAX_SCRIPT', true);
define('NO_MOODLE_COOKIES', true);

require_once(__DIR__ . '/../../../config.php');

header('Content-Type: application/json; charset=utf-8');

$email = required_param('email', PARAM_RAW_TRIMMED);
$exists = false;

if ($email !== '') {
    $exists = $DB->record_exists('user', [
        'email'   => \core_text::strtolower($email),
        'deleted' => 0
    ]);
}

echo json_encode(['exists' => (bool)$exists]);
