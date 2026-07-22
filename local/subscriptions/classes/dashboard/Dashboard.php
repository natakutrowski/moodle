<?php

namespace local_subscriptions\dashboard;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\crm\workspace\WorkspacePreferenceService;
use local_subscriptions\crm\workspace\WorkspaceToolbarState;
use local_subscriptions\crm\workspace\rendering\WorkspaceRenderer;
use local_subscriptions\crm\workspace\rendering\WorkspaceToolbarRenderer;
use local_subscriptions\dashboard\personalization\DashboardPersonalizationRenderer;
use local_subscriptions\dashboard\services\DashboardPeriod;
use local_subscriptions\dashboard\workspace\DashboardWorkspaceFactory;
use local_subscriptions\crm\workspace\WorkspaceDefinition;
use local_subscriptions\crm\workspace\WorkspaceLayout;

/**
 * Dashboard implemented as a CRM Workspace.
 */
final class Dashboard {

    /**
     * Renders the complete personalized Dashboard Workspace.
     */
    public static function render(
        string $period = DashboardPeriod::TODAY,
        string $returnurl = ''
    ): string {
        global $USER;

        $period = DashboardPeriod::normalize($period);

        $definition =
            DashboardWorkspaceFactory::create(
                (int)$USER->id,
                $period,
                $returnurl
            );

        $preferences =
            new WorkspacePreferenceService($definition);

        $layout = $preferences->load(
            (int)$USER->id
        );

        $out = WorkspaceRenderer::start(
            $definition,
            $layout,
            [
                'class' =>
                    'crm-workspace ' .
                    'crm-workspace-dashboard ' .
                    'local-subscriptions-dashboard-workspace',
            ]
        );

        $out .= self::render_heading(
            $layout->to_array()
        );

        $out .= WorkspaceToolbarRenderer::render(
            new WorkspaceToolbarState(
                workspacekey: $definition->key,
                hiddencount: $layout->hidden_count(),
                canreset: true,
                cansave: true
            )
        );

        $out .= WorkspaceRenderer::render_zone(
            $definition,
            $layout,
            DashboardWorkspaceFactory::ZONE_ONBOARDING,
            'crm-dashboard-onboarding-zone'
        );

        $out .= self::render_workspace_zone(
            $definition,
            $layout,
            DashboardWorkspaceFactory::ZONE_HERO,
            'crm-dashboard-hero-zone',
            get_string(
                'dashboard_workspace_empty_hero',
                'local_subscriptions'
            ),
            '📊',
            true
        );

        $out .= html_writer::start_div(
            'local-subscriptions-dashboard-grid'
        );

        $out .= html_writer::start_div(
            'local-subscriptions-dashboard-main'
        );

        $out .= self::render_workspace_zone(
            $definition,
            $layout,
            DashboardWorkspaceFactory::ZONE_MAIN,
            'crm-dashboard-panels-grid',
            get_string(
                'dashboard_workspace_empty_main',
                'local_subscriptions'
            ),
            '🧩',
            false
        );
        $out .= html_writer::end_div();

        $out .= html_writer::start_div(
            'local-subscriptions-dashboard-side'
        );

        $out .= self::render_workspace_zone(
            $definition,
            $layout,
            DashboardWorkspaceFactory::ZONE_SIDE,
            'crm-dashboard-side-zone',
            get_string(
                'dashboard_workspace_empty_side',
                'local_subscriptions'
            ),
            '📌',
            true
        );

        $out .= html_writer::end_div();
        $out .= html_writer::end_div();

        $out .= WorkspaceRenderer::end();

        return $out;
    }

    /**
     * Renders the Dashboard title row and current personalization panel.
     */
    private static function render_heading(
        array $layout
    ): string {
        $out = html_writer::start_div(
            'crm-dashboard-heading-row'
        );

        $out .= html_writer::tag(
            'p',
            get_string(
                'admin_dashboard_intro',
                'local_subscriptions'
            ),
            [
                'class' =>
                    'lead text-muted mb-0',
            ]
        );

        $out .= DashboardPersonalizationRenderer::render(
            $layout
        );

        $out .= html_writer::end_div();

        return $out;
    }

    /**
     * Renders one Dashboard Workspace zone and its optional empty state.
     */
    private static function render_workspace_zone(
        WorkspaceDefinition $definition,
        WorkspaceLayout $layout,
        string $zone,
        string $class,
        string $emptymessage,
        string $emptyicon,
        bool $editonlyempty
    ): string {
        $content = WorkspaceRenderer::render_zone(
            $definition,
            $layout,
            $zone,
            $class
        );

        if ($content !== '') {
            return $content;
        }

        return self::empty_zone(
            $zone,
            $emptymessage,
            $emptyicon,
            $editonlyempty
        );
    }

    /**
     * Renders an empty Dashboard Workspace zone.
     */
    private static function empty_zone(
        string $zone,
        string $message,
        string $icon,
        bool $editonly
    ): string {
        $classes = [
            'crm-dashboard-workspace-empty-zone',
            'crm-dashboard-workspace-empty-zone-' . $zone,
        ];

        if ($editonly) {
            $classes[] =
                'crm-dashboard-workspace-empty-zone-edit-only';
        }

        $content = html_writer::div(
            s($icon),
            'crm-dashboard-workspace-empty-icon',
            [
                'aria-hidden' => 'true',
            ]
        );

        $content .= html_writer::div(
            s($message),
            'crm-dashboard-workspace-empty-label'
        );

        return html_writer::div(
            $content,
            implode(' ', $classes),
            [
                'data-workspace-empty-zone' => $zone,
                'aria-live' => 'polite',
            ]
        );
    }
}