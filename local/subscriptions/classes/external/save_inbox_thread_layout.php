<?php

namespace local_subscriptions\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\inbox\workspace\InboxThreadWorkspaceFactory;
use local_subscriptions\crm\workspace\WorkspacePreferenceService;

/**
 * Saves or resets the current user's Inbox thread layout.
 */
final class save_inbox_thread_layout
    extends external_api {

    public static function execute_parameters():
        external_function_parameters {
        return new external_function_parameters([
            'action' => new external_value(
                PARAM_ALPHA,
                'Workspace action: save or reset'
            ),
            'layout' => new external_value(
                PARAM_RAW,
                'JSON encoded Inbox thread Workspace layout',
                VALUE_DEFAULT,
                ''
            ),
        ]);
    }

    public static function execute(
        string $action,
        string $layout = ''
    ): array {
        global $USER;

        $params = self::validate_parameters(
            self::execute_parameters(),
            [
                'action' => $action,
                'layout' => $layout,
            ]
        );

        require_login();

        $context = \context_system::instance();

        self::validate_context($context);

        require_capability(
            Capabilities::VIEW_INBOX,
            $context
        );

        require_sesskey();

        $service = new WorkspacePreferenceService(
            InboxThreadWorkspaceFactory::
                create_for_preferences()
        );

        if ($params['action'] === 'reset') {
            $normalized = $service->reset(
                (int)$USER->id
            );
        } else if ($params['action'] === 'save') {
            $normalized = $service->save(
                self::decode_layout(
                    $params['layout']
                ),
                (int)$USER->id
            );
        } else {
            throw new \invalid_parameter_exception(
                'Unsupported Inbox thread Workspace action.'
            );
        }

        return [
            'success' => true,
            'layout' => json_encode(
                $normalized->to_array(),
                JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE
                | JSON_THROW_ON_ERROR
            ),
        ];
    }

    private static function decode_layout(
        string $layout
    ): array {
        if (trim($layout) === '') {
            throw new \invalid_parameter_exception(
                'The Inbox thread Workspace layout is required.'
            );
        }

        try {
            $decoded = json_decode(
                $layout,
                true,
                32,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException) {
            throw new \invalid_parameter_exception(
                'Invalid Inbox thread Workspace layout JSON.'
            );
        }

        if (!is_array($decoded)) {
            throw new \invalid_parameter_exception(
                'The Inbox thread Workspace layout must be an object.'
            );
        }

        return $decoded;
    }

    public static function execute_returns():
        external_single_structure {
        return new external_single_structure([
            'success' => new external_value(
                PARAM_BOOL,
                'Whether the operation succeeded'
            ),
            'layout' => new external_value(
                PARAM_RAW,
                'Normalized Inbox thread Workspace layout'
            ),
        ]);
    }
}