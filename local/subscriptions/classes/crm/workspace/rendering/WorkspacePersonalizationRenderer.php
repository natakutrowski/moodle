<?php

namespace local_subscriptions\crm\workspace\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\crm\workspace\WorkspaceDefinition;
use local_subscriptions\crm\workspace\WorkspaceItemDefinition;
use local_subscriptions\crm\workspace\WorkspaceLayout;
use local_subscriptions\crm\workspace\WorkspacePersonalizationOptions;

/**
 * Renders a generic CRM Workspace personalization panel.
 */
final class WorkspacePersonalizationRenderer {

    /**
     * Renders the customization trigger, panel and controller root.
     */
    public static function render(
        WorkspaceDefinition $definition,
        WorkspaceLayout|array $layout,
        WorkspacePersonalizationOptions $options
    ): string {
        $layoutdata =
            $layout instanceof WorkspaceLayout
                ? $layout->to_array()
                : $layout;

        $hidden = array_fill_keys(
            is_array($layoutdata['hidden'] ?? null)
                ? $layoutdata['hidden']
                : [],
            true
        );

        $panelcontent = self::header($options);

        foreach ($definition->zones() as $zone) {
            $keys =
                $layoutdata['order'][$zone]
                ?? [];

            if (!is_array($keys) || $keys === []) {
                continue;
            }

            $panelcontent .= self::zone(
                $definition,
                $options,
                $zone,
                $keys,
                $hidden
            );
        }

        $panelcontent .= self::actions($options);

        $panel = html_writer::tag(
            'section',
            $panelcontent,
            [
                'id' => $options->panelid,

                'class' =>
                    'crm-dashboard-personalization-panel ' .
                    'crm-workspace-personalization-panel',

                'data-region' =>
                    'workspace-personalization-panel',

                'role' => 'dialog',

                'aria-modal' => 'true',

                'aria-labelledby' =>
                    $options->titleid,

                'tabindex' => '-1',

                'hidden' => 'hidden',
            ]
        );

        $button = self::open_button($options);

        $rootclasses = [
            'crm-dashboard-personalization',
            'crm-workspace-personalization',
        ];

        if ($options->rootclass !== '') {
            $rootclasses[] = $options->rootclass;
        }

        return html_writer::div(
            $button . $panel,
            implode(' ', $rootclasses),
            [
                'data-region' =>
                    'workspace-edit-controller',
                'data-workspace' =>
                    $definition->key,
                'data-workspace-editing' => '0',
                'data-workspace-dirty' => '0',
                'data-save-method' =>
                    $options->savemethod,
                'data-save-error' =>
                    $options->saveerror,
                'data-reset-confirm' =>
                    $options->resetconfirm,
            ]
        );
    }

    /**
     * Renders the button opening the personalization panel.
     */
    private static function open_button(
        WorkspacePersonalizationOptions $options
    ): string {
        return html_writer::tag(
            'button',
            html_writer::span(
                '⚙️',
                'crm-dashboard-customize-icon',
                [
                    'aria-hidden' => 'true',
                ]
            )
            . html_writer::span(
                s($options->openlabel)
            ),
            [
                'type' => 'button',
                'class' =>
                    'btn btn-outline-secondary ' .
                    'crm-dashboard-customize-button ' .
                    'crm-workspace-customize-button',
                'data-action' =>
                    'open-workspace-personalization',
                'aria-expanded' => 'false',
                'aria-controls' =>
                    $options->panelid,
            ]
        );
    }

    /**
     * Renders the panel heading.
     */
    private static function header(
        WorkspacePersonalizationOptions $options
    ): string {
        $title = html_writer::tag(
            'h2',
            s($options->title),
            [
                'id' => $options->titleid,
                'class' =>
                    'crm-dashboard-personalization-title',
            ]
        );

        $description = html_writer::div(
            s($options->description),
            'crm-dashboard-personalization-description'
        );

        $content = $title . $description;

        if ($options->orderhint !== '') {
            $content .= html_writer::div(
                html_writer::span(
                    '↕',
                    'crm-dashboard-personalization-order-hint-icon',
                    [
                        'aria-hidden' => 'true',
                    ]
                )
                . html_writer::span(
                    s($options->orderhint)
                ),
                'crm-dashboard-personalization-order-hint'
            );
        }

        $close = html_writer::tag(
            'button',
            '×',
            [
                'type' => 'button',
                'class' =>
                    'crm-dashboard-personalization-close',
                'data-action' =>
                    'close-workspace-personalization',
                'aria-label' =>
                    $options->closelabel,
            ]
        );

        return html_writer::tag(
            'header',
            html_writer::div($content)
            . $close,
            [
                'class' =>
                    'crm-dashboard-personalization-header',
            ]
        );
    }

    /**
     * Renders one Workspace zone.
     *
     * @param string[] $keys
     * @param array<string, bool> $hidden
     */
    private static function zone(
        WorkspaceDefinition $definition,
        WorkspacePersonalizationOptions $options,
        string $zone,
        array $keys,
        array $hidden
    ): string {
        $items = '';

        foreach ($keys as $key) {
            if (!is_string($key)) {
                continue;
            }

            $item = $definition->item($key);

            if ($item === null) {
                continue;
            }

            if (
                !$options->includefixeditems
                && !$item->hideable
                && !$item->movable
            ) {
                continue;
            }

            $items .= self::item(
                $item,
                isset($hidden[$key]),
                $options
            );
        }

        if ($items === '') {
            return '';
        }

        return html_writer::tag(
            'section',
            html_writer::tag(
                'h3',
                s($options->zone_label($zone)),
                [
                    'class' =>
                        'crm-dashboard-personalization-zone-title',
                ]
            )
            . html_writer::tag(
                'ul',
                $items,
                [
                    'class' =>
                        'crm-dashboard-personalization-list ' .
                        'crm-workspace-personalization-list',
                    'data-zone' => $zone,
                ]
            ),
            [
                'class' =>
                    'crm-dashboard-personalization-zone ' .
                    'crm-workspace-personalization-zone',
            ]
        );
    }

