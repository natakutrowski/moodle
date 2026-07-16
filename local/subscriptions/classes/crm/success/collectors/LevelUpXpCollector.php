<?php

namespace local_subscriptions\crm\success\collectors;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\collection\SuccessMetric;
use local_subscriptions\crm\success\collection\SuccessMetricCollection;
use local_subscriptions\crm\success\contracts\SuccessCollectorInterface;
use local_subscriptions\crm\success\domain\SuccessMetricSource;
use local_subscriptions\crm\success\repositories\LevelUpXpRepository;

/**
 * Collects normalized Level Up XP metrics.
 */
final class LevelUpXpCollector implements
    SuccessCollectorInterface {

    public function __construct(
        private readonly LevelUpXpRepository $repository =
            new LevelUpXpRepository()
    ) {
    }

    public function key(): string {
        return 'levelup_xp';
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
                    'gamification.levelup.' . $key,
                    $value,
                    SuccessMetricSource::LEVELUP_XP,
                    $measuredat,
                    [
                        'plugin' => 'block_xp',
                        'estimated' => false,
                    ]
                )
            );
        }

        return $metrics;
    }
}