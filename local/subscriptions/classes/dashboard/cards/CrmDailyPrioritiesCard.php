<?php

namespace local_subscriptions\dashboard\cards;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\crm\intelligence\priority\DailyPriorityBuilder;
use local_subscriptions\dashboard\DashboardCard;
use local_subscriptions\subscription_config;
use local_subscriptions\admin\Capabilities;
use moodle_url;

final class CrmDailyPrioritiesCard implements DashboardCard {

    public static function render(): string {

        if (!Capabilities::can_view_users()) {
            return '';
        }

        $priorities = (new DailyPriorityBuilder())->build();

        $out = html_writer::tag('h3', '⭐ ' . get_string('crm_daily_priorities_title', 'local_subscriptions'), [
            'class' => 'h4 mb-3',
        ]);

        if (empty($priorities)) {
            $out .= html_writer::div(
                get_string('crm_daily_priorities_empty', 'local_subscriptions'),
                'text-muted'
            );

            return html_writer::div($out, 'card card-body local-subscriptions-dashboard-card mb-4');
        }

        foreach ($priorities as $priority) {
            $key =
                'crm_intelligence_recommendation_' .
                clean_param(
                    $priority->key,
                    PARAM_ALPHANUMEXT
                );

            $label =
                get_string_manager()->string_exists(
                    $key,
                    'local_subscriptions'
                )
                    ? get_string(
                        $key,
                        'local_subscriptions'
                    )
                    : get_string(
                        'crm_daily_priorities_item_fallback',
                        'local_subscriptions'
                    );

            $url = new moodle_url(
                subscription_config::
                    admin_user_view_page(),
                [
                    'id' => $priority->userid,
                ]
            );

            $out .= html_writer::div(
                html_writer::div(
                    html_writer::link(
                        $url,
                        s($priority->displayname),
                        [
                            'class' => 'fw-bold',
                        ]
                    ) .
                    html_writer::span(
                        (string)$priority->score,
                        'badge bg-light text-dark border float-end'
                    )
                ) .
                html_writer::div(
                    s($label),
                    'text-muted small mt-1'
                ),
                'border rounded p-2 mb-2'
            );
        }

        return html_writer::div($out, 'card card-body local-subscriptions-dashboard-card mb-4');
    }
}