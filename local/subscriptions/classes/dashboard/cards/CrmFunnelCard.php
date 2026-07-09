<?php

namespace local_subscriptions\dashboard\cards;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\crm\intelligence\reports\CrmFunnelRepository;
use local_subscriptions\dashboard\DashboardCard;

final class CrmFunnelCard implements DashboardCard {

    public static function render(): string {
        $report = (new CrmFunnelRepository())->build_report();

        $out = html_writer::tag('h3', '📊 ' . get_string('crm_funnel_title', 'local_subscriptions'), [
            'class' => 'h4 mb-3',
        ]);

        $items = [
            get_string('crm_funnel_users', 'local_subscriptions') => $report->users,
            get_string('crm_funnel_trials', 'local_subscriptions') => $report->trials,
            get_string('crm_funnel_customers', 'local_subscriptions') => $report->customers,
            get_string('crm_funnel_digital_customers', 'local_subscriptions') => $report->digitalCustomers,
            get_string('crm_funnel_expired_customers', 'local_subscriptions') => $report->expiredCustomers,
            get_string('crm_funnel_trial_conversion_rate', 'local_subscriptions') => $report->trial_conversion_rate() . '%',
        ];

        foreach ($items as $label => $value) {
            $out .= html_writer::div(
                html_writer::span(s($label), 'text-muted') .
                html_writer::span(s((string)$value), 'fw-bold float-end'),
                'border-bottom py-2'
            );
        }

        return html_writer::div($out, 'card card-body local-subscriptions-dashboard-card mb-4');
    }
}