<?php

namespace local_subscriptions\crm\user360\workspace;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\crm\workspace\WorkspacePreferenceService;
use local_subscriptions\crm\workspace\WorkspaceToolbarState;
use local_subscriptions\crm\workspace\rendering\WorkspaceRenderer;
use local_subscriptions\crm\workspace\rendering\WorkspaceToolbarRenderer;

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

        if (empty($profile->iscommerceguest)) {
            $out .= html_writer::div(
                User360PersonalizationRenderer::render(
                    $definition,
                    $layout
                ),
                'crm-user360-workspace-heading'
            );

            $out .= WorkspaceToolbarRenderer::render(
                new WorkspaceToolbarState(
                    workspacekey:
                        $definition->key,

                    hiddencount:
                        $layout->hidden_count(),

                    canreset: true,

                    cansave: true
                )
            );
        }

        $mainzone =
            WorkspaceRenderer::render_zone(
                $definition,
                $layout,
                User360WorkspaceFactory::ZONE_MAIN,
                'crm-user360-workspace-main-zone'
            );

        $sidebarzone =
            WorkspaceRenderer::render_zone(
                $definition,
                $layout,
                User360WorkspaceFactory::ZONE_SIDEBAR,
                'crm-user360-workspace-sidebar-zone'
            );

        $hasmain =
            self::zone_has_registered_items(
                $definition,
                $layout,
                User360WorkspaceFactory::ZONE_MAIN
            );

        $hassidebar =
            self::zone_has_registered_items(
                $definition,
                $layout,
                User360WorkspaceFactory::ZONE_SIDEBAR
            );

        if ($hasmain || $hassidebar) {
            $layoutclasses =
                'crm-user360-workspace-layout';

            if ($hasmain && $hassidebar) {
                $layoutclasses .=
                    ' has-main has-sidebar';
            } else if ($hasmain) {
                $layoutclasses .=
                    ' has-main without-sidebar';
            } else {
                $layoutclasses .=
                    ' without-main has-sidebar';
            }

            $out .= html_writer::start_div(
                $layoutclasses
            );

            if ($mainzone !== '') {
                $out .= html_writer::tag(
                    'main',
                    $mainzone,
                    [
                        'class' =>
                            'crm-user360-workspace-main',

                        'aria-label' => get_string(
                            'user360_workspace_zone_main',
                            'local_subscriptions'
                        ),
                    ]
                );
            }

            if ($sidebarzone !== '') {
                $out .= html_writer::tag(
                    'aside',
                    $sidebarzone,
                    [
                        'class' =>
                            'crm-user360-workspace-sidebar',

                        'aria-label' => get_string(
                            'user360_workspace_zone_sidebar',
                            'local_subscriptions'
                        ),
                    ]
                );
            }

            $out .= html_writer::end_div();
        }

        $out .= WorkspaceRenderer::render_zone(
            $definition,
            $layout,
            User360WorkspaceFactory::ZONE_TIMELINE,
            'crm-user360-workspace-timeline-zone'
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