<?php

namespace local_subscriptions\crm\intelligence\trends;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\history\CrmScoreHistoryRepository;

final class CrmScoreTrendService {

    public function __construct(
        private readonly CrmScoreHistoryRepository $history = new CrmScoreHistoryRepository()
    ) {
    }

    public function global_trend_for_user(int $userid): ?CrmScoreTrend {
        $records = $this->history->recent_for_user($userid, 2);

        if (empty($records)) {
            return null;
        }

        $current = (int)$records[0]->globalscore;
        $previous = isset($records[1]) ? (int)$records[1]->globalscore : null;

        return new CrmScoreTrend(
            $current,
            $previous,
            $previous === null ? 0 : $current - $previous
        );
    }
}