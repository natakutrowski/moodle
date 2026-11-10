<?php
// CampusFR: persist dismissal of the dark-mode onboarding hint for members.
require_once(__DIR__ . '/../../config.php');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit;
}
require_login();
require_sesskey();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (isguestuser()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'guest_not_supported']);
    exit;
}

$dismiss = optional_param('dismiss', 0, PARAM_BOOL);
if (!$dismiss) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'invalid_request']);
    exit;
}

set_user_preference('theme_edly_dark_mode_hint_dismissed', 1);
echo json_encode(['success' => true]);
