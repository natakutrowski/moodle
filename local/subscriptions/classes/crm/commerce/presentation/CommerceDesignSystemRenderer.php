<?php

declare(strict_types=1);

namespace local_subscriptions\crm\commerce\presentation;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use moodle_url;

/** Shared, accessible Commerce UI building blocks. */
final class CommerceDesignSystemRenderer {
    /**
     * @param array<int, array{label:string, url:moodle_url, class?:string}> $actions
     */
    public static function action_bar(array $actions, string $class = ''): string {
        $links = [];
        foreach ($actions as $index => $action) {
            $buttonclass = trim(($action['class'] ?? 'btn btn-outline-secondary') . ' mb-2');
            if ($index < count($actions) - 1) {
                $buttonclass .= ' me-2';
            }
            $label = s($action['label']);
            if (!empty($action['icon'])) {
                $label = html_writer::tag('i', '', [
                    'class' => 'fa ' . clean_param(
                        (string)$action['icon'],
                        PARAM_TEXT
                    ) . ' me-1',
                    'aria-hidden' => 'true',
                ]) . $label;
            }
            $links[] = html_writer::link(
                $action['url'],
                $label,
                ['class' => $buttonclass]
            );
        }

        return html_writer::div(
            implode('', $links),
            trim('crm-commerce-actionbar d-flex flex-wrap align-items-center ' . $class)
        );
    }

    public static function filter_panel(string $content): string {
        return html_writer::div($content, 'card card-body crm-commerce-filter-card mb-4');
    }

    public static function empty_state(
        string $title,
        string $description,
        ?moodle_url $actionurl = null,
        ?string $actionlabel = null
    ): string {
        $content = html_writer::tag('h3', s($title), ['class' => 'h5 mb-2']) .
            html_writer::tag('p', s($description), ['class' => 'text-muted mb-0']);

        if ($actionurl !== null && $actionlabel !== null && trim($actionlabel) !== '') {
            $content .= html_writer::div(
                html_writer::link($actionurl, s($actionlabel), ['class' => 'btn btn-primary']),
                'mt-3'
            );
        }

        return html_writer::tag('section', $content, [
            'class' => 'crm-commerce-empty-state',
            'aria-live' => 'polite',
        ]);
    }

    /**
     * @param array<int, array{label:string, value:string|int}> $metrics
     */
    public static function metrics(array $metrics): string {
        $items = [];
        foreach ($metrics as $metric) {
            $items[] = html_writer::div(
                html_writer::div(s((string) $metric['value']), 'crm-commerce-metric-value') .
                html_writer::div(s($metric['label']), 'crm-commerce-metric-label'),
                'crm-commerce-metric'
            );
        }

        return html_writer::div(implode('', $items), 'crm-commerce-metrics');
    }

    public static function page_intro(string $description): string {
        return html_writer::tag('p', s($description), ['class' => 'text-muted mb-0']);
    }

    public static function notice(string $title, string $description, string $type = 'info'): string {
        $allowed = ['info', 'success', 'warning', 'danger'];
        if (!in_array($type, $allowed, true)) {
            $type = 'info';
        }

        return html_writer::tag(
            'section',
            html_writer::tag('h3', s($title), ['class' => 'h5 mb-2']) .
                html_writer::tag('p', s($description), ['class' => 'mb-0']),
            [
                'class' => 'alert alert-' . $type . ' crm-commerce-notice',
                'role' => $type === 'danger' ? 'alert' : 'status',
            ]
        );
    }

    public static function form_actions(string $primaryhtml, string $secondaryhtml = ''): string {
        return html_writer::div(
            $primaryhtml . $secondaryhtml,
            'crm-commerce-form-actions'
        );
    }

    public static function section_heading(string $title, ?string $description = null): string {
        $html = html_writer::tag('h2', s($title), ['class' => 'h4 mb-1']);
        if ($description !== null && trim($description) !== '') {
            $html .= html_writer::tag('p', s($description), ['class' => 'text-muted mb-0']);
        }
        return html_writer::div($html, 'crm-commerce-section-heading');
    }

    public static function panel(string $title, string $content, string $class = ''): string {
        return html_writer::tag(
            'section',
            html_writer::tag('h3', s($title), ['class' => 'h5']) . $content,
            ['class' => trim('card card-body crm-commerce-info-panel ' . $class)]
        );
    }
}
