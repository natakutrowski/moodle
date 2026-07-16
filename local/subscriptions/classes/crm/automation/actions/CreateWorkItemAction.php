<?php

namespace local_subscriptions\crm\automation\actions;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\automation\AbstractAutomationAction;
use local_subscriptions\crm\automation\AutomationAction;
use local_subscriptions\crm\automation\AutomationActionKeys;
use local_subscriptions\crm\automation\AutomationActionResult;
use local_subscriptions\crm\automation\AutomationContext;
use local_subscriptions\crm\work\domain\WorkItemPriority;
use local_subscriptions\crm\work\domain\WorkItemSource;
use local_subscriptions\crm\work\domain\WorkItemType;
use local_subscriptions\crm\work\dto\CreateWorkItemRequest;
use local_subscriptions\crm\work\services\WorkItemService;

final class CreateWorkItemAction extends AbstractAutomationAction {

    public function key(): string {
        return AutomationActionKeys::CREATE_WORK_ITEM;
    }

    public function execute(
        AutomationAction $action,
        AutomationContext $context
    ): AutomationActionResult {
        if ($context->userid <= 0) {
            return AutomationActionResult::failure('Missing user id.');
        }

        $title = $this->payload_string($action, 'title');
        $description = $this->payload_string($action, 'description');
        $type = $this->payload_string($action, 'type', WorkItemType::FOLLOW_UP);
        $priority = $this->payload_string($action, 'priority', WorkItemPriority::NORMAL);
        $assignedteamid = (int)$this->payload_string($action, 'assignedteamid', '0');
        $assigneduserid = (int)$this->payload_string($action, 'assigneduserid', '0');

        if ($title === '') {
            return $this->missing_payload('title');
        }

        if (!WorkItemType::is_valid($type) || !WorkItemPriority::is_valid($priority)) {
            return AutomationActionResult::failure('Invalid Work Item configuration.');
        }

        // Duplicate guard for the same automation context and title.
        global $DB;
        $duplicate = $DB->record_exists_select(
            'local_subscriptions_work_item',
            'targetuserid = :userid
             AND source = :source
             AND title = :title
             AND status IN (:open, :progress, :blocked, :waiting)',
            [
                'userid' => $context->userid,
                'source' => WorkItemSource::AUTOMATION,
                'title' => $title,
                'open' => 'open',
                'progress' => 'in_progress',
                'blocked' => 'blocked',
                'waiting' => 'waiting',
            ]
        );

        if ($duplicate) {
            return AutomationActionResult::success('Existing active Work Item reused.', [
                'userid' => $context->userid,
                'duplicateprevented' => true,
            ]);
        }

        $item = (new WorkItemService())->create(new CreateWorkItemRequest(
            $title,
            $description,
            $type,
            $priority,
            WorkItemSource::AUTOMATION,
            $context->actorid > 0 ? $context->actorid : (int)get_admin()->id,
            $context->userid,
            $assigneduserid > 0 ? $assigneduserid : null,
            $assignedteamid > 0 ? $assignedteamid : null
        ));

        return AutomationActionResult::success('Work Item created.', [
            'userid' => $context->userid,
            'workitemid' => (int)$item->id,
            'reference' => $item->reference,
        ]);
    }
}