<?php

namespace local_subscriptions\crm\automation\actions;

use local_subscriptions\crm\automation\AbstractAutomationAction;
use local_subscriptions\crm\automation\AutomationAction;
use local_subscriptions\crm\automation\AutomationActionKeys;
use local_subscriptions\crm\automation\AutomationActionResult;
use local_subscriptions\crm\automation\AutomationContext;
use local_subscriptions\crm\user\UserProfileTagService;
use local_subscriptions\crm\user\UserProfileRepository;

defined('MOODLE_INTERNAL') || die();

final class AddTagAction extends AbstractAutomationAction {

    public function key(): string {
        return AutomationActionKeys::ADD_TAG;
    }

    public function execute(AutomationAction $action, AutomationContext $context): AutomationActionResult {
        if ($context->userid <= 0) {
            return AutomationActionResult::failure('Missing user id.');
        }

        $tagkey = $this->payload_string($action, 'tagkey');

        if ($tagkey === '') {
            return $this->missing_payload('tagkey');
        }

        $service = new UserProfileTagService(new UserProfileRepository());
        $service->add($context->userid, $tagkey, $context->actorid ?: 0, false);

        return AutomationActionResult::success('Tag added.', [
            'userid' => $context->userid,
            'tagkey' => $tagkey,
        ]);
    }
}