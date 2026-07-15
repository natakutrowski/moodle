<?php

namespace local_subscriptions\dashboard\inbox;

defined('MOODLE_INTERNAL') || die();

final class DashboardInboxService {

    public function __construct(
        private readonly DashboardInboxRepository $repository
    ) {
    }

    public function load(
        int $recentlimit = 5
    ): DashboardInboxSummary {
        if (!$this->repository->is_available()) {
            return DashboardInboxSummary::unavailable();
        }

        $counts =
            $this->repository->get_counts();

        return new DashboardInboxSummary(
            true,

            (int)(
                $counts->opencount
                ?? 0
            ),

            (int)(
                $counts->unassignedcount
                ?? 0
            ),

            (int)(
                $counts->urgentcount
                ?? 0
            ),

            (int)(
                $counts->pendingcount
                ?? 0
            ),

            $this->repository->get_recent_threads(
                $recentlimit
            )
        );
    }
}