<?php

namespace local_subscriptions\crm\automation\actions;

use local_subscriptions\crm\automation\AbstractAutomationAction;
use local_subscriptions\crm\automation\AutomationAction;
use local_subscriptions\crm\automation\AutomationActionKeys;
use local_subscriptions\crm\automation\AutomationActionResult;
use local_subscriptions\crm\automation\AutomationContext;
use local_subscriptions\crm\user\UserProfileNoteService;
use local_subscriptions\crm\user\UserProfileRepository;

defined('MOODLE_INTERNAL') || die();

final class CreateNoteAction extends AbstractAutomationAction {

    public function key(): string {
        return AutomationActionKeys::CREATE_NOTE;
    }

    public function execute(AutomationAction $action, AutomationContext $context): AutomationActionResult {
        if ($context->userid <= 0) {
            return AutomationActionResult::failure('Missing user id.');
        }

        $content = $this->payload_string($action, 'content');
        $type = $this->payload_string($action, 'type', 'general');

        if ($content === '') {
            return $this->missing_payload('content');
        }

        $service = new UserProfileNoteService(new UserProfileRepository());
        $noteid = $service->add($context->userid, $context->actorid ?: 0, $content, $type, false);

        return AutomationActionResult::success('Note created.', [
            'userid' => $context->userid,
            'noteid' => $noteid,
            'source' => 'automation',
            'type' => $type,
        ]);
    }
}