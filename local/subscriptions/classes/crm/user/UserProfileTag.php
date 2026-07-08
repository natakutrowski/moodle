<?php

namespace local_subscriptions\crm\user;

defined('MOODLE_INTERNAL') || die();

final class UserProfileTag {

    public function __construct(
        public readonly int $id,
        public readonly int $userid,
        public readonly string $tag,
        public readonly int $createdby,
        public readonly int $timecreated
    ) {
    }

    public static function from_record(\stdClass $record): self {
        return new self(
            (int)$record->id,
            (int)$record->userid,
            (string)$record->tag,
            (int)$record->createdby,
            (int)$record->timecreated
        );
    }

    public function to_object(): \stdClass {
        return (object)[
            'id' => $this->id,
            'userid' => $this->userid,
            'tag' => $this->tag,
            'label' => self::label($this->tag),
            'createdby' => $this->createdby,
            'timecreated' => $this->timecreated,
        ];
    }

    public static function label(string $tag): string {
        $key = 'crm_tag_' . $tag;

        if (get_string_manager()->string_exists($key, 'local_subscriptions')) {
            return get_string($key, 'local_subscriptions');
        }

        return $tag;
    }

    public static function allowed_tags(): array {
        return [
            'vip',
            'followup',
            'payment_issue',
            'refund',
            'manual_access',
            'sensitive',
        ];
    }
}