<?php

namespace local_subscriptions\crm\work\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\admin\AdminEvents;
use local_subscriptions\admin\AdminLog;
use local_subscriptions\crm\work\domain\WorkItemPriority;
use local_subscriptions\crm\work\domain\WorkItemRelation;
use local_subscriptions\crm\work\domain\WorkItemStatus;
use local_subscriptions\crm\work\domain\WorkItemTransitionPolicy;
use local_subscriptions\crm\work\dto\CreateWorkItemRequest;
use local_subscriptions\crm\work\repositories\WorkItemRepository;

final class WorkItemService {

    public function __construct(
        private readonly WorkItemRepository $repository =
            new WorkItemRepository()
    ) {
    }

    public function create(
        CreateWorkItemRequest $request
    ): \stdClass {

        if ($request->parentid !== null) {
            $this->repository->get(
                $request->parentid
            );
        }

        $item = $this->repository->create($request);

        $this->repository->add_history(
            (int)$item->id,
            $request->createdby,
            'created',
            null,
            [
                'type' => $item->type,
                'priority' => $item->priority,
                'source' => $item->source,
            ]
        );

        AdminLog::log(
            AdminEvents::WORK_ITEM_CREATED,
            $item->targetuserid !== null
                ? (int)$item->targetuserid
                : null,
            'work_item',
            (int)$item->id,
            [
                'reference' => $item->reference,
                'type' => $item->type,
                'priority' => $item->priority,
                'source' => $item->source,
            ]
        );

        return $item;
    }

    public function change_status(
        int $itemid,
        string $status,
        int $actorid
    ): \stdClass {
        if (!WorkItemStatus::is_valid($status)) {
            throw new \InvalidArgumentException(
                'Invalid work item status.'
            );
        }

        $before = $this->repository->get($itemid);

        if (
            !WorkItemTransitionPolicy::can_transition(
                (string)$before->status,
                $status
            )
        ) {
            throw new \InvalidArgumentException(
                'Unsupported work item status transition.'
            );
        }

        $fields = [
            'status' => $status,
        ];

        if (
            $status !== WorkItemStatus::RESOLVED &&
            $status !== WorkItemStatus::CLOSED
        ) {
            $fields['resolvedat'] = null;
            $fields['closedat'] = null;
        }

        if ($status === WorkItemStatus::RESOLVED) {
            $fields['resolvedat'] = time();
            $fields['closedat'] = null;
        }

        if ($status === WorkItemStatus::CLOSED) {
            $fields['closedat'] = time();

            if (empty($before->resolvedat)) {
                $fields['resolvedat'] = time();
            }
        }

        $item = $this->repository->update_fields(
            $itemid,
            $fields
        );

        $this->repository->add_history(
            $itemid,
            $actorid,
            'status_changed',
            $before->status,
            $status
        );

        AdminLog::log(
            AdminEvents::WORK_ITEM_STATUS_CHANGED,
            $item->targetuserid !== null
                ? (int)$item->targetuserid
                : null,
            'work_item',
            $itemid,
            [
                'reference' => $item->reference,
                'oldstatus' => $before->status,
                'newstatus' => $status,
            ]
        );

        return $item;
    }

    public function change_priority(
        int $itemid,
        string $priority,
        int $actorid
    ): \stdClass {
        if (!WorkItemPriority::is_valid($priority)) {
            throw new \InvalidArgumentException(
                'Invalid work item priority.'
            );
        }

        $before = $this->repository->get($itemid);

        $item = $this->repository->update_fields(
            $itemid,
            ['priority' => $priority]
        );

        $this->repository->add_history(
            $itemid,
            $actorid,
            'priority_changed',
            $before->priority,
            $priority
        );

        AdminLog::log(
            AdminEvents::WORK_ITEM_PRIORITY_CHANGED,
            $item->targetuserid !== null
                ? (int)$item->targetuserid
                : null,
            'work_item',
            $itemid,
            [
                'reference' => $item->reference,
                'oldpriority' => $before->priority,
                'newpriority' => $priority,
            ]
        );

        return $item;
    }

    public function assign(
        int $itemid,
        ?int $assigneduserid,
        ?int $assignedteamid,
        int $actorid
    ): \stdClass {

        if (
            $assigneduserid !== null &&
            !$this->repository->user_exists(
                $assigneduserid
            )
        ) {
            throw new \InvalidArgumentException(
                'Assigned user does not exist or is unavailable.'
            );
        }

        if (
            $assignedteamid !== null &&
            !$this->repository->enabled_team_exists(
                $assignedteamid
            )
        ) {
            throw new \InvalidArgumentException(
                'Assigned team does not exist or is disabled.'
            );
        }


        $before = $this->repository->get($itemid);

        $item = $this->repository->update_fields(
            $itemid,
            [
                'assigneduserid' => $assigneduserid,
                'assignedteamid' => $assignedteamid,
            ]
        );

        $this->repository->add_history(
            $itemid,
            $actorid,
            'assignment_changed',
            [
                'userid' => $before->assigneduserid,
                'teamid' => $before->assignedteamid,
            ],
            [
                'userid' => $assigneduserid,
                'teamid' => $assignedteamid,
            ]
        );

        AdminLog::log(
            AdminEvents::WORK_ITEM_ASSIGNED,
            $item->targetuserid !== null
                ? (int)$item->targetuserid
                : null,
            'work_item',
            $itemid,
            [
                'reference' => $item->reference,
                'assigneduserid' => $assigneduserid,
                'assignedteamid' => $assignedteamid,
            ]
        );

        return $item;
    }

    public function add_comment(
        int $itemid,
        int $authorid,
        string $body
    ): int {
        $commentid = $this->repository->add_comment(
            $itemid,
            $authorid,
            $body
        );

        $this->repository->add_history(
            $itemid,
            $authorid,
            'comment_added',
            null,
            null,
            ['commentid' => $commentid]
        );

        AdminLog::log(
            AdminEvents::WORK_ITEM_COMMENT_ADDED,
            null,
            'work_item',
            $itemid,
            [
                'commentid' => $commentid,
            ]
        );

        return $commentid;
    }

    public function link(
        int $itemid,
        string $objecttype,
        int $objectid,
        string $relation,
        int $actorid
    ): int {
        if (!WorkItemRelation::is_valid($relation)) {
            throw new \InvalidArgumentException(
                'Invalid work item relation.'
            );
        }

        $allowedobjecttypes = [
            'inbox_thread',
            'inbox_contact',
            'user',
            'subscription',
            'payment_request',
            'digital_payment_request',
            'automation_rule',
            'intelligence_alert',
            'recommendation',
            'course',
            'work_item',
        ];

        if (
            !in_array(
                $objecttype,
                $allowedobjecttypes,
                true
            )
        ) {
            throw new \InvalidArgumentException(
                'Unsupported work item object type.'
            );
        }

        if ($objectid <= 0) {
            throw new \InvalidArgumentException(
                'Linked object ID must be greater than zero.'
            );
        }        

        $linkid = $this->repository->add_link(
            $itemid,
            $objecttype,
            $objectid,
            $relation
        );

        $this->repository->add_history(
            $itemid,
            $actorid,
            'link_added',
            null,
            [
                'objecttype' => $objecttype,
                'objectid' => $objectid,
                'relation' => $relation,
            ]
        );

        AdminLog::log(
            AdminEvents::WORK_ITEM_LINKED,
            null,
            'work_item',
            $itemid,
            [
                'objecttype' => $objecttype,
                'objectid' => $objectid,
                'relation' => $relation,
            ]
        );

        return $linkid;
    }
}