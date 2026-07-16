<?php

namespace local_subscriptions\crm\success\collectors;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\collection\SuccessMetric;
use local_subscriptions\crm\success\collection\SuccessMetricCollection;
use local_subscriptions\crm\success\contracts\SuccessCollectorInterface;
use local_subscriptions\crm\success\domain\SuccessMetricSource;
use local_subscriptions\crm\success\repositories\MoodleActivityRepository;

/**
 * Collects native Moodle activity metrics.
 */
final class MoodleActivityCollector implements
    SuccessCollectorInterface {

    public function __construct(
        private readonly MoodleActivityRepository $repository =
            new MoodleActivityRepository()
    ) {
    }

    public function key(): string {
        return 'moodle_activity';
    }

    public function is_available(): bool {
        return $this->repository->is_available();
    }

    public function collect(
        int $userid,
        int $measuredat
    ): SuccessMetricCollection {
        $lastaccess =
            $this->repository->get_user_last_access($userid);

        $statistics =
            $this->repository->get_activity_statistics(
                $userid,
                $measuredat
            );

        $metrics = new SuccessMetricCollection();

        $metrics->add(
            $this->metric(
                $userid,
                'activity.last_access_at',
                $lastaccess,
                SuccessMetricSource::MOODLE_USER,
                $measuredat
            )
        );

        foreach ($statistics as $key => $value) {
            $metrics->add(
                $this->metric(
                    $userid,
                    'activity.' . $key,
                    $value,
                    SuccessMetricSource::MOODLE_LOGS,
                    $measuredat,
                    [
                        'window_days' =>
                            str_contains($key, '_7d') ? 7 : 30,
                        'estimated' =>
                            str_starts_with(
                                $key,
                                'estimated_'
                            ),
                    ]
                )
            );
        }

        return $metrics;
    }

    private function metric(
        int $userid,
        string $key,
        int|float|string|bool|null $value,
        string $source,
        int $measuredat,
        array $metadata = []
    ): SuccessMetric {
        return new SuccessMetric(
            $userid,
            $key,
            $value,
            $source,
            $measuredat,
            $metadata
        );
    }
}