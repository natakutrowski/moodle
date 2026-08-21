<?php

namespace local_subscriptions\crm\inbox\workspace;

defined('MOODLE_INTERNAL') || die();

use context_system;
use local_subscriptions\admin\Capabilities;
use local_subscriptions\crm\inbox\ai\rendering\InboxAiPanelRenderer;
use local_subscriptions\crm\inbox\rendering\InboxThreadRenderer;
use local_subscriptions\crm\workspace\WorkspaceDefinition;
use local_subscriptions\crm\workspace\WorkspaceItemDefinition;

/**
 * Builds the CRM Inbox thread Workspace.
 */
final class InboxThreadWorkspaceFactory {

    public const WORKSPACE_KEY =
        'inbox_thread';

    public const PREFERENCE_KEY =
        'local_subscriptions_inbox_thread_workspace_layout';

    public const ZONE_READING =
        'reading';

    public const ZONE_CONTEXT =
        'context';

    public const ITEM_MESSAGES =
        'inbox_thread_messages';

    public const ITEM_REPLY =
        'inbox_thread_reply';

    public const ITEM_OVERVIEW =
        'inbox_thread_overview';

    public const ITEM_CONTACT =
        'inbox_thread_contact';

    public const ITEM_ACTIONS =
        'inbox_thread_actions';

    public const ITEM_AI =
        'inbox_thread_ai';

    /**
     * Creates the thread Workspace definition.
     *
     * Protected items are registered only when the current user may
     * actually access them. This keeps the rendered Workspace, the
     * personalization panel and the persisted preference definition
     * strictly aligned.
     */
    public static function create(
        ?object $thread = null,
        bool $canmanage = false,
        bool $canuseai = false,
        ?array $airesult = null,
        bool $allowremoteimages = false
    ): WorkspaceDefinition {
        $definition = new WorkspaceDefinition(
            self::WORKSPACE_KEY,
            self::PREFERENCE_KEY,
            [
                self::ZONE_READING,
                self::ZONE_CONTEXT,
            ]
        );

        self::register_messages(
            $definition,
            $thread,
            $allowremoteimages
        );

        if ($canmanage) {
            self::register_reply(
                $definition,
                $thread
            );
        }

        self::register_overview(
            $definition,
            $thread
        );

        self::register_contact(
            $definition,
            $thread
        );

        if ($canmanage) {
            self::register_actions(
                $definition,
                $thread
            );
        }

        if ($canuseai) {
            self::register_ai(
                $definition,
                $thread,
                $canmanage,
                $airesult
            );
        }

        return $definition;
    }

    /**
     * Creates a definition suitable for preference operations.
     *
     * The definition uses the target user's effective capabilities, so
     * inaccessible items cannot be submitted through the generic AJAX
     * endpoint.
     */
    public static function create_for_preferences(
        int $userid
    ): WorkspaceDefinition {
        $context = context_system::instance();

        $canmanage = has_capability(
            Capabilities::MANAGE_INBOX,
            $context,
            $userid
        );

        $canuseai = has_capability(
            Capabilities::USE_INBOX_AI,
            $context,
            $userid
        );

        return self::create(
            thread: null,
            canmanage: $canmanage,
            canuseai: $canuseai,
            airesult: null,
            allowremoteimages: false
        );
    }

    /**
     * Registers the fixed messages area.
     */
    private static function register_messages(
        WorkspaceDefinition $definition,
        ?object $thread,
        bool $allowremoteimages = false
    ): void {
        $definition->register(
            new WorkspaceItemDefinition(
                key: self::ITEM_MESSAGES,

                label: get_string(
                    'inbox_thread_workspace_messages',
                    'local_subscriptions'
                ),

                description: get_string(
                    'inbox_thread_workspace_messages_description',
                    'local_subscriptions'
                ),

                icon: '✉️',

                zone: self::ZONE_READING,

                span: 3,

                type:
                    WorkspaceItemDefinition::TYPE_SYSTEM,

                hideable: false,

                movable: false,

                defaultvisible: true,

                renderer: static function () use (
                    $thread,
                    $allowremoteimages
                ): string {
                    if ($thread === null) {
                        return '';
                    }

                    return InboxThreadRenderer::
                        render_messages_panel(
                            $thread,
                            $allowremoteimages
                        );
                }
            )
        );
    }

