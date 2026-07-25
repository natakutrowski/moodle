<?php

namespace local_subscriptions\commerce\task\health;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\task\persistence\CommerceTaskRunRepository;
use local_subscriptions\commerce\task\repository\PaidPaymentRequestRepairRepository;

/**
 * Builds a dashboard-ready operational snapshot for Commerce cron tasks.
 */
final class CommerceCronHealthService {

    public function __construct(
        private readonly ?CommerceTaskRunRepository $runs = null,
    ) {
    }

    public function snapshot(int $windowseconds = 7 * DAYSECS): CommerceCronHealthSnapshot {
        $repository = $this->runs ?? new CommerceTaskRunRepository();
        $windowstart = time() - max(HOURSECS, $windowseconds);
        $latest = $repository->latest_by_job();
        $aggregates = $repository->aggregate_since($windowstart);
        $jobs = [];
        $globalstatus = 'healthy';

        foreach ($latest as $run) {
            $aggregate = $aggregates[$run->jobname] ?? null;
            $status = (string) $run->status;

            if ($status === 'failed') {
                $globalstatus = 'critical';
            } else if ($status === 'warning' && $globalstatus === 'healthy') {
                $globalstatus = 'degraded';
            }

            $jobs[] = new CommerceCronJobHealth(
                (string) $run->jobname,
                $status,
                (int) $run->finishedat,
                (int) $run->durationms,
                (int) ($aggregate->executions ?? 1),
                (int) round((float) ($aggregate->averagedurationms ?? $run->durationms)),
                (int) ($aggregate->maxdurationms ?? $run->durationms),
                (int) ($aggregate->failures ?? 0),
                (int) ($aggregate->warnings ?? 0),
                (int) ($aggregate->lockmisses ?? 0),
                $this->decode_counters((string) $run->countersjson),
            );
        }

        $quarantined = (new PaidPaymentRequestRepairRepository())->count_quarantined();
        if ($quarantined > 0 && $globalstatus === 'healthy') {
            $globalstatus = 'degraded';
        }

        return new CommerceCronHealthSnapshot(
            $globalstatus,
            time(),
            $windowstart,
            $jobs,
            $quarantined,
        );
    }

    private function decode_counters(string $json): array {
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }
}
