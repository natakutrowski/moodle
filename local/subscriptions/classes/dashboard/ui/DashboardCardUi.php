<?php

namespace local_subscriptions\dashboard\ui;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use moodle_url;

/**
 * Shared presentation primitives for CRM Dashboard cards.
 *
 * This class only produces HTML. It must never access repositories,
 * services, Moodle preferences or the database.
 */
final class DashboardCardUi {

    public const TONE_NEUTRAL = 'neutral';
    public const TONE_SUCCESS = 'success';
    public const TONE_WARNING = 'warning';
    public const TONE_DANGER = 'danger';
    public const TONE_INFO = 'info';

    /**
     * Renders a complete Dashboard Card shell.
     */
    public static function shell(
        string $content,
        string $extraclasses = '',
        ?string $labelledby = null,
        string $tag = 'section'
    ): string {
        $attributes = [
            'class' => self::classes(
                'card card-body ' .
                'local-subscriptions-dashboard-card ' .
                'crm-dashboard-panel',
                $extraclasses
            ),
        ];

        if (
            $labelledby !== null &&
            trim($labelledby) !== ''
        ) {
            $attributes['aria-labelledby'] =
                trim($labelledby);
        }

        return html_writer::tag(
            $tag,
            $content,
            $attributes
        );
    }

    /**
     * Renders a common Card header.
     */
    public static function header(
        string $title,
        string $icon = '',
        string $subtitle = '',
        string $actions = '',
        ?string $titleid = null,
        string $headingtag = 'h3'
    ): string {
        $titlecontent = '';

        if ($icon !== '') {
            $titlecontent .= html_writer::span(
                s($icon),
                'crm-dashboard-panel-icon',
                [
                    'aria-hidden' => 'true',
                ]
            );
        }

        $titlecontent .= html_writer::span(
            s($title),
            'crm-dashboard-panel-title-text'
        );

        $titleattributes = [
            'class' =>
                'crm-dashboard-panel-title h5 mb-0',
        ];

        if (
            $titleid !== null &&
            trim($titleid) !== ''
        ) {
            $titleattributes['id'] =
                trim($titleid);
        }

        $heading = html_writer::tag(
            self::heading_tag($headingtag),
            $titlecontent,
            $titleattributes
        );

        if ($subtitle !== '') {
            $heading .= html_writer::div(
                s($subtitle),
                'crm-dashboard-panel-subtitle'
            );
        }

        $content =
            html_writer::div(
                $heading,
                'crm-dashboard-panel-heading'
            );

        if ($actions !== '') {
            $content .= html_writer::div(
                $actions,
                'crm-dashboard-panel-actions'
            );
        }

        return html_writer::tag(
            'header',
            $content,
            [
                'class' =>
                    'crm-dashboard-panel-header',
            ]
        );
    }

    /**
     * Renders a standard Dashboard action link.
     */
    public static function action(
        moodle_url $url,
        string $label,
        string $style = 'primary',
        string $extraclasses = ''
    ): string {
        $allowedstyles = [
            'primary',
            'secondary',
            'success',
            'warning',
            'danger',
        ];

        if (!in_array(
            $style,
            $allowedstyles,
            true
        )) {
            $style = 'primary';
        }

        return html_writer::link(
            $url,
            s($label),
            [
                'class' => self::classes(
                    'btn btn-sm ' .
                    'btn-outline-' .
                    $style .
                    ' crm-dashboard-panel-action',
                    $extraclasses
                ),
            ]
        );
    }

    /**
     * Renders a menu of secondary actions.
     *
     * The HTML passed here must already be escaped and generated using
     * Moodle output helpers.
     */
    public static function actions(
        array $actions
    ): string {
        $actions = array_values(
            array_filter(
                $actions,
                static fn(mixed $action): bool =>
                    is_string($action) &&
                    trim($action) !== ''
            )
        );

        if ($actions === []) {
            return '';
        }

        return html_writer::div(
            implode('', $actions),
            'crm-dashboard-action-list'
        );
    }

    /**
     * Renders a common empty state.
     */
    public static function empty_state(
        string $title,
        string $description = '',
        string $icon = '✓',
        string $tone = self::TONE_NEUTRAL,
        string $action = ''
    ): string {
        return self::state(
            type: 'empty',
            title: $title,
            description: $description,
            icon: $icon,
            tone: $tone,
            action: $action
        );
    }

