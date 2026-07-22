<?php

namespace local_subscriptions\crm\user;

defined('MOODLE_INTERNAL') || die();

/**
 * One page of User360 Timeline events.
 */
final class UserProfileTimelinePage {

    /**
     * @param UserProfileTimelineEvent[] $events
     */
    public function __construct(
        public readonly array $events,
        public readonly int $offset,
        public readonly int $limit,
        public readonly bool $hasmore
    ) {
    }

    public function next_offset(): int {
        return $this->offset + count(
            $this->events
        );
    }
}