<?php

namespace local_subscriptions\dashboard\cards;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\crm\intelligence\alerts\CrmAlertBuilder;
use local_subscriptions\dashboard\DashboardCard;
use local_subscriptions\subscription_config;
use local_subscriptions\admin\Capabilities;
use moodle_url;

final class CrmIntelligenceAlertsCard implements DashboardCard {

    public static function render(): string {

        if (!Capabilities::can_view_users()) {
            return '';
        }

        $alerts = (new CrmAlertBuilder())->build();

        $out = html_writer::tag('h3', '🚨 ' . get_string('crm_intelligence_alerts_title', 'local_subscriptions'), [
            'class' => 'h4 mb-3',
        ]);

        if (empty($alerts)) {
            $out .= html_writer::div(
                get_string('crm_intelligence_alerts_empty', 'local_subscriptions'),
                'text-muted'
            );

            return html_writer::div($out, 'card card-body local-subscriptions-dashboard-card mb-4');
        }

        foreach ($alerts as $alert) {
            $key = 'crm_intelligence_alert_' . clean_param($alert->key, PARAM_ALPHANUMEXT);

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
                        'crm_intelligence_alert_fallback',
                        'local_subscriptions'
                    );

            $classes = [
                'danger' => 'border-danger',
                'warning' => 'border-warning',
                'success' => 'border-success',
                'info' => 'border-info',
            ];

            $class = $classes[$alert->severity] ?? 'border-info';

            $content = html_writer::div(s($label), 'fw-bold');

            if (!empty($alert->userid)) {
                $url = new moodle_url(subscription_config::admin_user_view_page(), ['id' => $alert->userid]);

                $content .= html_writer::link(
                    $url,
                    get_string('crm_intelligence_alert_open_profile', 'local_subscriptions'),
                    ['class' => 'small']
                );
            }

            $out .= html_writer::div($content, 'border rounded p-2 mb-2 ' . $class);
        }

        return html_writer::div($out, 'card card-body local-subscriptions-dashboard-card mb-4');
    }
}