<?php

namespace local_subscriptions\dashboard\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\AdminFormatter;
use local_subscriptions\dashboard\repositories\DashboardStatsRepository;

final class DashboardStatsService {

    public function __construct(
        private readonly DashboardStatsRepository $repository = new DashboardStatsRepository()
    ) {
    }

    public function load(string $period = DashboardPeriod::TODAY): \stdClass {
        $period = DashboardPeriod::normalize($period);
        $range = DashboardPeriod::range($period);

        $stats = new \stdClass();

        $stats->period = $period;
        $stats->periodlabel = DashboardPeriod::label($period);
        $stats->newusers = $this->repository->count_new_users($range['start'], $range['end']);
        $stats->newsubscriptions = $this->repository->count_new_subscriptions($range['start'], $range['end']);
        $stats->digitalpurchases = $this->repository->count_digital_purchases($range['start'], $range['end']);

        $revenues = $this->repository->get_digital_revenue_by_currency($range['start'], $range['end']);
        $stats->revenues = $revenues;
        $stats->revenue = $this->format_revenues($revenues);

        return $stats;
    }

    private function format_revenues(array $revenues): string {
        if (!$revenues) {
            return '-';
        }

        $parts = [];

        foreach ($revenues as $revenue) {
            $parts[] = AdminFormatter::price($revenue->total ?? 0, $revenue->currency ?? '');
        }

        return implode('<br>', $parts);
    }
}