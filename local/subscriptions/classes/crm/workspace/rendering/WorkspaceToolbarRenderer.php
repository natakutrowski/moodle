<?php

namespace local_subscriptions\crm\workspace\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\crm\workspace\WorkspaceToolbarState;

/**
 * Renders the generic CRM Workspace editing toolbar.
 */
final class WorkspaceToolbarRenderer {

    /**
     * Renders the Workspace toolbar.
     */
    public static function render(
        WorkspaceToolbarState $state
    ): string {
        $content =
            self::render_identity($state->workspacekey)
            . self::render_meta($state)
            . self::render_actions($state);

        return html_writer::tag(
            'aside',
            $content,
            [
                'class' =>
                    'crm-workspace-toolbar',
                'data-region' =>
                    'workspace-toolbar',
                'data-workspace' =>
                    $state->workspacekey,
                'data-workspace-toolbar-state' =>
                    'clean',
                'data-status-clean' =>
                    get_string(
                        'workspace_toolbar_status_clean',
                        'local_subscriptions'
                    ),
                'data-status-dirty' =>
                    get_string(
                        'workspace_toolbar_status_dirty',
                        'local_subscriptions'
                    ),
                'data-status-saving' =>
                    get_string(
                        'workspace_toolbar_status_saving',
                        'local_subscriptions'
                    ),                    
                'data-hidden-singular' =>
                    get_string(
                        'workspace_toolbar_hidden_singular',
                        'local_subscriptions'
                    ),
                'data-hidden-plural' =>
                    get_string(
                        'workspace_toolbar_hidden_plural',
                        'local_subscriptions'
                    ),
                'aria-labelledby' =>
                    'crm-workspace-toolbar-title-' .
                    $state->workspacekey,
                'hidden' => 'hidden',
            ]
        );
    }

    /**
     * Renders the toolbar title and description.
     */
    private static function render_identity(
        string $workspacekey
    ): string {
        $icon = html_writer::span(
            '🛠️',
            'crm-workspace-toolbar-icon',
            [
                'aria-hidden' => 'true',
            ]
        );

        $title = html_writer::tag(
            'h2',
            get_string(
                'workspace_toolbar_title',
                'local_subscriptions'
            ),
            [
                'class' =>
                    'crm-workspace-toolbar-title',
                'id' =>
                    'crm-workspace-toolbar-title-' .
                    $workspacekey,
            ]
        );

        $description = html_writer::div(
            get_string(
                'workspace_toolbar_description',
                'local_subscriptions'
            ),
            'crm-workspace-toolbar-description'
        );

        return html_writer::div(
            $icon
            . html_writer::div(
                $title . $description,
                'crm-workspace-toolbar-heading'
            ),
            'crm-workspace-toolbar-identity'
        );
    }

    /**
     * Renders dirty state and hidden-item counter.
     */
    private static function render_meta(
        WorkspaceToolbarState $state
    ): string {
        $status = html_writer::div(
            html_writer::span(
                '',
                'crm-workspace-toolbar-status-indicator',
                [
                    'aria-hidden' => 'true',
                ]
            )
            . html_writer::span(
                get_string(
                    'workspace_toolbar_status_clean',
                    'local_subscriptions'
                ),
                'crm-workspace-toolbar-status-text',
                [
                    'data-region' =>
                        'workspace-toolbar-status',
                ]
            ),
            'crm-workspace-toolbar-status',
            [
                'role' => 'status',
                'aria-live' => 'polite',
                'aria-atomic' => 'true',
            ]
        );

        $count = html_writer::span(
            (string)$state->normalized_hidden_count(),
            'crm-workspace-toolbar-hidden-number',
            [
                'data-region' =>
                    'workspace-toolbar-hidden-count',
            ]
        );

        $labelkey =
            $state->normalized_hidden_count() === 1
                ? 'workspace_toolbar_hidden_singular'
                : 'workspace_toolbar_hidden_plural';

        $label = html_writer::span(
            get_string(
                $labelkey,
                'local_subscriptions'
            ),
            'crm-workspace-toolbar-hidden-label',
            [
                'data-region' =>
                    'workspace-toolbar-hidden-label',
            ]
        );

        $hiddenitems = html_writer::div(
            $count . ' ' . $label,
            'crm-workspace-toolbar-hidden'
        );

        return html_writer::div(
            $status . $hiddenitems,
            'crm-workspace-toolbar-meta'
        );
    }

    /**
     * Renders editing actions.
     */
    private static function render_actions(
        WorkspaceToolbarState $state
    ): string {
        $reset = '';

        if ($state->canreset) {
            $reset = html_writer::tag(
                'button',
                html_writer::span(
                    '↺',
                    'crm-workspace-toolbar-action-icon',
                    ['aria-hidden' => 'true']
                )
                . html_writer::span(
                    get_string(
                        'workspace_toolbar_reset',
                        'local_subscriptions'
                    )
                ),
                [
                    'type' => 'button',
                    'class' =>
                        'btn btn-outline-secondary ' .
                        'crm-workspace-toolbar-button ' .
                        'crm-workspace-toolbar-reset',
                    'data-action' =>
                        'workspace-toolbar-reset',
                ]
            );
        }

        $cancel = html_writer::tag(
            'button',
            get_string(
                'workspace_toolbar_cancel',
                'local_subscriptions'
            ),
            [
                'type' => 'button',
                'class' =>
                    'btn btn-outline-secondary ' .
                    'crm-workspace-toolbar-button',
                'data-action' =>
                    'workspace-toolbar-cancel',
            ]
        );

        $save = '';

        if ($state->cansave) {
            $save = html_writer::tag(
                'button',
                html_writer::span(
                    '✓',
                    'crm-workspace-toolbar-action-icon',
                    ['aria-hidden' => 'true']
                )
                . html_writer::span(
                    get_string(
                        'workspace_toolbar_save',
                        'local_subscriptions'
                    )
                ),
                [
                    'type' => 'button',
                    'class' =>
                        'btn btn-primary ' .
                        'crm-workspace-toolbar-button ' .
                        'crm-workspace-toolbar-save',
                    'data-action' =>
                        'workspace-toolbar-save',
                    'disabled' => 'disabled',
                ]
            );
        }

        return html_writer::div(
            $reset
            . html_writer::div(
                $cancel . $save,
                'crm-workspace-toolbar-actions-primary'
            ),
            'crm-workspace-toolbar-actions'
        );
    }
}