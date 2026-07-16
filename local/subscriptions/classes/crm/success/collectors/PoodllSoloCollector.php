<?php

namespace local_subscriptions\crm\success\collectors;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\collection\SuccessMetric;
use local_subscriptions\crm\success\collection\SuccessMetricCollection;
use local_subscriptions\crm\success\contracts\SuccessCollectorInterface;
use local_subscriptions\crm\success\domain\SuccessMetricSource;
use local_subscriptions\crm\success\repositories\PoodllLearningRepository;

final class PoodllSoloCollector implements
    SuccessCollectorInterface {

    public function __construct(
        private readonly PoodllLearningRepository $repository =
            new PoodllLearningRepository()
    ) {
    }

    public function key(): string {
        return 'poodll_solo';
    }

    public function is_available(): bool {
        return
            $this->repository->is_module_available(
                'mod_solo',
                'solo_attempts'
            ) &&
            $this->repository->is_module_available(
                'mod_solo',
                'solo_attemptstats'
            );
    }

    public function collect(
        int $userid,
        int $measuredat
    ): SuccessMetricCollection {
        $statistics =
            $this->repository->get_solo_statistics(
                $userid,
                $measuredat
            );

        $metrics = new SuccessMetricCollection();

        foreach ($statistics as $key => $value) {
            $metrics->add(
                new SuccessMetric(
                    $userid,
                    'learning.poodll.solo.' . $key,
                    $value,
                    SuccessMetricSource::POODLL,
                    $measuredat,
                    ['module' => 'solo']
                )
            );
        }

        return $metrics;
    }
}