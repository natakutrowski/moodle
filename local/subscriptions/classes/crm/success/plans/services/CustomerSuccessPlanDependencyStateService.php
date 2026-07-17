<?php

namespace local_subscriptions\crm\success\plans\services;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\success\plans\domain\CustomerSuccessPlanStepStatus;
use local_subscriptions\crm\success\plans\dto\CustomerSuccessPlanStep;

/**
 * Evaluates persisted step dependencies.
 */
final class CustomerSuccessPlanDependencyStateService {

    /**
     * @param CustomerSuccessPlanStep[] $steps
     * @return array<int,CustomerSuccessPlanStep>
     */
    public function index_by_id(
        array $steps
    ): array {
        $result = [];

        foreach ($steps as $step) {
            if (!$step instanceof CustomerSuccessPlanStep) {
                throw new \InvalidArgumentException(
                    'Dependency state service requires CustomerSuccessPlanStep objects.'
                );
            }

            $result[$step->id] = $step;
        }

        return $result;
    }

    /**
     * @param array<int,CustomerSuccessPlanStep> $stepsbyid
     */
    public function dependency_is_satisfied(
        CustomerSuccessPlanStep $step,
        array $stepsbyid
    ): bool {
        if ($step->dependsonstepid === null) {
            return true;
        }

        $dependency =
            $stepsbyid[$step->dependsonstepid] ?? null;

        if ($dependency === null) {
            return false;
        }

        return in_array(
            $dependency->status,
            [
                CustomerSuccessPlanStepStatus::COMPLETED,
                CustomerSuccessPlanStepStatus::SKIPPED,
            ],
            true
        );
    }

    /**
     * @param CustomerSuccessPlanStep[] $steps
     */
    public function all_terminal(
        array $steps
    ): bool {
        if ($steps === []) {
            return false;
        }

        foreach ($steps as $step) {
            if (
                !CustomerSuccessPlanStepStatus::is_terminal(
                    $step->status
                )
            ) {
                return false;
            }
        }

        return true;
    }
}