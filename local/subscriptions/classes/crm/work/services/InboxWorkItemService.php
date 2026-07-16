<?php

namespace local_subscriptions\crm\work\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\work\domain\WorkItemPriority;
use local_subscriptions\crm\work\domain\WorkItemRelation;
use local_subscriptions\crm\work\domain\WorkItemSource;
use local_subscriptions\crm\work\domain\WorkItemType;
use local_subscriptions\crm\work\dto\CreateWorkItemRequest;
use local_subscriptions\crm\work\repositories\InboxWorkItemRepository;

final class InboxWorkItemService {

    public function __construct(
        private readonly WorkItemService $workitems =
            new WorkItemService(),
        private readonly InboxWorkItemRepository $inbox =
            new InboxWorkItemRepository()
    ) {
    }

    public function create_from_thread(
        int $threadid,
        string $title,
        string $description,
        int $createdby,
        ?int $targetuserid = null,
        string $type = WorkItemType::SUPPORT,
        string $priority = WorkItemPriority::NORMAL,
        ?int $assigneduserid = null,
        ?int $assignedteamid = null
    ): \stdClass {
        $thread = $this->inbox->get_active_thread(
            $threadid
        );

        $item = $this->workitems->create(
            new CreateWorkItemRequest(
                $title,
                $description,
                $type,
                $priority,
                WorkItemSource::INBOX,
                $createdby,
                $targetuserid,
                $assigneduserid,
                $assignedteamid
            )
        );

        $this->workitems->link(
            (int)$item->id,
            'inbox_thread',
            (int)$thread->id,
            WorkItemRelation::CREATED_FROM,
            $createdby
        );

        if (!empty($thread->contactid)) {
            $this->workitems->link(
                (int)$item->id,
                'inbox_contact',
                (int)$thread->contactid,
                WorkItemRelation::RELATED,
                $createdby
            );
        }

        return $item;
    }
}