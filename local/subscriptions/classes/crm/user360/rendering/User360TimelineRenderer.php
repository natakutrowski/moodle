<?php

declare(strict_types=1);

namespace local_subscriptions\crm\user360\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\output\UserProfileRenderer;

/**
 * N11.7C — Full-width advanced Timeline.
 *
 * The Advanced pane owns the screen title. This renderer only adds compact
 * timeline metrics plus the filtering/event engine, avoiding nested headings.
 */
final class User360TimelineRenderer {

    public static function render(\stdClass $profile): string {
        $events = $profile->timeline ?? [];
        $important = 0;

        foreach ($events as $event) {
            if (in_array(
                strtolower((string)($event->importance ?? 'normal')),
                ['medium', 'high'],
                true
            )) {
                $important++;
            }
        }

        $metrics = html_writer::div(
            self::metric(
                (string)count($events),
                get_string(
                    'crm_user360_n113d_timeline_events',
                    'local_subscriptions'
                )
            )
            . self::metric(
                (string)$important,
                get_string(
                    'crm_user360_n113d_timeline_important',
                    'local_subscriptions'
                )
            ),
            'crm-user360-n117c-timeline-metrics'
        );

        return html_writer::tag(
            'section',
            html_writer::div(
                $metrics,
                'crm-user360-n117c-timeline-summary'
            )
            . html_writer::div(
                UserProfileRenderer::render_timeline_content($profile),
                'crm-user360-n117c-timeline-body'
            ),
            [
                'id' => 'crm-user-timeline',
                'class' =>
                    'crm-user360-n113d-timeline '
                    . 'crm-user360-n117c-timeline',
                'aria-label' => get_string(
                    'crm_user360_n113d_timeline_title',
                    'local_subscriptions'
                ),
            ]
        );
    }

    private static function metric(
        string $value,
        string $label
    ): string {
        return html_writer::div(
            html_writer::tag(
                'strong',
                s($value),
                [
                    'class' =>
                        'crm-user360-n117c-timeline-metric-value',
                ]
            )
            . html_writer::span(
                s($label),
                'crm-user360-n117c-timeline-metric-label'
            ),
            'crm-user360-n117c-timeline-metric'
        );
    }
}
