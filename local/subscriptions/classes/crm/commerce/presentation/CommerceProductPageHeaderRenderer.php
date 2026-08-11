<?php

declare(strict_types=1);

namespace local_subscriptions\crm\commerce\presentation;

defined('MOODLE_INTERNAL') || die();

use html_writer;

/** Shared CRM Commerce product page header. */
final class CommerceProductPageHeaderRenderer {
    public static function render(
        string $title,
        string $metahtml = '',
        string $actionshtml = '',
        ?string $eyebrow = null
    ): string {
        $heading = '';
        if ($eyebrow !== null && trim($eyebrow) !== '') {
            $heading .= html_writer::div(s($eyebrow), 'crm-commerce-eyebrow');
        }

        $heading .= html_writer::tag('h1', format_string($title), ['class' => 'h2 mb-0']);
        if ($metahtml !== '') {
            $heading .= html_writer::div($metahtml, 'mt-2');
        }

        $content = html_writer::div($heading, 'flex-grow-1');
        if ($actionshtml !== '') {
            $content .= html_writer::div($actionshtml, 'crm-commerce-page-header-actions');
        }

        return html_writer::tag('header', $content, ['class' => 'crm-commerce-page-header']);
    }
}
