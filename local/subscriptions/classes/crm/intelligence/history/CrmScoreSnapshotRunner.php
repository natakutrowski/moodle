<?php

namespace local_subscriptions\crm\intelligence\history;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\batch\CrmCandidateUserIterator;
use local_subscriptions\crm\intelligence\core\CrmIntelligenceLimits;
use local_subscriptions\crm\intelligence\runtime\CrmComputationContext;
use local_subscriptions\crm\intelligence\runtime\CrmComputationSources;
use local_subscriptions\crm\intelligence\runtime\CrmUserComputationService;

final class CrmScoreSnapshotRunner {

    public function __construct(
        private readonly CrmCandidateUserIterator
            $users =
                new CrmCandidateUserIterator(),
        private readonly CrmUserComputationService
            $computation =
                new CrmUserComputationService(),
        private readonly CrmScoreHistoryRepository
            $history =
                new CrmScoreHistoryRepository()
    ) {
    }

    public function run(
        int $limit =
            CrmIntelligenceLimits::SNAPSHOT_USERS
    ): int {
        $context =
            CrmComputationContext::create(
                CrmComputationSources::SNAPSHOT
            );

        $count = 0;

        foreach (
            $this->users->iterate(
                CrmIntelligenceLimits::
                    SNAPSHOT_BATCH_SIZE
            ) as $user
        ) {
            if ($count >= $limit) {
                break;
            }

            $result =
                $this->computation->compute(
                    user: $user,
                    context: $context,
                    withtrend: false
                );

            $this->history->save(
                userid: $result->userid,
                intelligence:
                    $result->intelligence,
                timecreated:
                    $context->startedat
            );

            $count++;
        }

        $this->history->cleanup_older_than(
            CrmIntelligenceLimits::
                HISTORY_RETENTION_DAYS
        );

        return $count;
    }
}