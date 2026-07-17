<?php

namespace local_subscriptions\crm\success\plans\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\crm\success\plans\repositories\CustomerSuccessPlanOperationsRepository;

/**
 * Dashboard summary card for Customer Success plans.
 */
final class CustomerSuccessPlanDashboardCard {

    public function __construct(
        private readonly CustomerSuccessPlanOperationsRepository $operations =
            new CustomerSuccessPlanOperationsRepository()
    ) {
    }

    public function render(): string {
        $metrics =
            $this->operations
                ->get_dashboard_metrics();

        $items = [
            'openplans' =>
                get_string(
                    'csplandashboard_open',
                    'local_subscriptions'
                ),

            'activeplans' =>
                get_string(
                    'csplandashboard_active',
                    'local_subscriptions'
                ),

            'blockedsteps' =>
                get_string(
                    'csplandashboard_blocked',
                    'local_subscriptions'
                ),

            'criticalopen' =>
                get_string(
                    'csplandashboard_critical',
                    'local_subscriptions'
                ),

            'completedtoday' =>
                get_string(
                    'csplandashboard_completedtoday',
                    'local_subscriptions'
                ),
        ];

        $content = html_writer::tag(
            'h3',
            get_string(
                'csplandashboard_title',
                'local_subscriptions'
            )
        );

        $content .= html_writer::start_div(
            'local-subscriptions-cs-dashboard-grid'
        );

        foreach ($items as $key => $label) {
            $content .= html_writer::div(
                html_writer::div(
                    (string)$metrics[$key],
                    'local-subscriptions-cs-dashboard-value'
                ) .
                html_writer::div(
                    s($label),
                    'local-subscriptions-cs-dashboard-label'
                ),
                'local-subscriptions-cs-dashboard-item'
            );
        }

        $content .= html_writer::end_div();

        $content .= html_writer::div(
            get_string(
                'csplandashboard_averageprogress',
                'local_subscriptions',
                format_float(
                    (float)$metrics['averageprogress'],
                    0
                )
            ),
            'small text-muted mt-3'
        );

        return html_writer::div(
            $content,
            'local-subscriptions-cs-dashboard-card card'
        );
    }
}