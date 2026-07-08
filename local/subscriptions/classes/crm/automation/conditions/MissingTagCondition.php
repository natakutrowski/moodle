<?php

namespace local_subscriptions\crm\automation\conditions;

use local_subscriptions\crm\automation\AbstractAutomationCondition;
use local_subscriptions\crm\automation\AutomationCondition;
use local_subscriptions\crm\automation\AutomationConditionKeys;
use local_subscriptions\crm\automation\AutomationContext;
use local_subscriptions\crm\user\UserProfileTagService;
use local_subscriptions\crm\user\UserProfileRepository;

defined('MOODLE_INTERNAL') || die();

final class MissingTagCondition extends AbstractAutomationCondition {

    public function key(): string {
        return AutomationConditionKeys::MISSING_TAG;
    }

    public function evaluate(AutomationCondition $condition, AutomationContext $context): bool {
        if ($context->userid <= 0) {
            return false;
        }

        $tagkey = $this->payload_string($condition, 'tagkey');

        if ($tagkey === '') {
            return false;
        }

        $service = new UserProfileTagService(new UserProfileRepository());
        $tags = $service->get_for_profile($context->userid);

        foreach ($tags as $tag) {
            if ($tag->tag === $tagkey) {
                return $this->apply_negation($condition, false);
            }
        }

        return $this->apply_negation($condition, true);
    }
}