<?php

namespace local_subscriptions\dashboard\cards;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\admin\AdminEntityLinks;
use local_subscriptions\admin\AdminEventPresentation;
use local_subscriptions\admin\AdminFormatter;
use local_subscriptions\dashboard\DashboardCard;
use local_subscriptions\dashboard\services\DashboardActivityService;
use local_subscriptions\dashboard\ui\DashboardCardUi;

final class ActivityCard implements DashboardCard {

    public static function render(): string {
        $items =
            DashboardActivityService::load(6);

        $content = DashboardCardUi::header(
            title: get_string(
                'dashboard_recent_activity',
                'local_subscriptions'
            ),
            icon: '🕒',
            titleid:
                'crm-dashboard-activity-title'
        );

        if (!$items) {
            $content .=
                DashboardCardUi::empty_state(
                    title: get_string(
                        'dashboard_no_recent_activity',
                        'local_subscriptions'
                    ),
                    icon: '✓',
                    tone:
                        DashboardCardUi::TONE_SUCCESS
                );

            return DashboardCardUi::shell(
                content: $content,
                extraclasses:
                    'crm-dashboard-activity-card',
                labelledby:
                    'crm-dashboard-activity-title'
            );
        }

        foreach ($items as $item) {
            $content .=
                self::render_item($item);
        }

        return DashboardCardUi::shell(
            content: $content,
            extraclasses:
                'crm-dashboard-activity-card',
            labelledby:
                'crm-dashboard-activity-title'
        );
    }

    private static function render_item(
        \stdClass $item
    ): string {
        $action = trim(
            (string)($item->action ?? '')
        );

        $category =
            AdminEventPresentation::category(
                $action
            );

        $importance =
            AdminEventPresentation::importance(
                $action
            );

        $details =
            AdminEventPresentation::details(
                $item
            );

        $description =
            AdminEventPresentation::description(
                $action,
                $details
            );

        $eventurl =
            AdminEventPresentation::url($item);

        $title = html_writer::div(
            html_writer::span(
                AdminEventPresentation::icon(
                    $action
                ),
                'dashboard-activity-icon',
                [
                    'aria-hidden' => 'true',
                ]
            ) .
            html_writer::span(
                s(
                    AdminEventPresentation::label(
                        $action
                    )
                ),
                'dashboard-activity-title'
            ),
            'dashboard-activity-title-row'
        );

        $actor =
            self::actor_label($item);

        $target =
            self::target_label($item);

        $row = $title;

        $row .= html_writer::div(
            $target !== '' ? $target : '—',
            'dashboard-activity-target'
        );

        $row .= html_writer::div(
            $actor !== '' ? s($actor) : '—',
            'dashboard-activity-actor'
        );

        $row .= html_writer::div(
            AdminFormatter::datetime(
                (int)$item->timecreated
            ),
            'dashboard-activity-time',
            [
                'title' => get_string(
                    'dashboard_activity_exact_date',
                    'local_subscriptions',
                    userdate(
                        (int)$item->timecreated,
                        get_string(
                            'strftimedatetimeshort',
                            'langconfig'
                        )
                    )
                ),
            ]
        );

        if ($eventurl !== null) {
            $row .= html_writer::div(
                html_writer::link(
                    $eventurl,
                    get_string(
                        'dashboard_activity_open',
                        'local_subscriptions'
                    ),
                    [
                        'class' =>
                            'dashboard-activity-open-link',
                    ]
                ),
                'dashboard-activity-actions'
            );
        } else {
            $row .= html_writer::div(
                '',
                'dashboard-activity-actions'
            );
        }

        $body = html_writer::div(
            $row,
            'dashboard-activity-row'
        );

        if ($description !== '') {
            $body .= html_writer::div(
                s($description),
                'dashboard-activity-description'
            );
        }

        return DashboardCardUi::item(
            $body,
            'dashboard-activity-item ' .
                'dashboard-activity-' .
                $category .
                ' dashboard-activity-importance-' .
                $importance
        );
    }

    private static function actor_label(
        \stdClass $item
    ): string {
        $actorid = (int)(
            $item->actorid ?? 0
        );

        $name = trim(
            (string)($item->firstname ?? '') .
            ' ' .
            (string)($item->lastname ?? '')
        );

        if (
            $actorid <= 0 ||
            $name === ''
        ) {
            return get_string(
                'dashboard_activity_system_actor',
                'local_subscriptions'
            );
        }

        return get_string(
            'dashboard_activity_actor',
            'local_subscriptions',
            $name
        );
    }

    private static function target_label(
        \stdClass $item
    ): string {
        $targetuserid = (int)(
            $item->targetuserid ?? 0
        );

        if ($targetuserid <= 0) {
            return '';
        }

        $targetname = trim(
            (string)(
                $item->targetfirstname ?? ''
            ) .
            ' ' .
            (string)(
                $item->targetlastname ?? ''
            )
        );

        $targetlabel =
            $targetname !== ''
                ? $targetname
                : trim(
                    (string)(
                        $item->targetemail ?? ''
                    )
                );

        if ($targetlabel === '') {
            $targetlabel =
                '#' . $targetuserid;
        }

        $link = AdminEntityLinks::user(
            $targetuserid,
            s($targetlabel)
        );

        return get_string(
            'dashboard_activity_target',
            'local_subscriptions',
            $link
        );
    }
}