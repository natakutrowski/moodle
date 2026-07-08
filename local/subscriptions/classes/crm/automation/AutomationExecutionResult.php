<?php

namespace local_subscriptions\crm\automation;

defined('MOODLE_INTERNAL') || die();

final class AutomationExecutionResult {

    /**
     * @param AutomationActionResult[] $actionresults
     */
    public function __construct(
        public readonly AutomationRule $rule,
        public readonly AutomationContext $context,
        public readonly bool $success,
        public readonly array $actionresults = [],
        public readonly string $message = ''
    ) {
    }

    public static function skipped(AutomationRule $rule, AutomationContext $context, string $message): self {
        return new self($rule, $context, true, [], $message);
    }

    public static function success(AutomationRule $rule, AutomationContext $context, array $actionresults = []): self {
        return new self($rule, $context, true, $actionresults);
    }

    public static function failure(
        AutomationRule $rule,
        AutomationContext $context,
        array $actionresults = [],
        string $message = ''
    ): self {
        return new self($rule, $context, false, $actionresults, $message);
    }
}