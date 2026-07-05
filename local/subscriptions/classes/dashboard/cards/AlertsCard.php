<?php

namespace local_subscriptions\dashboard\cards;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use moodle_url;
use local_subscriptions\subscription_config;
use local_subscriptions\dashboard\DashboardCard;
use local_subscriptions\dashboard\services\DashboardAlertService;

final class AlertsCard implements DashboardCard {

    public static function render(): string {
        $alerts = DashboardAlertService::load();

        $items = [
            [
                'label' => get_string('dashboard_alert_pending_digital', 'local_subscriptions'),
                'count' => $alerts->pendingdigital,
                'url' => new moodle_url(subscription_config::digital_purchases_admin_page(), ['status' => 'pending']),
            ],
            [
                'label' => get_string('dashboard_alert_failed_digital', 'local_subscriptions'),
                'count' => $alerts->faileddigital,
                'url' => new moodle_url(subscription_config::digital_purchases_admin_page(), ['status' => 'failed']),
            ],
            [
                'label' => get_string('dashboard_alert_email_errors', 'local_subscriptions'),
                'count' => $alerts->emailerrors,
                'url' => new moodle_url(subscription_config::digital_purchases_admin_page()),
            ],
            [
                'label' => get_string('dashboard_alert_expired_tokens', 'local_subscriptions'),
                'count' => $alerts->expiredtokens,
                'url' => new moodle_url(subscription_config::digital_purchases_admin_page()),
            ],
        ];

        $out = html_writer::start_div('card card-body local-subscriptions-dashboard-card mb-4');
        $out .= html_writer::tag('h3', '⚠️ ' . get_string('dashboard_alerts', 'local_subscriptions'), [
            'class' => 'h5 mb-3',
        ]);

        foreach ($items as $item) {
            $class = $item['count'] > 0 ? 'dashboard-alert-item has-alert' : 'dashboard-alert-item';

            $out .= html_writer::link(
                $item['url'],
                html_writer::span((string)$item['count'], 'dashboard-alert-count') .
                html_writer::span($item['label']),
                ['class' => $class]
            );
        }

        $out .= html_writer::end_div();

        return $out;
    }
}