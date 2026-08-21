<?php

namespace local_subscriptions\crm\user360\workspace;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\crm\workspace\WorkspacePreferenceService;
use local_subscriptions\crm\workspace\rendering\WorkspaceRenderer;
use local_subscriptions\crm\user360\rendering\User360OverviewRenderer;
use local_subscriptions\crm\user360\rendering\User360SupportOverviewRenderer;
use local_subscriptions\crm\user360\rendering\User360AdvancedRenderer;

/**
 * Renders the CRM User360 Workspace.
 */
final class User360WorkspaceRenderer {

    /**
     * Renders the complete User360 Workspace.
     */
    public static function render(
        \stdClass $profile,
        ?int $userid = null
    ): string {
        global $USER;

        $userid = $userid ?? (int)$USER->id;

        $definition =
            User360WorkspaceFactory::create(
                $profile
            );

        $preferences =
            new WorkspacePreferenceService(
                $definition
            );

        $layout = $preferences->load(
            $userid
        );

        $out = WorkspaceRenderer::start(
            $definition,
            $layout,
            [
                'class' =>
                    'crm-workspace ' .
                    'crm-workspace-user360 ' .
                    'crm-user360-workspace',

                'aria-label' => get_string(
                    'user360_workspace_region_label',
                    'local_subscriptions'
                ),
            ]
        );

        $out .= WorkspaceRenderer::render_zone(
            $definition,
            $layout,
            User360WorkspaceFactory::ZONE_HERO,
            'crm-user360-workspace-hero'
        );

        $out .= WorkspaceRenderer::render_zone(
            $definition,
            $layout,
            User360WorkspaceFactory::ZONE_SUMMARY,
            'crm-user360-workspace-summary-zone'
        );

        $out .= User360SupportOverviewRenderer::render(
            $profile
        );

        $out .= User360AdvancedRenderer::render(
            $profile
        );

        $out .= WorkspaceRenderer::end();

        return $out;
    }

    /**
     * Determines whether a zone contains a visible registered item.
     */
    private static function zone_has_registered_items(
        \local_subscriptions\crm\workspace\WorkspaceDefinition $definition,
        \local_subscriptions\crm\workspace\WorkspaceLayout $layout,
        string $zone
    ): bool {
        foreach (
            $layout->visible_keys($zone)
            as $key
        ) {
            if ($definition->has_item($key)) {
                return true;
            }
        }

        return false;
    }
}