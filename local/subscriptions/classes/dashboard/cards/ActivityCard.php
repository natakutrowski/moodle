<?php

namespace local_subscriptions\dashboard\cards;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\dashboard\DashboardCard;
use local_subscriptions\dashboard\services\DashboardActivityService;
use local_subscriptions\admin\AdminFormatter;
use local_subscriptions\admin\AdminEntityLinks;
use local_subscriptions\admin\AdminEventPresentation;

final class ActivityCard implements DashboardCard {

    public static function render(): string {
        $items = DashboardActivityService::load(10);

        $out = html_writer::start_div(
            'card card-body local-subscriptions-dashboard-card mb-4'
        );

        $out .= html_writer::tag(
            'h3',
            '🕒 ' . get_string(
                'dashboard_recent_activity',
                'local_subscriptions'
            ),
            [
                'class' => 'h5 mb-3',
            ]
        );

        if (!$items) {
            $out .= html_writer::div(
                get_string(
                    'dashboard_no_recent_activity',
                    'local_subscriptions'
                ),
                'text-muted small'
            );

            $out .= html_writer::end_div();

            return $out;
        }

        foreach ($items as $item) {
            $action = trim((string)($item->action ?? ''));

            $target = '-';

            if (!empty($item->targetuserid)) {
                $targetname = trim(
                    (string)($item->targetfirstname ?? '') .
                    ' ' .
                    (string)($item->targetlastname ?? '')
                );

                $targetlabel = $targetname !== ''
                    ? $targetname
                    : (
                        !empty($item->targetemail)
                            ? (string)$item->targetemail
                            : '#' . (int)$item->targetuserid
                    );

                $target = AdminEntityLinks::user(
                    (int)$item->targetuserid,
                    s($targetlabel)
                );
            }

            $category = AdminEventPresentation::category($action);

            $out .= html_writer::start_div(
                'dashboard-activity-item dashboard-activity-' . $category
            );

            $out .= html_writer::div(
                AdminEventPresentation::icon($action),
                'dashboard-activity-icon'
            );

            $out .= html_writer::start_div(
                'dashboard-activity-content'
            );

            $out .= html_writer::start_div(
                'dashboard-activity-header'
            );

            $out .= html_writer::tag(
                'strong',
                s(AdminEventPresentation::label($action))
            );

            $out .= html_writer::span(
                AdminFormatter::datetime(
                    (int)$item->timecreated
                ),
                'dashboard-activity-time'
            );

            $out .= html_writer::end_div();

            $out .= html_writer::div(
                $target,
                'small text-muted dashboard-activity-target'
            );

            $out .= html_writer::end_div();
            $out .= html_writer::end_div();
        }

        $out .= html_writer::end_div();

        return $out;
    }
}