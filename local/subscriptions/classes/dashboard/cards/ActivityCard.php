<?php

namespace local_subscriptions\dashboard\cards;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\dashboard\DashboardCard;
use local_subscriptions\dashboard\services\DashboardActivityService;
use local_subscriptions\admin\AdminFormatter;
use local_subscriptions\admin\AdminEntityLinks;

final class ActivityCard implements DashboardCard {

    public static function render(): string {
        $items = DashboardActivityService::load(10);

        $out = html_writer::start_div('card card-body local-subscriptions-dashboard-card mb-4');
        $out .= html_writer::tag('h3', '🕒 ' . get_string('dashboard_recent_activity', 'local_subscriptions'), [
            'class' => 'h5 mb-3',
        ]);

        if (!$items) {
            $out .= html_writer::div(get_string('dashboard_no_recent_activity', 'local_subscriptions'), 'text-muted small');
            $out .= html_writer::end_div();
            return $out;
        }

        foreach ($items as $item) {
            $target = '-';

            if (!empty($item->targetuserid)) {
                $targetname = trim(($item->targetfirstname ?? '') . ' ' . ($item->targetlastname ?? ''));
                $targetlabel = $targetname !== '' ? s($targetname) : s($item->targetemail ?? ('#' . $item->targetuserid));

                $target = AdminEntityLinks::user((int)$item->targetuserid, $targetlabel);
            }

            $out .= html_writer::div(
                html_writer::div(
                    self::icon_for_action((string)$item->action),
                    'dashboard-activity-icon'
                ) .
                html_writer::div(
                    html_writer::div(
                        html_writer::tag('strong', self::label_for_action((string)$item->action)) .
                        html_writer::span(AdminFormatter::datetime((int)$item->timecreated), 'dashboard-activity-time')
                    ) .
                    html_writer::div($target, 'small text-muted'),
                    'dashboard-activity-content'
                ),
                'dashboard-activity-item'
            );
        }

        $out .= html_writer::end_div();

        return $out;
    }

    private static function label_for_action(string $action): string {
        $key = 'adminlog_' . str_replace('.', '_', $action);

        if (get_string_manager()->string_exists($key, 'local_subscriptions')) {
            return get_string($key, 'local_subscriptions');
        }

        return s($action);
    }

    private static function icon_for_action(string $action): string {
        if (str_contains($action, 'email')) {
            return '✉️';
        }

        if (str_contains($action, 'password')) {
            return '🔑';
        }

        if (str_contains($action, 'subscription')) {
            return '📚';
        }

        if (str_contains($action, 'digital')) {
            return '📦';
        }

        if (str_contains($action, 'note')) {
            return '📝';
        }

        return '⚙️';
    }
}