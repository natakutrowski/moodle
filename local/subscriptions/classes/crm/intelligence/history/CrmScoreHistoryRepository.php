<?php

namespace local_subscriptions\crm\intelligence\history;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\intelligence\core\UserIntelligence;

final class CrmScoreHistoryRepository {

    private const TABLE = 'local_subscriptions_crm_score';

    public function save(
        int $userid,
        UserIntelligence $intelligence,
        ?int $timecreated = null
    ): int {
        global $DB;

        $timecreated = $timecreated ?? time();

        if ($timecreated <= 0) {
            throw new \InvalidArgumentException(
                'CRM score snapshot timestamp must be greater than zero.'
            );
        }

        $score = $intelligence->leadScore;

        $record = (object)[
            'userid' => $userid,
            'commercialscore' => $score->commercial,
            'engagementscore' => $score->engagement,
            'riskscore' => $score->risk,
            'globalscore' => $score->global(),
            'level' => $score->level(),
            'segmentsjson' => json_encode(array_map(static fn($segment) => $segment->key, $intelligence->segments)),
            'opportunitiesjson' => json_encode(array_map(static fn($opportunity) => $opportunity->key, $intelligence->opportunities)),
            'recommendationsjson' => json_encode(array_map(static fn($recommendation) => $recommendation->key, $intelligence->recommendations)),
            'timecreated' => $timecreated,
        ];

        return (int)$DB->insert_record(self::TABLE, $record);
    }

    public function latest_for_user(int $userid): ?\stdClass {
        global $DB;

        $records = $DB->get_records(
            self::TABLE,
            ['userid' => $userid],
            'timecreated DESC, id DESC',
            '*',
            0,
            1
        );

        return $records ? reset($records) : null;
    }

    public function recent_for_user(int $userid, int $limit = 10): array {
        global $DB;

        return array_values($DB->get_records(
            self::TABLE,
            ['userid' => $userid],
            'timecreated DESC, id DESC',
            '*',
            0,
            $limit
        ));
    }

    public function cleanup_older_than(int $days = 180): int {
        global $DB;

        return $DB->delete_records_select(
            self::TABLE,
            'timecreated < :cutoff',
            ['cutoff' => time() - ($days * DAYSECS)]
        );
    }
}