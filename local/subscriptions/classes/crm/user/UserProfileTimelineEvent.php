<?php

namespace local_subscriptions\crm\user;

defined('MOODLE_INTERNAL') || die();

final class UserProfileTimelineEvent {

    public function __construct(
        public readonly string $type,
        public readonly int $timecreated,
        public readonly string $title,
        public readonly string $description = '',
        public readonly string $icon = 'event',
        public readonly string $importance = 'normal',
        public readonly ?string $actionurl = null,
        public readonly array $metadata = []
    ) {
    }

    public function to_object(): \stdClass {
        $object = (object)[
            'type' => $this->type,
            'timecreated' => $this->timecreated,
            'title' => $this->title,
            'description' => $this->description,
            'body' => $this->description,
            'icon' => $this->icon,
            'importance' => $this->importance,
            'actionurl' => $this->actionurl,
            'metadata' => $this->metadata,
        ];

        foreach ($this->metadata as $key => $value) {
            $object->{$key} = $value;
        }

        return $object;
    }
}