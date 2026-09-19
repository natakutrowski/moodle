<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\access;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\education\promotion\CommercePedagogicalPromotionRepository;

final class CommerceStudentCourseAccessRepository {
    private const TABLE = 'local_subs_commerce_ped_access';

    public function __construct(
        private readonly \moodle_database $db
    ) {
    }

    public static function create(
        ?\moodle_database $db = null
    ): self {
        global $DB;
        return new self($db ?? $DB);
    }

    public function find(
        int $courseid,
        int $userid
    ): ?CommerceStudentCourseAccess {
        $record = $this->db->get_record(
            self::TABLE,
            [
                'courseid' => $courseid,
                'userid' => $userid,
            ],
            '*',
            IGNORE_MISSING
        );

        return $record ? $this->hydrate($record) : null;
    }

    public function save(
        CommerceStudentCourseAccess $access
    ): CommerceStudentCourseAccess {
        if (!$this->db->record_exists(
            'course',
            ['id' => $access->get_course_id()]
        )) {
            throw new \coding_exception(
                'Cannot persist student access for an unknown course.'
            );
        }

        if (!$this->db->record_exists(
            'user',
            ['id' => $access->get_user_id()]
        )) {
            throw new \coding_exception(
                'Cannot persist student access for an unknown user.'
            );
        }

        $promotionid = $access->get_promotion_id();
        if ($promotionid !== null) {
            $promotion =
                CommercePedagogicalPromotionRepository::create($this->db)
                    ->get_by_id($promotionid);

            if (
                $promotion === null
                || $promotion->get_course_id()
                    !== $access->get_course_id()
            ) {
                throw new \coding_exception(
                    'Student access promotion must belong to the same Moodle course.'
                );
            }
        }

        $existing = $this->db->get_record(
            self::TABLE,
            [
                'courseid' => $access->get_course_id(),
                'userid' => $access->get_user_id(),
            ],
            'id,timecreated,createdby',
            IGNORE_MISSING
        );

        $record = (object)[
            'courseid' => $access->get_course_id(),
            'userid' => $access->get_user_id(),
            'promotionid' => $promotionid,
            'profile' => $access->get_profile(),
            'createdby' => $access->get_created_by(),
            'modifiedby' => $access->get_modified_by(),
            'timecreated' => $access->get_time_created(),
            'timemodified' => $access->get_time_modified(),
        ];

        if ($existing) {
            $record->id = (int)$existing->id;
            $record->timecreated =
                (int)$existing->timecreated;
            $record->createdby =
                $existing->createdby !== null
                    ? (int)$existing->createdby
                    : $access->get_created_by();

            $this->db->update_record(
                self::TABLE,
                $record
            );
        } else {
            $record->id = $this->db->insert_record(
                self::TABLE,
                $record
            );
        }

        return $this->find(
            $access->get_course_id(),
            $access->get_user_id()
        ) ?? throw new \coding_exception(
            'Student course access could not be reloaded after save.'
        );
    }

    private function hydrate(
        \stdClass $record
    ): CommerceStudentCourseAccess {
        return new CommerceStudentCourseAccess(
            (int)$record->id,
            (int)$record->courseid,
            (int)$record->userid,
            $record->promotionid !== null
                ? (int)$record->promotionid
                : null,
            (string)$record->profile,
            $record->createdby !== null
                ? (int)$record->createdby
                : null,
            $record->modifiedby !== null
                ? (int)$record->modifiedby
                : null,
            (int)$record->timecreated,
            (int)$record->timemodified
        );
    }
}
