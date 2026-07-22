<?php

namespace local_subscriptions\crm\user360\workspace;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\workspace\WorkspaceDefinition;
use local_subscriptions\crm\workspace\WorkspaceLayout;
use local_subscriptions\crm\workspace\WorkspacePersonalizationOptions;
use local_subscriptions\crm\workspace\rendering\WorkspacePersonalizationRenderer;

/**
 * User360 compatibility wrapper for the generic Workspace
 * personalization renderer.
 */
final class User360PersonalizationRenderer {

    /**
     * Renders the User360 personalization controller.
     */
    public static function render(
        WorkspaceDefinition $definition,
        WorkspaceLayout|array $layout
    ): string {
        $options = new WorkspacePersonalizationOptions(
            panelid:
                'crm-user360-personalization-panel',

            titleid:
                'crm-user360-personalization-title',

            openlabel: get_string(
                'user360_workspace_personalization_open',
                'local_subscriptions'
            ),

            title: get_string(
                'user360_workspace_personalization_title',
                'local_subscriptions'
            ),

            description: get_string(
                'user360_workspace_personalization_description',
                'local_subscriptions'
            ),

            closelabel: get_string(
                'user360_workspace_personalization_close',
                'local_subscriptions'
            ),

            resetlabel: get_string(
                'reset',
                'core'
            ),

            saveerror: get_string(
                'user360_workspace_personalization_save_error',
                'local_subscriptions'
            ),

            resetconfirm: get_string(
                'user360_workspace_personalization_reset_confirm',
                'local_subscriptions'
            ),

            savemethod:
                'local_subscriptions_save_workspace_layout',

            zonelabels: [
                User360WorkspaceFactory::ZONE_HERO =>
                    get_string(
                        'user360_workspace_zone_hero',
                        'local_subscriptions'
                    ),

                User360WorkspaceFactory::ZONE_SUMMARY =>
                    get_string(
                        'user360_workspace_zone_summary',
                        'local_subscriptions'
                    ),

                User360WorkspaceFactory::ZONE_MAIN =>
                    get_string(
                        'user360_workspace_zone_main',
                        'local_subscriptions'
                    ),

                User360WorkspaceFactory::ZONE_SIDEBAR =>
                    get_string(
                        'user360_workspace_zone_sidebar',
                        'local_subscriptions'
                    ),

                User360WorkspaceFactory::ZONE_TIMELINE =>
                    get_string(
                        'user360_workspace_zone_timeline',
                        'local_subscriptions'
                    ),
            ],

            includefixeditems: false,

            rootclass:
                'crm-user360-personalization'
        );

        return WorkspacePersonalizationRenderer::render(
            $definition,
            $layout,
            $options
        );
    }
}