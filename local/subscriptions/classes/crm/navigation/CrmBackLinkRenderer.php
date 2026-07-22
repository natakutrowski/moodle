<?php

namespace local_subscriptions\crm\navigation;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use moodle_url;

/**
 * Renders a consistent CRM back link.
 */
final class CrmBackLinkRenderer {

    /**
     * Renders a standard CRM back link.
     *
     * @param moodle_url $url Destination URL.
     * @param string $label Link label without the arrow.
     * @param string[] $additionalclasses Optional additional CSS classes.
     * @return string
     */
    public static function render(
        moodle_url $url,
        string $label,
        array $additionalclasses = []
    ): string {
        $classes = array_merge(
            [
                'crm-app-back-link',
                'btn',
                'btn-link',
                'ps-0',
                'mb-3',
            ],
            $additionalclasses
        );

        $classes = array_values(
            array_unique(
                array_filter(
                    array_map(
                        static fn(
                            mixed $class
                        ): string => trim(
                            (string)$class
                        ),
                        $classes
                    )
                )
            )
        );

        return html_writer::link(
            $url,
            html_writer::span(
                '←',
                'crm-app-back-link-icon',
                [
                    'aria-hidden' => 'true',
                ]
            ) .
            html_writer::span(
                s($label),
                'crm-app-back-link-label'
            ),
            [
                'class' => implode(
                    ' ',
                    $classes
                ),
            ]
        );
    }
}