    /**
     * Renders a common loading state.
     */
    public static function loading_state(
        string $title,
        string $description = ''
    ): string {
        return self::state(
            type: 'loading',
            title: $title,
            description: $description,
            icon: '…',
            tone: self::TONE_INFO
        );
    }

    /**
     * Renders a common error state.
     */
    public static function error_state(
        string $title,
        string $description = '',
        string $action = ''
    ): string {
        return self::state(
            type: 'error',
            title: $title,
            description: $description,
            icon: '!',
            tone: self::TONE_DANGER,
            action: $action
        );
    }

    /**
     * Renders a common informational state.
     */
    public static function info_state(
        string $title,
        string $description = '',
        string $icon = 'ℹ'
    ): string {
        return self::state(
            type: 'info',
            title: $title,
            description: $description,
            icon: $icon,
            tone: self::TONE_INFO
        );
    }

    /**
     * Renders a shared Card footer.
     */
    public static function footer(
        string $content,
        string $extraclasses = ''
    ): string {
        if (trim($content) === '') {
            return '';
        }

        return html_writer::tag(
            'footer',
            $content,
            [
                'class' => self::classes(
                    'crm-dashboard-panel-footer',
                    $extraclasses
                ),
            ]
        );
    }

    /**
     * Renders a standard metadata badge.
     */
    public static function badge(
        string $label,
        string $tone = self::TONE_NEUTRAL
    ): string {
        return html_writer::span(
            s($label),
            self::classes(
                'badge crm-dashboard-badge',
                'crm-dashboard-badge-' .
                    self::tone($tone)
            )
        );
    }

    /**
     * Renders a standard Dashboard item container.
     */
    public static function item(
        string $content,
        string $extraclasses = ''
    ): string {
        return html_writer::div(
            $content,
            self::classes(
                'crm-dashboard-item',
                $extraclasses
            )
        );
    }

    /**
     * Renders a common state component.
     */
    private static function state(
        string $type,
        string $title,
        string $description,
        string $icon,
        string $tone,
        string $action = ''
    ): string {
        $content = html_writer::div(
            s($icon),
            'crm-dashboard-state-icon',
            [
                'aria-hidden' => 'true',
            ]
        );

        $content .= html_writer::div(
            s($title),
            'crm-dashboard-state-title'
        );

        if ($description !== '') {
            $content .= html_writer::div(
                s($description),
                'crm-dashboard-state-description'
            );
        }

        if ($action !== '') {
            $content .= html_writer::div(
                $action,
                'crm-dashboard-state-action'
            );
        }

        $attributes = [
            'class' => self::classes(
                'crm-dashboard-state',
                'crm-dashboard-state-' .
                    clean_param(
                        $type,
                        PARAM_ALPHANUMEXT
                    ),
                'crm-dashboard-state-' .
                    self::tone($tone)
            ),
        ];

        if ($type === 'loading') {
            $attributes['aria-live'] = 'polite';
            $attributes['aria-busy'] = 'true';
        }

        if ($type === 'error') {
            $attributes['role'] = 'alert';
        }

        return html_writer::div(
            $content,
            $attributes['class'],
            array_diff_key(
                $attributes,
                [
                    'class' => true,
                ]
            )
        );
    }

    /**
     * Normalizes a visual tone.
     */
    private static function tone(
        string $tone
    ): string {
        $allowedtones = [
            self::TONE_NEUTRAL,
            self::TONE_SUCCESS,
            self::TONE_WARNING,
            self::TONE_DANGER,
            self::TONE_INFO,
        ];

        return in_array(
            $tone,
            $allowedtones,
            true
        )
            ? $tone
            : self::TONE_NEUTRAL;
    }

    /**
     * Restricts heading tags to semantic headings.
     */
    private static function heading_tag(
        string $tag
    ): string {
        return in_array(
            $tag,
            [
                'h2',
                'h3',
                'h4',
            ],
            true
        )
            ? $tag
            : 'h3';
    }

    /**
     * Joins CSS classes while removing duplicates.
     */
    private static function classes(
        string ...$classes
    ): string {
        $items = [];

        foreach ($classes as $classlist) {
            foreach (
                preg_split(
                    '/\s+/',
                    trim($classlist)
                ) ?: []
                as $class
            ) {
                if ($class !== '') {
                    $items[$class] = true;
                }
            }
        }

        return implode(
            ' ',
            array_keys($items)
        );
    }
}