<?php

namespace local_subscriptions\crm\inbox\workspace;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\workspace\WorkspaceDefinition;
use local_subscriptions\crm\workspace\WorkspaceLayout;
use local_subscriptions\crm\workspace\WorkspacePersonalizationOptions;
use local_subscriptions\crm\workspace\rendering\WorkspacePersonalizationRenderer;

/**
 * Inbox thread compatibility wrapper for the generic Workspace renderer.
 */
final class InboxThreadPersonalizationRenderer {

    public static function render(
        WorkspaceDefinition $definition,
        WorkspaceLayout|array $layout
    ): string {
        $options = new WorkspacePersonalizationOptions(
            panelid:
                'crm-inbox-thread-personalization-panel',

            titleid:
                'crm-inbox-thread-personalization-title',

            openlabel: get_string(
                'inbox_workspace_personalization_open',
                'local_subscriptions'
            ),

            title: get_string(
                'inbox_workspace_personalization_title',
                'local_subscriptions'
            ),

            description: get_string(
                'inbox_workspace_personalization_description',
                'local_subscriptions'
            ),

            closelabel: get_string(
                'inbox_workspace_personalization_close',
                'local_subscriptions'
            ),

            resetlabel: get_string(
                'reset',
                'core'
            ),

            saveerror: get_string(
                'inbox_workspace_personalization_save_error',
                'local_subscriptions'
            ),

            resetconfirm: get_string(
                'inbox_workspace_personalization_reset_confirm',
                'local_subscriptions'
            ),

            savemethod:
                'local_subscriptions_save_workspace_layout',

            zonelabels: [
                InboxThreadWorkspaceFactory::ZONE_READING =>
                    get_string(
                        'inbox_workspace_zone_reading',
                        'local_subscriptions'
                    ),

                InboxThreadWorkspaceFactory::ZONE_CONTEXT =>
                    get_string(
                        'inbox_workspace_zone_context',
                        'local_subscriptions'
                    ),
            ],

            includefixeditems: false,

            rootclass:
                'crm-inbox-thread-personalization'
        );

        return WorkspacePersonalizationRenderer::render(
            $definition,
            $layout,
            $options
        );
    }
}