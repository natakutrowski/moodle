<?php

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\commandcenter\repositories\UserSearchRepository;
use local_subscriptions\subscription_config;
use moodle_url;

AdminSecurity::require(
    Capabilities::MANAGE_INBOX
);

require_sesskey();

header('Content-Type: application/json; charset=utf-8');

$query = trim(
    optional_param(
        'q',
        '',
        PARAM_RAW_TRIMMED
    )
);

if (\core_text::strlen($query) < 2) {
    echo json_encode(
        ['results' => []],
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

$repository = new UserSearchRepository();
$results = [];

foreach ($repository->search($query, 12) as $user) {
    $email = trim((string)$user->email);

    if ($email === '') {
        continue;
    }

    $results[] = [
        'id' => (int)$user->id,
        'name' => fullname($user),
        'email' => $email,
        'username' => (string)$user->username,
        'suspended' => !empty($user->suspended),
        'user360url' => (
            new moodle_url(
                subscription_config::
                    admin_user_view_page(),
                ['id' => (int)$user->id]
            )
        )->out(false),
    ];
}

echo json_encode(
    ['results' => $results],
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
);

exit;
