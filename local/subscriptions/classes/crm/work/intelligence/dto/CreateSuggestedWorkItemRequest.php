<?php

namespace local_subscriptions\crm\work\intelligence\dto;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\work\domain\WorkItemPriority;
use local_subscriptions\crm\work\domain\WorkItemType;

/**
 * Administrator-confirmed values used to create a suggested Work Item.
 */
final class CreateSuggestedWorkItemRequest {

    public function __construct(
        public readonly int $recommendationid,
        public readonly int $createdby,
        public readonly string $title,
        public readonly string $description,
        public readonly string $type,
        public readonly string $priority,
        public readonly ?int $assignedteamid = null,
        public readonly ?int $assigneduserid = null,
        public readonly ?int $dueat = null,
        public readonly bool $allowduplicate = false
    ) {
        if ($this->recommendationid <= 0) {
            throw new \InvalidArgumentException(
                'Recommendation ID must be greater than zero.'
            );
        }

        if ($this->createdby <= 0) {
            throw new \InvalidArgumentException(
                'Work Item creator is required.'
            );
        }

        if (trim($this->title) === '') {
            throw new \InvalidArgumentException(
                'Suggested Work Item title cannot be empty.'
            );
        }

        if (!WorkItemType::is_valid($this->type)) {
            throw new \InvalidArgumentException(
                'Invalid Work Item type.'
            );
        }

        if (!WorkItemPriority::is_valid($this->priority)) {
            throw new \InvalidArgumentException(
                'Invalid Work Item priority.'
            );
        }
    }
}