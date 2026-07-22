<?php

namespace local_subscriptions\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_subscriptions\crm\workspace\WorkspacePreferenceService;
use local_subscriptions\crm\workspace\WorkspaceRegistry;

/**
 * Saves or resets one generic CRM Workspace layout.
 */
final class save_workspace_layout extends external_api {

    /**
     * Declares the external function parameters.
     */
    public static function execute_parameters():
        external_function_parameters {
        return new external_function_parameters([
            'workspace' => new external_value(
                PARAM_ALPHANUMEXT,
                'Registered Workspace key'
            ),

            'action' => new external_value(
                PARAM_ALPHA,
                'Workspace action: save or reset'
            ),

            'layout' => new external_value(
                PARAM_RAW,
                'JSON encoded Workspace layout',
                VALUE_DEFAULT,
                ''
            ),
        ]);
    }

    /**
     * Saves or resets one Workspace layout.
     *
     * @return array{
     *     success: bool,
     *     workspace: string,
     *     layout: string
     * }
     */
    public static function execute(
        string $workspace,
        string $action,
        string $layout = ''
    ): array {
        global $USER;

        $params = self::validate_parameters(
            self::execute_parameters(),
            [
                'workspace' => $workspace,
                'action' => $action,
                'layout' => $layout,
            ]
        );

        require_login();

        $context = \context_system::instance();

        self::validate_context($context);

        $workspacekey =
            WorkspaceRegistry::normalize_key(
                $params['workspace']
            );

        require_capability(
            WorkspaceRegistry::capability(
                $workspacekey
            ),
            $context
        );

        require_sesskey();

        $definition =
            WorkspaceRegistry::definition_for_preferences(
                $workspacekey,
                (int)$USER->id
            );

        $preferences =
            new WorkspacePreferenceService(
                $definition
            );

        if ($params['action'] === 'reset') {
            $normalized = $preferences->reset(
                (int)$USER->id
            );
        } else if ($params['action'] === 'save') {
            $normalized = $preferences->save(
                self::decode_layout(
                    $params['layout']
                ),
                (int)$USER->id
            );
        } else {
            throw new \invalid_parameter_exception(
                'Unsupported Workspace action.'
            );
        }

        return [
            'success' => true,
            'workspace' => $workspacekey,
            'layout' => json_encode(
                $normalized->to_array(),
                JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE
                | JSON_THROW_ON_ERROR
            ),
        ];
    }

    /**
     * Decodes submitted Workspace layout data.
     */
    private static function decode_layout(
        string $layout
    ): array {
        if (trim($layout) === '') {
            throw new \invalid_parameter_exception(
                'The Workspace layout is required.'
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
                'Invalid Workspace layout JSON.'
            );
        }

        if (!is_array($decoded)) {
            throw new \invalid_parameter_exception(
                'The Workspace layout must be an object.'
            );
        }

        return $decoded;
    }

    /**
     * Declares the external function result.
     */
    public static function execute_returns():
        external_single_structure {
        return new external_single_structure([
            'success' => new external_value(
                PARAM_BOOL,
                'Whether the operation succeeded'
            ),

            'workspace' => new external_value(
                PARAM_ALPHANUMEXT,
                'Saved Workspace key'
            ),

            'layout' => new external_value(
                PARAM_RAW,
                'Normalized Workspace layout'
            ),
        ]);
    }
}