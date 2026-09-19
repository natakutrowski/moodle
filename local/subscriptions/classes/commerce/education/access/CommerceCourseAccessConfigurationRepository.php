<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\education\access;

defined('MOODLE_INTERNAL') || die();

/**
 * Stores the pedagogical access mode for one Moodle course.
 *
 * Missing configuration deliberately means classic_immediate, so every course
 * that existed before 7.97 keeps its historical behaviour without a backfill.
 */
final class CommerceCourseAccessConfigurationRepository {
    private const TABLE = 'local_subs_commerce_course_cfg';

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

    public function mode_for_course(int $courseid): string {
        if ($courseid <= 0) {
            throw new \coding_exception(
                'Commerce course access configuration requires a valid course id.'
            );
        }

        $mode = $this->db->get_field(
            self::TABLE,
            'accessmode',
            ['courseid' => $courseid],
            IGNORE_MISSING
        );

        if ($mode === false || trim((string)$mode) === '') {
            return CommerceCourseAccessMode::default();
        }

        return CommerceCourseAccessMode::normalise((string)$mode);
    }

    /**
     * Courses explicitly configured for pedagogical promotions.
     *
     * Unconfigured courses intentionally remain classic and therefore do not
     * belong in promotion creation pickers.
     *
     * @return int[]
     */
    public function promotion_course_ids(): array {
        $records = $this->db->get_records(
            self::TABLE,
            ['accessmode' => CommerceCourseAccessMode::PROMOTION],
            'courseid ASC',
            'courseid'
        );

        return array_map(
            static fn(\stdClass $record): int => (int)$record->courseid,
            array_values($records)
        );
    }

    public function set_mode(
        int $courseid,
        string $mode,
        ?int $modifiedby = null
    ): void {
        if (
            $courseid <= 0
            || !$this->db->record_exists('course', ['id' => $courseid])
        ) {
            throw new \coding_exception(
                'Cannot configure access mode for an unknown Moodle course.'
            );
        }

        $mode = CommerceCourseAccessMode::normalise($mode);
        $now = time();

        $existing = $this->db->get_record(
            self::TABLE,
            ['courseid' => $courseid],
            'id',
            IGNORE_MISSING
        );

        if ($existing) {
            $this->db->update_record(
                self::TABLE,
                (object)[
                    'id' => (int)$existing->id,
                    'accessmode' => $mode,
                    'modifiedby' => $modifiedby,
                    'timemodified' => $now,
                ]
            );
            return;
        }

        $this->db->insert_record(
            self::TABLE,
            (object)[
                'courseid' => $courseid,
                'accessmode' => $mode,
                'modifiedby' => $modifiedby,
                'timecreated' => $now,
                'timemodified' => $now,
            ]
        );
    }
}