    /**
     * Registers the fixed reply area.
     */
    private static function register_reply(
        WorkspaceDefinition $definition,
        ?object $thread
    ): void {
        $definition->register(
            new WorkspaceItemDefinition(
                key: self::ITEM_REPLY,

                label: get_string(
                    'inbox_thread_workspace_reply',
                    'local_subscriptions'
                ),

                description: get_string(
                    'inbox_thread_workspace_reply_description',
                    'local_subscriptions'
                ),

                icon: '↩️',

                zone: self::ZONE_READING,

                span: 3,

                type:
                    WorkspaceItemDefinition::TYPE_WIDGET,

                hideable: false,

                movable: false,

                defaultvisible: true,

                renderer: static function () use (
                    $thread
                ): string {
                    if ($thread === null) {
                        return '';
                    }

                    return InboxThreadRenderer::
                        render_reply_panel(
                            $thread,
                            true
                        );
                }
            )
        );
    }

    /**
     * Registers the conversation overview.
     */
    private static function register_overview(
        WorkspaceDefinition $definition,
        ?object $thread
    ): void {
        $definition->register(
            new WorkspaceItemDefinition(
                key: self::ITEM_OVERVIEW,

                label: get_string(
                    'inbox_thread_workspace_overview',
                    'local_subscriptions'
                ),

                description: get_string(
                    'inbox_thread_workspace_overview_description',
                    'local_subscriptions'
                ),

                icon: '📋',

                zone: self::ZONE_CONTEXT,

                span: 3,

                type:
                    WorkspaceItemDefinition::TYPE_WIDGET,

                hideable: true,

                movable: true,

                defaultvisible: true,

                renderer: static function () use (
                    $thread
                ): string {
                    if ($thread === null) {
                        return '';
                    }

                    return InboxThreadRenderer::
                        render_overview_panel(
                            $thread
                        );
                }
            )
        );
    }

    /**
     * Registers the contact panel.
     */
    private static function register_contact(
        WorkspaceDefinition $definition,
        ?object $thread
    ): void {
        $definition->register(
            new WorkspaceItemDefinition(
                key: self::ITEM_CONTACT,

                label: get_string(
                    'inbox_thread_workspace_contact',
                    'local_subscriptions'
                ),

                description: get_string(
                    'inbox_thread_workspace_contact_description',
                    'local_subscriptions'
                ),

                icon: '👤',

                zone: self::ZONE_CONTEXT,

                span: 3,

                type:
                    WorkspaceItemDefinition::TYPE_WIDGET,

                hideable: true,

                movable: true,

                defaultvisible: true,

                renderer: static function () use (
                    $thread
                ): string {
                    if ($thread === null) {
                        return '';
                    }

                    return InboxThreadRenderer::
                        render_contact_panel(
                            $thread
                        );
                }
            )
        );
    }

    /**
     * Registers Inbox management actions.
     */
    private static function register_actions(
        WorkspaceDefinition $definition,
        ?object $thread
    ): void {
        $definition->register(
            new WorkspaceItemDefinition(
                key: self::ITEM_ACTIONS,

                label: get_string(
                    'inbox_thread_workspace_actions',
                    'local_subscriptions'
                ),

                description: get_string(
                    'inbox_thread_workspace_actions_description',
                    'local_subscriptions'
                ),

                icon: '⚙️',

                zone: self::ZONE_CONTEXT,

                span: 3,

                type:
                    WorkspaceItemDefinition::TYPE_WIDGET,

                hideable: true,

                movable: true,

                defaultvisible: true,

                renderer: static function () use (
                    $thread
                ): string {
                    if ($thread === null) {
                        return '';
                    }

                    return InboxThreadRenderer::
                        render_actions_panel(
                            $thread,
                            true
                        );
                }
            )
        );
    }

    /**
     * Registers the Inbox AI assistant.
     */
    private static function register_ai(
        WorkspaceDefinition $definition,
        ?object $thread,
        bool $canmanage,
        ?array $airesult
    ): void {
        $definition->register(
            new WorkspaceItemDefinition(
                key: self::ITEM_AI,

                label: get_string(
                    'inbox_thread_workspace_ai',
                    'local_subscriptions'
                ),

                description: get_string(
                    'inbox_thread_workspace_ai_description',
                    'local_subscriptions'
                ),

                icon: '✨',

                zone: self::ZONE_READING,

                span: 3,

                type:
                    WorkspaceItemDefinition::TYPE_WIDGET,

                hideable: true,

                movable: true,

                defaultvisible: true,

                renderer: static function () use (
                    $thread,
                    $canmanage,
                    $airesult
                ): string {
                    if ($thread === null) {
                        return '';
                    }

                    return InboxAiPanelRenderer::render(
                        (int)$thread->id,
                        $airesult,
                        true,
                        $canmanage
                    );
                }
            )
        );
    }
}