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
            $definition,
            $layout
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

        $out .= self::render_dashboard_section(
            $definition,
            $layout,
            DashboardWorkspaceFactory::ZONE_FOCUS,
            get_string(
                'dashboard_n1211_focus_title',
                'local_subscriptions'
            ),
            get_string(
                'dashboard_n1211_focus_description',
                'local_subscriptions'
            ),
            'crm-dashboard-focus-zone'
        );

        $out .= self::render_operations_section(
            $definition,
            $layout
        );

        $out .= self::render_dashboard_section(
            $definition,
            $layout,
            DashboardWorkspaceFactory::ZONE_INSIGHTS,
            get_string(
                'dashboard_n1211_insights_title',
                'local_subscriptions'
            ),
            get_string(
                'dashboard_n1211_insights_description',
                'local_subscriptions'
            ),
            'crm-dashboard-insights-zone'
        );

        $out .= self::render_dashboard_section(
            $definition,
            $layout,
            DashboardWorkspaceFactory::ZONE_SECONDARY,
            get_string(
                'dashboard_n1211_secondary_title',
                'local_subscriptions'
            ),
            get_string(
                'dashboard_n1211_secondary_description',
                'local_subscriptions'
            ),
            'crm-dashboard-secondary-zone'
        );

        $out .= WorkspaceRenderer::end();

        return $out;
    }

    /**
     * Renders the Dashboard title row and current personalization panel.
     */
    private static function render_heading(
        WorkspaceDefinition $definition,
        WorkspaceLayout $layout
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

        $out .= html_writer::start_div(
            'crm-dashboard-heading-actions'
        );

        $out .= WorkspaceRenderer::render_zone(
            $definition,
            $layout,
            DashboardWorkspaceFactory::ZONE_UTILITY,
            'crm-dashboard-utility-zone'
        );

        $out .= DashboardPersonalizationRenderer::render(
            $layout->to_array()
        );

        $out .= html_writer::end_div();
        $out .= html_writer::end_div();

        return $out;
    }

    /**
     * Renders the operational 2/3 + 1/3 workspace:
     * Inbox on the left, Work and Customer Success stacked on the right.
     */
    private static function render_operations_section(
        WorkspaceDefinition $definition,
        WorkspaceLayout $layout
    ): string {
        $main = WorkspaceRenderer::render_zone(
            $definition,
            $layout,
            DashboardWorkspaceFactory::ZONE_OPERATIONS,
            'crm-dashboard-operations-main'
        );

        $side = WorkspaceRenderer::render_zone(
            $definition,
            $layout,
            DashboardWorkspaceFactory::ZONE_OPERATIONS_SIDE,
            'crm-dashboard-operations-side'
        );

        if ($main === '' && $side === '') {
            return '';
        }

        $header = html_writer::div(
            html_writer::tag(
                'h2',
                s(get_string(
                    'dashboard_n1211_operations_title',
                    'local_subscriptions'
                )),
                [
                    'class' =>
                        'crm-dashboard-section-title',
                ]
            )
            . html_writer::div(
                s(get_string(
                    'dashboard_n1211_operations_description',
                    'local_subscriptions'
                )),
                'crm-dashboard-section-description'
            ),
            'crm-dashboard-section-heading'
        );

        $content = html_writer::div(
            $main . $side,
            'crm-dashboard-operations-layout'
        );

        return html_writer::tag(
            'section',
            $header . $content,
            [
                'class' =>
                    'crm-dashboard-section ' .
                    'crm-dashboard-section-operations',
            ]
        );
    }

    /**
     * Renders one semantic Dashboard section around a Workspace zone.
     */
    private static function render_dashboard_section(
        WorkspaceDefinition $definition,
        WorkspaceLayout $layout,
        string $zone,
        string $title,
        string $description,
        string $zoneclass
    ): string {
        $content = WorkspaceRenderer::render_zone(
            $definition,
            $layout,
            $zone,
            'crm-dashboard-panels-grid ' . $zoneclass
        );

        if ($content === '') {
            return '';
        }

        $header = html_writer::div(
            html_writer::tag(
                'h2',
                s($title),
                [
                    'class' =>
                        'crm-dashboard-section-title',
                ]
            )
            . html_writer::div(
                s($description),
                'crm-dashboard-section-description'
            ),
            'crm-dashboard-section-heading'
        );

        return html_writer::tag(
            'section',
            $header . $content,
            [
                'class' =>
                    'crm-dashboard-section '
                    . 'crm-dashboard-section-' . $zone,
                'data-dashboard-section' => $zone,
            ]
        );
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