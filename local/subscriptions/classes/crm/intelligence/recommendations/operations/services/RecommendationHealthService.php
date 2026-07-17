<?php

namespace local_subscriptions\crm\intelligence\recommendations\operations\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\recommendations\operations\RecommendationRunStatus;
use local_subscriptions\crm\intelligence\recommendations\operations\dto\RecommendationHealthStatus;
use local_subscriptions\crm\intelligence\recommendations\operations\repositories\RecommendationOperationsRepository;
use local_subscriptions\crm\intelligence\recommendations\operations\repositories\RecommendationRunRepository;

/**
 * Evaluates Recommendation Engine operational health.
 */
final class RecommendationHealthService {

    public function __construct(
        private readonly RecommendationRunRepository $runs =
            new RecommendationRunRepository(),
        private readonly RecommendationOperationsRepository $operations =
            new RecommendationOperationsRepository()
    ) {
    }

    public function evaluate(
        ?int $now = null
    ): RecommendationHealthStatus {
        $now = $now ?? time();

        $latest = $this->runs->latest();
        $counters =
            $this->operations->counters();

        $failedruns24h =
            $this->operations
                ->failed_runs_since(
                    $now - DAYSECS
                );

        $warnings = [];
        $errors = [];

        if ($latest === null) {
            $warnings[] = 'no_run_available';
        } else {
            $age =
                $now -
                (int)$latest->startedat;

            if ($age > 3 * HOURSECS) {
                $errors[] =
                    'last_run_too_old';
            } else if ($age > 90 * MINSECS) {
                $warnings[] =
                    'last_run_delayed';
            }

            if (
                (string)$latest->status ===
                RecommendationRunStatus::FAILED
            ) {
                $errors[] =
                    'last_run_failed';
            } else if (
                (string)$latest->status ===
                RecommendationRunStatus::PARTIAL
            ) {
                $warnings[] =
                    'last_run_partial';
            }
        }

        $dueexpirationcount =
            (int)($counters
                ->dueexpirationcount ?? 0);

        if ($dueexpirationcount > 0) {
            $warnings[] =
                'recommendations_waiting_expiration';
        }

        if ($failedruns24h >= 3) {
            $errors[] =
                'repeated_run_failures';
        } else if ($failedruns24h > 0) {
            $warnings[] =
                'recent_run_failures';
        }

        $status = $errors !== []
            ? RecommendationHealthStatus::UNHEALTHY
            : (
                $warnings !== []
                    ? RecommendationHealthStatus::DEGRADED
                    : RecommendationHealthStatus::HEALTHY
            );

        return new RecommendationHealthStatus(
            status: $status,
            lastrunat:
                $latest !== null
                    ? (int)$latest->startedat
                    : null,
            lastrunstatus:
                $latest !== null
                    ? (string)$latest->status
                    : null,
            activecount:
                (int)($counters->activecount ?? 0),
            criticalcount:
                (int)($counters->criticalcount ?? 0),
            dueexpirationcount:
                $dueexpirationcount,
            failedruns24h:
                $failedruns24h,
            warnings:
                array_values(array_unique(
                    $warnings
                )),
            errors:
                array_values(array_unique(
                    $errors
                ))
        );
    }
}