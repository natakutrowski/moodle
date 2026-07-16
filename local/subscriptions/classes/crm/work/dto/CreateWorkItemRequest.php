<?php

namespace local_subscriptions\crm\work\dto;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\work\domain\WorkItemPriority;
use local_subscriptions\crm\work\domain\WorkItemSource;
use local_subscriptions\crm\work\domain\WorkItemType;

final class CreateWorkItemRequest {

    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly string $type,
        public readonly string $priority,
        public readonly string $source,
        public readonly int $createdby,
        public readonly ?int $targetuserid = null,
        public readonly ?int $assigneduserid = null,
        public readonly ?int $assignedteamid = null,
        public readonly ?int $parentid = null,
        public readonly ?int $dueat = null
    ) {
        if (trim($this->title) === '') {
            throw new \InvalidArgumentException(
                'Work item title cannot be empty.'
            );
        }

        if (\core_text::strlen($this->title) > 255) {
            throw new \InvalidArgumentException(
                'Work item title is too long.'
            );
        }

        if (!WorkItemType::is_valid($this->type)) {
            throw new \InvalidArgumentException(
                'Invalid work item type.'
            );
        }

        if (!WorkItemPriority::is_valid($this->priority)) {
            throw new \InvalidArgumentException(
                'Invalid work item priority.'
            );
        }

        if (!WorkItemSource::is_valid($this->source)) {
            throw new \InvalidArgumentException(
                'Invalid work item source.'
            );
        }

        if ($this->createdby <= 0) {
            throw new \InvalidArgumentException(
                'Work item creator is required.'
            );
        }

        if (
            $this->parentid !== null &&
            $this->parentid <= 0
        ) {
            throw new \InvalidArgumentException(
                'Work item parent ID must be greater than zero.'
            );
        }

    }
}