<?php

namespace local_subscriptions\crm\automation;

defined('MOODLE_INTERNAL') || die();

final class AutomationEngine {

    public function __construct(
        private readonly AutomationConditionRegistry $conditions,
        private readonly AutomationActionRegistry $actions,
        private readonly AutomationHistoryRepository $history
    ) {
    }

    /**
     * @param AutomationRule[] $rules
     * @return AutomationExecutionResult[]
     */
    public function run(array $rules, AutomationContext $context): array {
        $results = [];

        foreach ($rules as $rule) {
            $results[] = $this->run_rule($rule, $context);
        }

        return $results;
    }

    public function run_rule(AutomationRule $rule, AutomationContext $context): AutomationExecutionResult {
        if (!$rule->is_enabled()) {
            return AutomationExecutionResult::skipped($rule, $context, 'Rule disabled');
        }

        if (!$rule->matches_trigger($context->triggerkey)) {
            return AutomationExecutionResult::skipped($rule, $context, 'Trigger mismatch');
        }

        if (!$this->conditions_match($rule, $context)) {
            $result = AutomationExecutionResult::skipped($rule, $context, 'Conditions not matched');

            $this->history->record($rule, $context, AutomationStatuses::SKIPPED, $result->message);

            return $result;
        }

        $actionresults = [];
        $success = true;
        $message = '';

        foreach ($rule->actions as $action) {
            $actionresult = $this->actions->execute($action, $context);
            $actionresults[] = $actionresult;

            if (!$actionresult->success) {
                $success = false;
                $message = $actionresult->message;

                if ($action->stoponfailure) {
                    break;
                }
            }
        }

        $result = $success
            ? AutomationExecutionResult::success($rule, $context, $actionresults)
            : AutomationExecutionResult::failure($rule, $context, $actionresults, $message);

        $this->history->record(
            $rule,
            $context,
            $success ? AutomationStatuses::SUCCESS : AutomationStatuses::FAILED,
            $message,
            array_map(static fn(AutomationActionResult $actionresult): array => [
                'success' => $actionresult->success,
                'message' => $actionresult->message,
                'data' => $actionresult->data,
            ], $actionresults)
        );

        return $result;
    }

    private function conditions_match(AutomationRule $rule, AutomationContext $context): bool {
        foreach ($rule->conditions as $condition) {
            if (!$this->conditions->evaluate($condition, $context)) {
                return false;
            }
        }

        return true;
    }
}