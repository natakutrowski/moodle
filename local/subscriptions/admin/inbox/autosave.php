<?php

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\inbox\services\InboxDraftAutosaveService;

AdminSecurity::require(
    Capabilities::MANAGE_INBOX
);

require_sesskey();

header('Content-Type: application/json; charset=utf-8');

try {
    $mode = required_param(
        'mode',
        PARAM_ALPHA
    );

    if (!in_array(
        $mode,
        ['compose', 'reply'],
        true
    )) {
        throw new invalid_parameter_exception(
            'Invalid autosave mode.'
        );
    }

    $service = new InboxDraftAutosaveService();

    $result = $service->save(
        $mode,
        optional_param(
            'accountid',
            0,
            PARAM_INT
        ),
        optional_param(
            'threadid',
            0,
            PARAM_INT
        ),
        optional_param(
            'subject',
            '',
            PARAM_TEXT
        ),
        optional_param(
            'body',
            '',
            PARAM_RAW
        ),
        optional_param(
            'bodyhtml',
            '',
            PARAM_RAW
        ),
        optional_param_array(
            'to',
            [],
            PARAM_RAW_TRIMMED
        ),
        optional_param_array(
            'cc',
            [],
            PARAM_RAW_TRIMMED
        ),
        optional_param_array(
            'bcc',
            [],
            PARAM_RAW_TRIMMED
        ),
        (int)$USER->id
    );

    echo json_encode(
        [
            'success' => true,
            'threadid' => $result['threadid'],
            'draftid' => $result['draftid'],
            'savedat' => $result['savedat'],
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );
} catch (Throwable $exception) {
    http_response_code(400);

    echo json_encode(
        [
            'success' => false,
            'message' => $exception->getMessage(),
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );
}
