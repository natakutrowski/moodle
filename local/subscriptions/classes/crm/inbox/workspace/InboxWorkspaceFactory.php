<?php

namespace local_subscriptions\crm\inbox\workspace;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\dto\InboxThreadListResult;
use local_subscriptions\crm\inbox\rendering\InboxRenderer;
use local_subscriptions\crm\inbox\rendering\InboxWorkspacePlaceholderRenderer;
use local_subscriptions\crm\workspace\WorkspaceDefinition;
use local_subscriptions\crm\workspace\WorkspaceItemDefinition;

/**
 * Builds the CRM Inbox Workspace definition.
 */
final class InboxWorkspaceFactory {

    public const WORKSPACE_KEY = 'inbox';

    public const PREFERENCE_KEY =
        'local_subscriptions_inbox_workspace_layout';

    public const ZONE_NAVIGATION = 'navigation';
    public const ZONE_LIST = 'list';
    public const ZONE_READING = 'reading';
    public const ZONE_CONTEXT = 'context';

    public const ITEM_FILTERS =
        'inbox_filters';

    public const ITEM_THREAD_LIST =
        'inbox_thread_list';

    public const ITEM_READING_PLACEHOLDER =
        'inbox_reading_placeholder';

    public const ITEM_CONTEXT_PLACEHOLDER =
        'inbox_context_placeholder';

    /**
     * Creates the Inbox Workspace definition.
     *
     * The result is optional so the same definition may later be reused
     * by preference save/reset endpoints without executing an Inbox query.
     */
    public static function create(
        ?InboxThreadListResult $result = null
    ): WorkspaceDefinition {
        $workspace = new WorkspaceDefinition(
            self::WORKSPACE_KEY,
            self::PREFERENCE_KEY,
            [
                self::ZONE_NAVIGATION,
                self::ZONE_LIST,
                self::ZONE_READING,
                self::ZONE_CONTEXT,
            ]
        );

        $workspace->register(
            new WorkspaceItemDefinition(
                key: self::ITEM_FILTERS,
                label: get_string(
                    'inbox_workspace_filters_label',
                    'local_subscriptions'
                ),
                description: get_string(
                    'inbox_workspace_filters_description',
                    'local_subscriptions'
                ),
                icon: '🔎',
                zone: self::ZONE_NAVIGATION,
                span: 3,
                type:
                    WorkspaceItemDefinition::TYPE_SYSTEM,
                hideable: false,
                movable: false,
                defaultvisible: true,
                renderer: static function () use (
                    $result
                ): string {
                    if ($result === null) {
                        return '';
                    }

                    return InboxRenderer::render_filters(
                        $result
                    );
                }
            )
        );

        $workspace->register(
            new WorkspaceItemDefinition(
                key: self::ITEM_THREAD_LIST,
                label: get_string(
                    'inbox_workspace_thread_list_label',
                    'local_subscriptions'
                ),
                description: get_string(
                    'inbox_workspace_thread_list_description',
                    'local_subscriptions'
                ),
                icon: '✉️',
                zone: self::ZONE_LIST,
                span: 3,
                type:
                    WorkspaceItemDefinition::TYPE_SYSTEM,
                hideable: false,
                movable: false,
                defaultvisible: true,
                renderer: static function () use (
                    $result
                ): string {
                    if ($result === null) {
                        return '';
                    }

                    return InboxRenderer::render_thread_list(
                        $result
                    );
                }
            )
        );

        $workspace->register(
            new WorkspaceItemDefinition(
                key:
                    self::ITEM_READING_PLACEHOLDER,

                label: get_string(
                    'inbox_workspace_reading_placeholder_label',
                    'local_subscriptions'
                ),

                description: get_string(
                    'inbox_workspace_reading_placeholder_item_description',
                    'local_subscriptions'
                ),

                icon: '✉️',

                zone:
                    self::ZONE_READING,

                span: 3,

                type:
                    WorkspaceItemDefinition::TYPE_SYSTEM,

                hideable: false,

                movable: false,

                defaultvisible: true,

                renderer: static function (): string {
                    return
                        InboxWorkspacePlaceholderRenderer::
                            reading();
                }
            )
        );

        $workspace->register(
            new WorkspaceItemDefinition(
                key:
                    self::ITEM_CONTEXT_PLACEHOLDER,

                label: get_string(
                    'inbox_workspace_context_placeholder_label',
                    'local_subscriptions'
                ),

                description: get_string(
                    'inbox_workspace_context_placeholder_item_description',
                    'local_subscriptions'
                ),

                icon: '👤',

                zone:
                    self::ZONE_CONTEXT,

                span: 3,

                type:
                    WorkspaceItemDefinition::TYPE_SYSTEM,

                hideable: false,

                movable: false,

                defaultvisible: true,

                renderer: static function (): string {
                    return
                        InboxWorkspacePlaceholderRenderer::
                            context();
                }
            )
        );

        return $workspace;
    }

    /**
     * Creates the definition required by future preference endpoints.
     */
    public static function create_for_preferences():
        WorkspaceDefinition {
        return self::create();
    }
}