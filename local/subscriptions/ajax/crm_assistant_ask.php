<?php

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');

use local_subscriptions\admin\AdminSecurity;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\assistant\ai\dto\CrmAssistantQuestion;
use local_subscriptions\crm\assistant\ai\services\CrmAssistantConversationService;

header('Content-Type: application/json; charset=utf-8');

try {
    AdminSecurity::require(
        Capabilities::USE_CRM_ASSISTANT_AI
    );

    require_sesskey();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new \moodle_exception(
            'invalidrequest'
        );
    }

    $question = required_param(
        'question',
        PARAM_RAW_TRIMMED
    );

    $scope = optional_param(
        'scope',
        CrmAssistantQuestion::SCOPE_GLOBAL,
        PARAM_ALPHANUMEXT
    );

    $userid = optional_param(
        'userid',
        0,
        PARAM_INT
    );

    $recommendationid = optional_param(
        'recommendationid',
        0,
        PARAM_INT
    );

    $language = current_language();

    $result =
        (new CrmAssistantConversationService())
            ->ask(
                new CrmAssistantQuestion(
                    question: $question,
                    language: $language,
                    scope: $scope,
                    userid:
                        $userid > 0
                            ? $userid
                            : null,
                    recommendationid:
                        $recommendationid > 0
                            ? $recommendationid
                            : null
                )
            );

    echo json_encode(
        [
            'success' =>
                $result->is_success(),
            'result' =>
                $result->to_object(),
        ],
        JSON_THROW_ON_ERROR |
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );
} catch (\Throwable $exception) {
    debugging(
        $exception->getMessage(),
        DEBUG_DEVELOPER
    );

    http_response_code(400);

    echo json_encode(
        [
            'success' => false,
            'error' =>
                get_string(
                    'crm_assistant_ai_request_failed',
                    'local_subscriptions'
                ),
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );
}