    /**
     * Renders one Workspace item.
     */
    private static function item(
        WorkspaceItemDefinition $item,
        bool $hidden,
        WorkspacePersonalizationOptions $options
    ): string {
        $checkboxid =
            'workspace-' .
            self::html_id($item->key) .
            '-visible';

        $presentation =
            $options->item_presentation(
                $item->key
            );

        $checkboxattributes = [
            'type' => 'checkbox',
            'id' => $checkboxid,
            'class' =>
                'form-check-input ' .
                'crm-dashboard-card-visibility ' .
                'crm-workspace-item-visibility',
            'data-card-key' => $item->key,
            'checked' =>
                $hidden ? null : 'checked',
            'disabled' =>
                $item->hideable
                    ? null
                    : 'disabled',
            'aria-label' =>
                $options->visibility_label($item),
        ];

        $checkbox = html_writer::empty_tag(
            'input',
            $checkboxattributes
        );

        $titleline = html_writer::div(
            html_writer::span(
                s($item->label),
                'crm-dashboard-personalization-card-label'
            )
            . self::badges(
                $presentation['badges']
            ),
            'crm-dashboard-personalization-card-titleline'
        );

        $identity =
            html_writer::span(
                s($item->icon),
                'crm-dashboard-personalization-card-icon',
                [
                    'aria-hidden' => 'true',
                ]
            )
            . html_writer::div(
                $titleline
                . html_writer::div(
                    s($item->description),
                    'crm-dashboard-personalization-card-description'
                ),
                'crm-dashboard-personalization-card-identity'
            );

        $classes = [
            'crm-dashboard-personalization-card',
            'crm-workspace-personalization-item',
            'crm-dashboard-personalization-card-' .
                $item->type,
        ];

        foreach ($presentation['classes'] as $class) {
            if (
                is_string($class)
                && trim($class) !== ''
            ) {
                $classes[] = trim($class);
            }
        }

        if ($hidden) {
            $classes[] =
                'is-dashboard-item-hidden';
            $classes[] =
                'is-workspace-item-hidden';
        }

        $attributes = [
            'class' => implode(
                ' ',
                array_values(
                    array_unique($classes)
                )
            ),
            'data-card-key' => $item->key,
            'data-workspace-item-type' =>
                $item->type,
            'data-workspace-span' =>
                (string)$item->normalized_span(),
            'data-workspace-movable' =>
                $item->movable ? '1' : '0',
            'data-workspace-hideable' =>
                $item->hideable ? '1' : '0',
        ];

        foreach (
            $presentation['attributes']
            as $name => $value
        ) {
            if (
                is_string($name)
                && $name !== ''
                && is_scalar($value)
            ) {
                $attributes[$name] =
                    (string)$value;
            }
        }

        return html_writer::tag(
            'li',
            html_writer::span(
                '•',
                'crm-dashboard-personalization-item-marker',
                [
                    'aria-hidden' => 'true',
                ]
            )
            . $checkbox
            . $identity,
            $attributes
        );
    }

    /**
     * Renders item metadata badges.
     *
     * @param array<int, array{
     *     label: string,
     *     kind?: string
     * }> $badges
     */
    private static function badges(array $badges): string {
        $output = '';

        foreach ($badges as $badge) {
            if (
                !is_array($badge)
                || !is_string(
                    $badge['label'] ?? null
                )
                || $badge['label'] === ''
            ) {
                continue;
            }

            $kind =
                is_string($badge['kind'] ?? null)
                    ? preg_replace(
                        '/[^a-z0-9_-]/i',
                        '',
                        $badge['kind']
                    )
                    : 'default';

            if ($kind === '') {
                $kind = 'default';
            }

            $output .= html_writer::span(
                s($badge['label']),
                'crm-dashboard-personalization-badge ' .
                'crm-dashboard-personalization-badge-' .
                $kind
            );
        }

        if ($output === '') {
            return '';
        }

        return html_writer::span(
            $output,
            'crm-dashboard-personalization-card-badges'
        );
    }

    /**
     * Renders panel actions.
     */
    private static function actions(
        WorkspacePersonalizationOptions $options
    ): string {
        $reset = html_writer::tag(
            'button',
            s($options->resetlabel),
            [
                'type' => 'button',
                'class' =>
                    'btn btn-outline-danger',
                'data-action' =>
                    'reset-workspace-personalization',
            ]
        );

        $cancel = html_writer::tag(
            'button',
            get_string('cancel', 'core'),
            [
                'type' => 'button',
                'class' =>
                    'btn btn-outline-secondary',
                'data-action' =>
                    'close-workspace-personalization',
            ]
        );

        $save = html_writer::tag(
            'button',
            get_string('savechanges', 'core'),
            [
                'type' => 'button',
                'class' => 'btn btn-primary',
                'data-action' =>
                    'save-workspace-personalization',
            ]
        );

        return html_writer::div(
            $reset
            . html_writer::div(
                $cancel . $save,
                'crm-dashboard-personalization-actions-primary'
            ),
            'crm-dashboard-personalization-actions'
        );
    }

    /**
     * Converts a stable Workspace key into an HTML-safe identifier.
     */
    private static function html_id(string $value): string {
        $value = preg_replace(
            '/[^a-z0-9_-]+/i',
            '-',
            $value
        );

        return trim(
            (string)$value,
            '-'
        );
    }
}