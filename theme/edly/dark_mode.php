<?php
// CampusFR: per-user preference for the Edly dark visual mode.
// This endpoint intentionally does not touch subscriptions or payments.
require_once(__DIR__ . '/../../config.php');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit;
}
require_login();
require_sesskey();

// The setting must be enforced server-side: hiding the toggle alone is insufficient.
if ((string)get_config('theme_edly', 'campus_dark_mode_available') === '0') {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(['success' => false, 'error' => 'dark_mode_disabled']);
    exit;
}


$mode = required_param('mode', PARAM_ALPHA);
if (!in_array($mode, ['light', 'dark'], true)) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'invalid_mode']);
    exit;
}
set_user_preference('theme_edly_dark_mode', $mode === 'dark' ? 1 : 0);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
echo json_encode(['success' => true, 'mode' => $mode]);
