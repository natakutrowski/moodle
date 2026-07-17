<?php

namespace local_subscriptions\crm\success\plans\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\plans\repositories\CustomerSuccessPlanReadRepository;
use local_subscriptions\crm\success\plans\rendering\CustomerSuccessPlanPresentation;
use local_subscriptions\subscription_config;
use moodle_url;

/**
 * Builds normalized timeline entries for Customer Success plans.
 */
final class CustomerSuccessPlanTimelineService {

    public function __construct(
        private readonly CustomerSuccessPlanReadRepository $plans =
            new CustomerSuccessPlanReadRepository()
    ) {
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function get_for_user(
        int $userid
    ): array {
        $plans =
            $this->plans->get_for_user(
                $userid
            );

        $events = [];

        foreach ($plans as $plan) {
            $url = new moodle_url(
                subscription_config::
                    admin_customer_success_plan_page(),
                [
                    'id' => $plan->id,
                ]
            );

            $plantitle =
                CustomerSuccessPlanPresentation::title(
                    $plan->objectivekey,
                    $plan->title
                );            

            $events[] = [
                'type' => 'customer_success_plan',
                'subtype' => 'created',
                'timestamp' => $plan->timecreated,
                'title' => $plantitle,
                'description' =>
                    get_string(
                        'csplantimelinecreated',
                        'local_subscriptions'
                    ),
                'url' => $url,
                'objectid' => $plan->id,
            ];

            if ($plan->activatedat !== null) {
                $events[] = [
                    'type' => 'customer_success_plan',
                    'subtype' => 'activated',
                    'timestamp' => $plan->activatedat,
                    'title' => $plantitle,
                    'description' =>
                        get_string(
                            'csplantimelineactivated',
                            'local_subscriptions'
                        ),
                    'url' => $url,
                    'objectid' => $plan->id,
                ];
            }

            foreach ($plan->steps as $step) {
                if ($step->completedat === null) {
                    continue;
                }

                $events[] = [
                    'type' =>
                        'customer_success_plan_step',
                    'subtype' => $step->status,
                    'timestamp' =>
                        $step->completedat,
                    'title' => $step->title,
                    'description' =>
                        get_string(
                            'csplantimelinestepcompleted',
                            'local_subscriptions'
                        ),
                    'url' => $url,
                    'objectid' => $step->id,
                ];
            }

            if ($plan->completedat !== null) {
                $events[] = [
                    'type' => 'customer_success_plan',
                    'subtype' => 'completed',
                    'timestamp' => $plan->completedat,
                    'title' => $plantitle,
                    'description' =>
                        get_string(
                            'csplantimelinecompleted',
                            'local_subscriptions'
                        ),
                    'url' => $url,
                    'objectid' => $plan->id,
                ];
            }
        }

        usort(
            $events,
            static fn(array $left, array $right): int =>
                $right['timestamp'] <=>
                $left['timestamp']
        );

        return $events;
    }
}