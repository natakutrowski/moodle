<?php

namespace local_subscriptions\crm\user;

defined('MOODLE_INTERNAL') || die();

final class UserProfileNote {

    public function __construct(
        public readonly int $id,
        public readonly int $userid,
        public readonly int $authorid,
        public readonly string $note,
        public readonly string $type,
        public readonly int $timecreated
    ) {
    }

    public static function from_record(\stdClass $record): self {
        return new self(
            (int)$record->id,
            (int)$record->userid,
            (int)$record->authorid,
            (string)$record->note,
            (string)($record->type ?? 'general'),
            (int)$record->timecreated
        );
    }

    public function to_object(): \stdClass {
        return (object)[
            'id' => $this->id,
            'userid' => $this->userid,
            'authorid' => $this->authorid,
            'note' => $this->note,
            'body' => $this->note,
            'type' => $this->type,
            'timecreated' => $this->timecreated,
        ];
    }
}