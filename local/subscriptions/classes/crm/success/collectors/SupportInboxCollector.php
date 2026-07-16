<?php

namespace local_subscriptions\crm\success\collectors;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\collection\SuccessMetric;
use local_subscriptions\crm\success\collection\SuccessMetricCollection;
use local_subscriptions\crm\success\contracts\SuccessCollectorInterface;
use local_subscriptions\crm\success\domain\SuccessMetricSource;
use local_subscriptions\crm\success\repositories\SupportInboxRepository;

/**
 * Collects aggregated CRM Inbox support metrics.
 */
final class SupportInboxCollector implements
    SuccessCollectorInterface {

    public function __construct(
        private readonly SupportInboxRepository $repository =
            new SupportInboxRepository()
    ) {
    }

    public function key(): string {
        return 'support_inbox';
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
                    'support.inbox.' . $key,
                    $value,
                    SuccessMetricSource::INBOX,
                    $measuredat,
                    [
                        'aggregate' => true,
                        'contains_content' => false,
                    ]
                )
            );
        }

        return $metrics;
    }
}