<?php

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\inbox\repositories\InboxTemplateRepository;
use local_subscriptions\crm\inbox\services\InboxTemplateService;

AdminSecurity::require(
    Capabilities::MANAGE_INBOX
);

header('Content-Type: application/json; charset=utf-8');

$id = required_param(
    'id',
    PARAM_INT
);

$template = (
    new InboxTemplateRepository()
)->find($id);

if (
    !$template
    || !$template->enabled
    || $template->type !==
        InboxTemplateService::TYPE_QUICK_REPLY
) {
    http_response_code(404);

    echo json_encode(
        [
            'success' => false,
            'message' => get_string(
                'crm_inbox_template_not_found_o9',
                'local_subscriptions'
            ),
        ]
    );

    exit;
}

echo json_encode(
    [
        'success' => true,
        'id' => (int)$template->id,
        'name' => (string)$template->name,
        'subject' =>
            (string)($template->subject ?? ''),
        'bodyhtml' =>
            (string)($template->bodyhtml ?? ''),
        'bodytext' =>
            (string)($template->bodytext ?? ''),
    ],
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
);
