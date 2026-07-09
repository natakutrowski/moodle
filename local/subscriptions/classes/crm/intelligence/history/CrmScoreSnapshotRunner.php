<?php

namespace local_subscriptions\crm\intelligence\history;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\core\CrmIntelligenceLimits;
use local_subscriptions\crm\intelligence\core\UserIntelligenceBuilder;
use local_subscriptions\crm\intelligence\batch\CrmCandidateUserIterator;

final class CrmScoreSnapshotRunner {

    public function __construct(
        private readonly CrmCandidateUserIterator $users = new CrmCandidateUserIterator(),
        private readonly UserIntelligenceBuilder $builder = new UserIntelligenceBuilder(),
        private readonly CrmScoreHistoryRepository $history = new CrmScoreHistoryRepository()
    ) {
    }

    public function run(int $limit = CrmIntelligenceLimits::SNAPSHOT_USERS): int {
        $count = 0;

        foreach ($this->users->iterate(CrmIntelligenceLimits::SNAPSHOT_BATCH_SIZE) as $user) {
            if ($count >= $limit) {
                break;
            }

            $intelligence = $this->builder->build_for_user($user, false);
            $this->history->save((int)$user->id, $intelligence);

            $count++;
        }

        $this->history->cleanup_older_than(CrmIntelligenceLimits::HISTORY_RETENTION_DAYS);

        return $count;
    }
}