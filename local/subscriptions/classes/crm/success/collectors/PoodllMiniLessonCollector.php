<?php

namespace local_subscriptions\crm\success\collectors;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\collection\SuccessMetric;
use local_subscriptions\crm\success\collection\SuccessMetricCollection;
use local_subscriptions\crm\success\contracts\SuccessCollectorInterface;
use local_subscriptions\crm\success\domain\SuccessMetricSource;
use local_subscriptions\crm\success\repositories\PoodllLearningRepository;

final class PoodllMiniLessonCollector implements
    SuccessCollectorInterface {

    public function __construct(
        private readonly PoodllLearningRepository $repository =
            new PoodllLearningRepository()
    ) {
    }

    public function key(): string {
        return 'poodll_minilesson';
    }

    public function is_available(): bool {
        return $this->repository->is_module_available(
            'mod_minilesson',
            'minilesson_attempt'
        );
    }

    public function collect(
        int $userid,
        int $measuredat
    ): SuccessMetricCollection {
        return $this->build(
            $userid,
            $measuredat,
            'minilesson',
            $this->repository->get_minilesson_statistics(
                $userid,
                $measuredat
            )
        );
    }

    private function build(
        int $userid,
        int $measuredat,
        string $module,
        array $statistics
    ): SuccessMetricCollection {
        $metrics = new SuccessMetricCollection();

        foreach ($statistics as $key => $value) {
            $metrics->add(
                new SuccessMetric(
                    $userid,
                    'learning.poodll.' .
                        $module .
                        '.' .
                        $key,
                    $value,
                    SuccessMetricSource::POODLL,
                    $measuredat,
                    ['module' => $module]
                )
            );
        }

        return $metrics;
    }
}