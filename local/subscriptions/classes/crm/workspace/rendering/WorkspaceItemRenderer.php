<?php

namespace local_subscriptions\crm\workspace\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\crm\workspace\WorkspaceItemActionDefinition;
use local_subscriptions\crm\workspace\WorkspaceItemDefinition;

/**
 * Renders the standard wrapper around one CRM Workspace item.
 */
final class WorkspaceItemRenderer {

    /**
     * Renders one Workspace item.
     *
     * The business renderer is invoked only here, after the layout
     * visibility has already been resolved.
     */
    public static function render(
        string $workspacekey,
        WorkspaceItemDefinition $item
    ): string {
        $html = $item->render();

        if (trim($html) === '') {
            return '';
        }

        $iseditable =
            $item->movable
            || $item->hideable;

        $classes = [
            'crm-workspace-item',
            'crm-workspace-item-' . $item->type,
            'crm-workspace-item-' . $item->key,
            'crm-workspace-item-span-' .
                $item->normalized_span(),
        ];

        if ($iseditable) {
            $classes[] =
                'crm-workspace-item-editable';
        }

        if ($item->movable) {
            $classes[] =
                'crm-workspace-item-movable';
        }

        if ($item->hideable) {
            $classes[] =
                'crm-workspace-item-hideable';
        }

        $content = '';

        if ($iseditable) {
            $content .= self::render_edit_chrome(
                $workspacekey,
                $item
            );
        }

        $content .= html_writer::div(
            $html,
            'crm-workspace-item-content',
            [
                'data-region' =>
                    'workspace-item-content',
            ]
        );

        return html_writer::tag(
            'section',
            $content,
            [
                'class' => implode(
                    ' ',
                    $classes
                ),
                'data-region' =>
                    'workspace-item',
                'data-workspace' =>
                    $workspacekey,
                'data-workspace-item' =>
                    $item->key,
                'data-workspace-item-label' =>
                    $item->label,
                'data-workspace-item-type' =>
                    $item->type,
                'data-workspace-zone' =>
                    $item->zone,
                'data-workspace-span' =>
                    (string)$item->normalized_span(),
                'data-workspace-editable' =>
                    $iseditable ? '1' : '0',
                'data-workspace-hideable' =>
                    $item->hideable ? '1' : '0',
                'data-workspace-movable' =>
                    $item->movable ? '1' : '0',
                'aria-label' =>
                    $item->label,
            ]
        );
    }

    /**
     * Renders the editing chrome displayed above one Workspace item.
     */
    private static function render_edit_chrome(
        string $workspacekey,
        WorkspaceItemDefinition $item
    ): string {
        $handle = '';

        if ($item->movable) {
            $handle = html_writer::span(
                self::render_grip(),
                'crm-workspace-item-edit-handle',
                [
                    'data-region' =>
                        'workspace-item-drag-handle',
                    'title' => get_string(
                        'workspace_item_drag_handle',
                        'local_subscriptions'
                    ),
                    'aria-label' => get_string(
                        'workspace_item_drag_handle_named',
                        'local_subscriptions',
                        $item->label
                    ),
                ]
            );
        }

        $icon = html_writer::span(
            $item->icon,
            'crm-workspace-item-edit-icon',
            [
                'aria-hidden' => 'true',
            ]
        );

        $label = html_writer::span(
            $item->label,
            'crm-workspace-item-edit-label'
        );

        $type = html_writer::span(
            self::get_type_label($item),
            'crm-workspace-item-edit-type'
        );

        $identity = html_writer::div(
            $icon
            . html_writer::div(
                $label . $type,
                'crm-workspace-item-edit-identity-text'
            ),
            'crm-workspace-item-edit-identity'
        );

        $actions = self::render_context_menu(
            $workspacekey,
            $item
        );

        return html_writer::div(
            $handle . $identity . $actions,
            'crm-workspace-item-edit-chrome',
            [
                'data-region' =>
                    'workspace-item-edit-chrome',
                'data-workspace-item' =>
                    $item->key,
                'hidden' => 'hidden',
            ]
        );
    }

