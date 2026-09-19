<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\participant;

defined('MOODLE_INTERNAL') || die();

/**
 * Read model for pedagogical-promotion participants.
 */
final class CommercePedagogicalParticipantRepository {
    public function __construct(private readonly \moodle_database $db) {}

    public static function create(?\moodle_database $db = null): self {
        global $DB;
        return new self($db ?? $DB);
    }

    /** @return \stdClass[] */
    public function for_promotion(int $promotionid): array {
        $sql = "SELECT a.id AS accessid,
                       a.courseid,
                       a.userid,
                       a.promotionid,
                       a.profile,
                       a.timecreated,
                       a.timemodified,
                       u.firstname,
                       u.lastname,
                       u.firstnamephonetic,
                       u.lastnamephonetic,
                       u.middlename,
                       u.alternatename,
                       u.email,
                       pg.id AS pedagogicalgroupid,
                       pg.displayname AS groupname,
                       pg.moodlegroupid
                  FROM {local_subs_commerce_ped_access} a
                  JOIN {user} u ON u.id = a.userid
             LEFT JOIN {local_subs_commerce_ped_gmem} gm
                    ON gm.userid = a.userid
                   AND gm.active = 1
                   AND EXISTS (
                       SELECT 1
                         FROM {local_subs_commerce_ped_group} gscope
                        WHERE gscope.id = gm.groupid
                          AND gscope.promotionid = a.promotionid
                          AND gscope.active = 1
                   )
             LEFT JOIN {local_subs_commerce_ped_group} pg
                    ON pg.id = gm.groupid
                 WHERE a.promotionid = :promotionid
              ORDER BY u.lastname ASC, u.firstname ASC, u.id ASC";

        return array_values($this->db->get_records_sql($sql, [
            'promotionid' => $promotionid,
        ]));
    }
}
