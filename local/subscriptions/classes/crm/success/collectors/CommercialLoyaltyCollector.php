<?php

namespace local_subscriptions\crm\success\collectors;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\collection\SuccessMetric;
use local_subscriptions\crm\success\collection\SuccessMetricCollection;
use local_subscriptions\crm\success\contracts\SuccessCollectorInterface;
use local_subscriptions\crm\success\domain\SuccessMetricSource;
use local_subscriptions\crm\success\repositories\CommercialLoyaltyRepository;

/**
 * Collects commercial and loyalty metrics.
 */
final class CommercialLoyaltyCollector implements
    SuccessCollectorInterface {

    public function __construct(
        private readonly CommercialLoyaltyRepository $repository =
            new CommercialLoyaltyRepository()
    ) {
    }

    public function key(): string {
        return 'commercial_loyalty';
    }

    public function is_available(): bool {
        return $this->repository->is_available();
    }

    public function collect(
        int $userid,
        int $measuredat
    ): SuccessMetricCollection {

        $statistics =
            $this->repository->get_statistics(
                $userid,
                $this->repository->get_user_email($userid),
                $measuredat
            );

        $metrics = new SuccessMetricCollection();

        foreach ($statistics as $key => $value) {
            $prefix = $this->metric_prefix($key);

            $metrics->add(
                new SuccessMetric(
                    $userid,
                    $prefix . '.' . $key,
                    $value,
                    $this->metric_source($prefix),
                    $measuredat,
                    [
                        'aggregate' => true,
                        'monetary' =>
                            str_contains($key, 'revenue'),
                    ]
                )
            );
        }

        return $metrics;
    }

    private function metric_prefix(
        string $key
    ): string {
        if (
            str_contains($key, 'customer_age') ||
            str_contains($key, 'first_commercial') ||
            str_contains($key, 'last_commercial') ||
            str_contains($key, 'replaced_subscription')
        ) {
            return 'loyalty';
        }

        return 'commercial';
    }

    private function metric_source(
        string $prefix
    ): string {
        return $prefix === 'loyalty'
            ? SuccessMetricSource::CRM
            : SuccessMetricSource::SUBSCRIPTIONS;
    }
}