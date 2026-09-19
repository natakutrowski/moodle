<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\xp;

defined('MOODLE_INTERNAL') || die();

/** Persistence for promotion-scoped team XP contributions. */
final class CommercePedagogicalXpRepository {
    public const TABLE = 'local_subs_commerce_ped_xp';

    public function __construct(private readonly \moodle_database $db) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        return new self($db ?? $DB);
    }

    public function find_by_source_hash(
        int $promotionid,
        int $userid,
        string $sourcehash
    ): ?CommercePedagogicalXpContribution {
        $record = $this->db->get_record(
            self::TABLE,
            [
                'promotionid' => $promotionid,
                'userid' => $userid,
                'sourcehash' => $sourcehash,
            ],
            '*',
            IGNORE_MISSING
        );
        return $record ? $this->hydrate($record) : null;
    }

    public function insert(CommercePedagogicalXpContribution $contribution): CommercePedagogicalXpContribution {
        $id = $this->db->insert_record(self::TABLE, (object)[
            'promotionid' => $contribution->get_promotion_id(),
            'courseid' => $contribution->get_course_id(),
            'userid' => $contribution->get_user_id(),
            'sourcecomponent' => $contribution->get_source_component(),
            'sourcetype' => $contribution->get_source_type(),
            'sourcekey' => $contribution->get_source_key(),
            'sourcehash' => $contribution->get_source_hash(),
            'points' => $contribution->get_points(),
            'timeearned' => $contribution->get_time_earned(),
            'timecreated' => $contribution->get_time_created(),
        ]);
        $record = $this->db->get_record(self::TABLE, ['id' => $id], '*', MUST_EXIST);
        return $this->hydrate($record);
    }

    public function user_points(int $promotionid, int $userid): int {
        return (int)$this->db->get_field_select(
            self::TABLE,
            'COALESCE(SUM(points), 0)',
            'promotionid = :promotionid AND userid = :userid',
            ['promotionid' => $promotionid, 'userid' => $userid]
        );
    }

    /**
     * @return array<int,int> user id => points
     */
    public function points_by_user(int $promotionid): array {
        $records = $this->db->get_records_sql(
            "SELECT userid, SUM(points) AS points
               FROM {" . self::TABLE . "}
              WHERE promotionid = :promotionid
           GROUP BY userid",
            ['promotionid' => $promotionid]
        );
        $result = [];
        foreach ($records as $record) {
            $result[(int)$record->userid] = (int)$record->points;
        }
        return $result;
    }

    private function hydrate(\stdClass $record): CommercePedagogicalXpContribution {
        return new CommercePedagogicalXpContribution(
            (int)$record->id,
            (int)$record->promotionid,
            (int)$record->courseid,
            (int)$record->userid,
            (string)$record->sourcecomponent,
            (string)$record->sourcetype,
            (string)$record->sourcekey,
            (string)$record->sourcehash,
            (int)$record->points,
            (int)$record->timeearned,
            (int)$record->timecreated
        );
    }
}
