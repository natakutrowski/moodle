<?php

namespace local_subscriptions\crm\success\plans\rendering;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_subscriptions\crm\success\plans\repositories\CustomerSuccessPlanReadRepository;
use moodle_url;
use local_subscriptions\subscription_config;

/**
 * Customer Success plan section for User 360°.
 */
final class CustomerSuccessPlanUserSection {

    public function __construct(
        private readonly CustomerSuccessPlanReadRepository $plans =
            new CustomerSuccessPlanReadRepository()
    ) {
    }

    public function render(
        int $userid,
        bool $canmanage
    ): string {
        $plans = $this->plans->get_for_user(
            $userid,
            true
        );

        $content = '';

        if ($plans === []) {
            $content .= html_writer::div(
                get_string(
                    'csplannoneforuser',
                    'local_subscriptions'
                ),
                'text-muted'
            );

            if ($canmanage) {
                $content .= self::create_button(
                    $userid,
                    true
                );
            }

            return html_writer::div(
                $content,
                'local-subscriptions-user-cs-plans'
            );
        }

        foreach ($plans as $plan) {
            $url = new moodle_url(
                subscription_config::
                    admin_customer_success_plan_page(),
                [
                    'id' => $plan->id,
                ]
            );

            $summary =
                html_writer::link(
                    $url,
                    s(
                        CustomerSuccessPlanPresentation::
                            title(
                                $plan->objectivekey,
                                $plan->title
                            )
                    ),
                    [
                        'class' =>
                            'font-weight-bold',
                    ]
                ) .
                html_writer::div(
                    get_string(
                        'csplanprogresspercentage',
                        'local_subscriptions',
                        format_float(
                            $plan->progress_percentage(),
                            0
                        )
                    ),
                    'small text-muted'
                );

            if ($plan->is_blocked()) {
                $summary .= html_writer::span(
                    get_string(
                        'csplanblocked',
                        'local_subscriptions'
                    ),
                    'badge badge-warning'
                );
            }

            $content .= html_writer::div(
                $summary,
                'local-subscriptions-user-cs-plans__item'
            );
        }

        if ($canmanage) {
            $content .= self::create_button(
                $userid,
                false
            );
        }

        return html_writer::div(
            $content,
            'local-subscriptions-user-cs-plans'
        );
    }

    private static function create_button(
        int $userid,
        bool $primary
    ): string {
        $url = new moodle_url(
            subscription_config::
                admin_customer_success_plan_create_page(),
            ['userid' => $userid]
        );

        return html_writer::div(
            html_writer::link(
                $url,
                html_writer::tag(
                    'i',
                    '',
                    [
                        'class' => 'fa fa-plus-circle',
                        'aria-hidden' => 'true',
                    ]
                )
                . html_writer::span(
                    get_string(
                        'csplancreate_button_n129c',
                        'local_subscriptions'
                    )
                ),
                [
                    'class' =>
                        'btn btn-sm '
                        . (
                            $primary
                                ? 'btn-primary'
                                : 'btn-outline-primary'
                        ),
                ]
            ),
            'local-subscriptions-user-cs-plans__create'
        );
    }
}