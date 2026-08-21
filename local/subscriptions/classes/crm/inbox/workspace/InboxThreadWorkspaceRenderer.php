<?php

namespace local_subscriptions\crm\inbox\workspace;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\crm\workspace\WorkspacePreferenceService;
use local_subscriptions\crm\workspace\WorkspaceToolbarState;
use local_subscriptions\crm\workspace\rendering\WorkspaceRenderer;
use local_subscriptions\crm\workspace\rendering\WorkspaceToolbarRenderer;

/**
 * Renders one Inbox conversation as a CRM Workspace.
 */
final class InboxThreadWorkspaceRenderer {

    /**
     * Renders the complete Inbox thread Workspace.
     */
    public static function render(
        object $thread,
        bool $canmanage,
        bool $canuseai,
        ?array $airesult = null,
        ?int $userid = null,
        bool $allowremoteimages = false
    ): string {
        global $USER;

        $userid = $userid ?? (int)$USER->id;

        $definition =
            InboxThreadWorkspaceFactory::create(
                $thread,
                $canmanage,
                $canuseai,
                $airesult,
                $allowremoteimages
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
                    'crm-workspace-inbox-thread ' .
                    'local-subscriptions-inbox-thread-workspace',

                'aria-label' => get_string(
                    'crm_inbox_thread_region_label',
                    'local_subscriptions'
                ),
            ]
        );

        /*
         * The personalization controller must be inside the Workspace
         * root so generic events can reach the rendered Workspace items.
         */
        $hasconfigurableitems = false;

        foreach ($definition->items() as $item) {
            if ($item->hideable || $item->movable) {
                $hasconfigurableitems = true;
                break;
            }
        }

        if ($hasconfigurableitems) {
            $out .= html_writer::div(
                InboxThreadPersonalizationRenderer::render(
                    $definition,
                    $layout
                ),
                'crm-inbox-thread-workspace-heading'
            );

            $out .= WorkspaceToolbarRenderer::render(
                new WorkspaceToolbarState(
                    workspacekey: $definition->key,
                    hiddencount:
                        $layout->hidden_count(),
                    canreset: true,
                    cansave: true
                )
            );
        }

        $hascontext = false;

        foreach (
            $layout->visible_keys(
                InboxThreadWorkspaceFactory::ZONE_CONTEXT
            )
            as $key
        ) {
            if ($definition->has_item($key)) {
                $hascontext = true;
                break;
            }
        }

        $out .= html_writer::start_div(
            'crm-inbox-thread-workspace-layout' .
            (
                $hascontext
                    ? ' has-context'
                    : ' without-context'
            )
        );

        $out .= html_writer::start_div(
            'crm-inbox-thread-workspace-reading'
        );

        $out .= WorkspaceRenderer::render_zone(
            $definition,
            $layout,
            InboxThreadWorkspaceFactory::ZONE_READING,
            'crm-inbox-thread-reading-zone'
        );

        $out .= html_writer::end_div();

        $contextzone =
            WorkspaceRenderer::render_zone(
                $definition,
                $layout,
                InboxThreadWorkspaceFactory::ZONE_CONTEXT,
                'crm-inbox-thread-context-zone'
            );

        if ($contextzone !== '') {
            $out .= html_writer::start_tag(
                'aside',
                [
                    'class' =>
                        'crm-inbox-thread-workspace-context',

                    'aria-label' => get_string(
                        'inbox_thread_workspace_context_zone',
                        'local_subscriptions'
                    ),
                ]
            );

            $out .= $contextzone;

            $out .= html_writer::end_tag(
                'aside'
            );
        }

        $out .= html_writer::end_div();

        $out .= WorkspaceRenderer::end();

        return $out;
    }
}