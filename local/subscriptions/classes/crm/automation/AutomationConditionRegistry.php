<?php

namespace local_subscriptions\crm\automation;

defined('MOODLE_INTERNAL') || die();

final class AutomationConditionRegistry {

    /** @var AutomationConditionInterface[] */
    private array $conditions = [];

    public function register(AutomationConditionInterface $condition): self {
        $this->conditions[$condition->key()] = $condition;
        return $this;
    }

    public function has(string $key): bool {
        return isset($this->conditions[$key]);
    }

    public function evaluate(AutomationCondition $condition, AutomationContext $context): bool {
        if (!$this->has($condition->key)) {
            return false;
        }

        return $this->conditions[$condition->key]->evaluate($condition, $context);
    }
}