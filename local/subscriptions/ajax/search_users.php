<?php

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

global $DB;

header('Content-Type: application/json; charset=utf-8');

$q = optional_param('q', '', PARAM_RAW_TRIMMED);
$q = trim($q);

if (core_text::strlen($q) < 2) {
    echo json_encode(['results' => []]);
    exit;
}

$likesql = $DB->sql_like(
    $DB->sql_concat('u.firstname', "' '", 'u.lastname', "' '", 'u.email'),
    ':q',
    false,
    false
);

$sql = "
    SELECT u.id, u.firstname, u.lastname, u.email, u.country
      FROM {user} u
     WHERE u.deleted = 0
       AND u.suspended = 0
       AND $likesql
  ORDER BY u.lastname ASC, u.firstname ASC, u.email ASC
";

$users = $DB->get_records_sql($sql, ['q' => '%' . $DB->sql_like_escape($q) . '%'], 0, 20);

$results = [];

foreach ($users as $user) {
    $currency = in_array(strtoupper((string)$user->country), ['RU', 'BY'], true) ? 'RUB' : 'EUR';

    $results[] = [
        'id' => (int)$user->id,
        'text' => fullname($user) . ' (' . $user->email . ')',
        'currency' => $currency,
    ];
}

echo json_encode(['results' => $results], JSON_UNESCAPED_UNICODE);
exit;