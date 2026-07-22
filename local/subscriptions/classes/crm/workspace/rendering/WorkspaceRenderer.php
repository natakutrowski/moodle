<?php

namespace local_subscriptions\crm\workspace\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\crm\workspace\WorkspaceDefinition;
use local_subscriptions\crm\workspace\WorkspaceLayout;

/**
 * Generic CRM Workspace renderer.
 */
final class WorkspaceRenderer {

    /**
     * Opens a Workspace root.
     */
    public static function start(
        WorkspaceDefinition $definition,
        WorkspaceLayout $layout,
        array $attributes = []
    ): string {
        $attributes = array_merge(
            [
                'class' =>
                    'crm-workspace crm-workspace-' .
                    $definition->key,
                'data-region' => 'crm-workspace',
                'data-workspace' => $definition->key,
                'data-workspace-version' =>
                    (string)WorkspaceLayout::VERSION,
                'data-workspace-hidden-count' =>
                    (string)$layout->hidden_count(),
            ],
            $attributes
        );

        return html_writer::start_tag(
            'div',
            $attributes
        );
    }

    /**
     * Closes a Workspace root.
     */
    public static function end(): string {
        return html_writer::end_tag('div');
    }

    /**
     * Renders one Workspace zone.
     */
    public static function render_zone(
        WorkspaceDefinition $definition,
        WorkspaceLayout $layout,
        string $zone,
        string $classes = ''
    ): string {
        if (
            !in_array(
                $zone,
                $definition->zones(),
                true
            )
        ) {
            return '';
        }

        $keys = $layout->visible_keys($zone);

        if ($keys === []) {
            return '';
        }

        $zoneclasses = trim(
            'crm-workspace-zone ' .
            'crm-workspace-zone-' . $zone .
            ' ' . $classes
        );

        $out = html_writer::start_div(
            $zoneclasses,
            [
                'data-region' => 'workspace-zone',
                'data-workspace-zone' => $zone,
            ]
        );

        foreach ($keys as $key) {
            $item = $definition->item($key);

            if ($item === null) {
                continue;
            }

            $out .= WorkspaceItemRenderer::render(
                $definition->key,
                $item
            );
        }

        $out .= html_writer::end_div();

        return $out;
    }
}