    /**
     * Renders an item's contextual action menu.
     */
    private static function render_context_menu(
        string $workspacekey,
        WorkspaceItemDefinition $item
    ): string {
        $menuid = self::menu_id(
            $workspacekey,
            $item->key
        );

        $trigger = html_writer::tag(
            'button',
            html_writer::span(
                '⋮',
                'crm-workspace-item-menu-trigger-icon',
                [
                    'aria-hidden' => 'true',
                ]
            ),
            [
                'type' => 'button',
                'class' =>
                    'crm-workspace-item-menu-trigger',
                'data-action' =>
                    'workspace-item-menu-toggle',
                'aria-label' => get_string(
                    'workspace_item_menu_open_named',
                    'local_subscriptions',
                    $item->label
                ),
                'aria-haspopup' => 'menu',
                'aria-expanded' => 'false',
                'aria-controls' => $menuid,
            ]
        );

        $items = '';

        if ($item->movable) {
            $items .= self::render_menu_action(
                'workspace-item-move-before',
                '↑',
                get_string(
                    'workspace_item_move_before',
                    'local_subscriptions'
                )
            );

            $items .= self::render_menu_action(
                'workspace-item-move-after',
                '↓',
                get_string(
                    'workspace_item_move_after',
                    'local_subscriptions'
                )
            );
        }

        if (
            $item->movable
            && $item->hideable
        ) {
            $items .= html_writer::div(
                '',
                'crm-workspace-item-menu-separator',
                [
                    'role' => 'separator',
                ]
            );
        }

        if ($item->hideable) {
            $items .= self::render_menu_action(
                'workspace-item-hide',
                '◉',
                get_string(
                    'workspace_item_hide',
                    'local_subscriptions'
                ),
                true
            );
        }

        if (
            $item->movable
            || $item->hideable
        ) {
            $items .= html_writer::div(
                '',
                'crm-workspace-item-menu-separator',
                [
                    'role' => 'separator',
                ]
            );

            $items .= self::render_menu_action(
                'workspace-item-reset',
                '↺',
                get_string(
                    'workspace_item_reset',
                    'local_subscriptions'
                )
            );
        }

        $customactions = $item->actions();

        if (!empty($customactions)) {
            $items .= html_writer::div(
                '',
                'crm-workspace-item-menu-separator',
                [
                    'role' => 'separator',
                ]
            );

            foreach ($customactions as $action) {
                $items .= self::render_custom_menu_action(
                    $action
                );
            }
        }

        $menu = html_writer::div(
            $items,
            'crm-workspace-item-menu',
            [
                'id' => $menuid,
                'data-region' =>
                    'workspace-item-menu',
                'role' => 'menu',
                'aria-label' => get_string(
                    'workspace_item_menu_label_named',
                    'local_subscriptions',
                    $item->label
                ),
                'hidden' => 'hidden',
            ]
        );

        return html_writer::div(
            $trigger . $menu,
            'crm-workspace-item-menu-wrapper',
            [
                'data-region' =>
                    'workspace-item-menu-wrapper',
            ]
        );
    }

    /**
     * Renders one custom contextual action.
     */
    private static function render_custom_menu_action(
        WorkspaceItemActionDefinition $action
    ): string {
        $classes =
            'crm-workspace-item-menu-action ' .
            'crm-workspace-item-menu-action-custom';

        if ($action->is_danger()) {
            $classes .=
                ' crm-workspace-item-menu-action-danger';
        }

        $icon = '';

        if ($action->icon !== '') {
            $icon = html_writer::span(
                $action->icon,
                'crm-workspace-item-menu-action-icon',
                [
                    'aria-hidden' => 'true',
                ]
            );
        }

        $attributes = [
            'type' => 'button',
            'class' => $classes,
            'data-action' =>
                'workspace-item-custom-action',
            'data-workspace-custom-action' =>
                $action->key,
            'role' => 'menuitem',
            'aria-disabled' =>
                $action->enabled
                    ? 'false'
                    : 'true',
        ];

        if (!$action->enabled) {
            $attributes['disabled'] =
                'disabled';
        }

        return html_writer::tag(
            'button',
            $icon
            . html_writer::span(
                $action->label,
                'crm-workspace-item-menu-action-label'
            ),
            $attributes
        );
    }

    /**
     * Renders one contextual menu action.
     */
    private static function render_menu_action(
        string $action,
        string $icon,
        string $label,
        bool $danger = false
    ): string {
        $classes =
            'crm-workspace-item-menu-action';

        if ($danger) {
            $classes .=
                ' crm-workspace-item-menu-action-danger';
        }

        return html_writer::tag(
            'button',
            html_writer::span(
                $icon,
                'crm-workspace-item-menu-action-icon',
                [
                    'aria-hidden' => 'true',
                ]
            )
            . html_writer::span(
                $label,
                'crm-workspace-item-menu-action-label'
            ),
            [
                'type' => 'button',
                'class' => $classes,
                'data-action' => $action,
                'role' => 'menuitem',
            ]
        );
    }

    /**
     * Returns a stable and HTML-safe contextual-menu identifier.
     */
    private static function menu_id(
        string $workspacekey,
        string $itemkey
    ): string {
        $raw =
            $workspacekey . '-' . $itemkey;

        $safe = preg_replace(
            '/[^a-zA-Z0-9_-]+/',
            '-',
            $raw
        );

        return 'crm-workspace-item-menu-' .
            trim((string)$safe, '-');
    }

    /**
     * Renders a six-dot drag grip.
     */
    private static function render_grip(): string {
        $dots = '';

        for ($index = 0; $index < 6; $index++) {
            $dots .= html_writer::span(
                '',
                'crm-workspace-item-edit-handle-dot'
            );
        }

        return $dots;
    }

    /**
     * Returns the localized item type label.
     */
    private static function get_type_label(
        WorkspaceItemDefinition $item
    ): string {
        if ($item->is_widget()) {
            return get_string(
                'workspace_item_type_widget',
                'local_subscriptions'
            );
        }

        if (
            $item->type ===
            WorkspaceItemDefinition::TYPE_SYSTEM
        ) {
            return get_string(
                'workspace_item_type_system',
                'local_subscriptions'
            );
        }

        return get_string(
            'workspace_item_type_card',
            'local_subscriptions'
        );
    }
}