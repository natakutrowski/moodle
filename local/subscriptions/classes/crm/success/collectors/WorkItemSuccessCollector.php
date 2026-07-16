<?php

namespace local_subscriptions\crm\success\collectors;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\collection\SuccessMetric;
use local_subscriptions\crm\success\collection\SuccessMetricCollection;
use local_subscriptions\crm\success\contracts\SuccessCollectorInterface;
use local_subscriptions\crm\success\domain\SuccessMetricSource;
use local_subscriptions\crm\success\repositories\WorkItemSuccessRepository;

/**
 * Collects aggregated Work Item metrics for Customer Success.
 */
final class WorkItemSuccessCollector implements
    SuccessCollectorInterface {

    public function __construct(
        private readonly WorkItemSuccessRepository $repository =
            new WorkItemSuccessRepository()
    ) {
    }

    public function key(): string {
        return 'work_items';
    }

    public function is_available(): bool {
        return $this->repository->is_available();
    }

    public function collect(
        int $userid,
        int $measuredat
    ): SuccessMetricCollection {
        $statistics = $this->repository->get_statistics(
            $userid,
            $measuredat
        );

        $metrics = new SuccessMetricCollection();

        foreach ($statistics as $key => $value) {
            $metrics->add(
                new SuccessMetric(
                    $userid,
                    'support.work_items.' . $key,
                    $value,
                    SuccessMetricSource::WORK_ITEMS,
                    $measuredat,
                    [
                        'aggregate' => true,
                        'contains_content' => false,
                        'targeted_user_only' => true,
                    ]
                )
            );
        }

        return $metrics;
    }
}