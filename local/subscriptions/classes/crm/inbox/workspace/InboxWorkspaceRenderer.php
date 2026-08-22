<?php

namespace local_subscriptions\crm\inbox\workspace;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\crm\inbox\dto\InboxThreadListResult;
use local_subscriptions\crm\workspace\WorkspaceDefinition;
use local_subscriptions\crm\workspace\WorkspaceLayout;
use local_subscriptions\crm\workspace\WorkspacePreferenceService;
use local_subscriptions\crm\workspace\rendering\WorkspaceRenderer;

/**
 * Renders the CRM Inbox as a split-view-ready Workspace.
 *
 * During phase E-5, the reading and context zones display structural
 * placeholders. Their content will become dynamic during E-6 without
 * changing the Workspace definition or the page layout.
 */
final class InboxWorkspaceRenderer {

    /**
     * Renders the complete Inbox Workspace.
     */
    public static function render(
        InboxThreadListResult $result,
        ?int $userid = null
    ): string {
        global $USER;

        $userid =
            $userid ?? (int)$USER->id;

        $definition =
            InboxWorkspaceFactory::create(
                $result
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
                    'crm-workspace-inbox ' .
                    'crm-inbox ' .
                    'local-subscriptions-inbox-workspace ' .
                    'crm-inbox-o15-workspace',

                'aria-label' => get_string(
                    'crm_inbox_region_label',
                    'local_subscriptions'
                ),

                'data-preview-loading' =>
                    get_string(
                        'crm_inbox_preview_loading',
                        'local_subscriptions'
                    ),

                'data-preview-error' =>
                    get_string(
                        'crm_inbox_preview_error',
                        'local_subscriptions'
                    ),
            ]
        );

        $out .= html_writer::start_div(
            'crm-inbox-workspace-layout'
        );

        $out .= self::render_zone(
            $definition,
            $layout,
            InboxWorkspaceFactory::ZONE_NAVIGATION,
            'crm-inbox-workspace-navigation'
        );

        $out .= html_writer::div(
            '',
            'sr-only',
            [
                'data-inbox-preview-live-region' => '1',
                'aria-live' => 'polite',
                'aria-atomic' => 'true',
            ]
        );

        $out .= html_writer::start_tag(
            'div',
            [
                'id' =>
                    'crm-inbox-preview-regions',

                'class' =>
                    'crm-inbox-workspace-content',

                'data-region' =>
                    'inbox-workspace-content',
            ]
        );
        $out .= self::render_zone(
            $definition,
            $layout,
            InboxWorkspaceFactory::ZONE_LIST,
            'crm-inbox-workspace-list'
        );

        $out .= self::render_zone(
            $definition,
            $layout,
            InboxWorkspaceFactory::ZONE_READING,
            'crm-inbox-workspace-reading'
        );

        $out .= self::render_zone(
            $definition,
            $layout,
            InboxWorkspaceFactory::ZONE_CONTEXT,
            'crm-inbox-workspace-context'
        );

        $out .= html_writer::end_div();

        $out .= html_writer::end_div();

        $out .= WorkspaceRenderer::end();

        return $out;
    }

    /**
     * Renders one Inbox Workspace zone.
     */
    private static function render_zone(
        WorkspaceDefinition $definition,
        WorkspaceLayout $layout,
        string $zone,
        string $classes
    ): string {
        return WorkspaceRenderer::render_zone(
            $definition,
            $layout,
            $zone,
            $classes
        );
    }
}