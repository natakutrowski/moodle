<?php

declare(strict_types=1);
namespace local_subscriptions\commerce\rollout;
defined('MOODLE_INTERNAL') || die();
final class CommerceRolloutStageEvaluator {
    public function current(array $state): string {
        if (!empty($state['repair'])) return CommerceRolloutStage::REPAIR;
        if (!empty($state['reconciliation'])) return CommerceRolloutStage::RECONCILIATION;
        if (!empty($state['task_dual_write'])) return CommerceRolloutStage::TASKS;
        if (!empty($state['runtime_dual_write'])) return CommerceRolloutStage::RUNTIME;
        if (!empty($state['shadow_compare'])) return CommerceRolloutStage::SHADOW;
        return CommerceRolloutStage::BASELINE;
    }
    public function violations(array $state): array {
        $v=[];
        if (!empty($state['repair']) && empty($state['reconciliation'])) $v[]='repair requires reconciliation';
        if (!empty($state['reconciliation']) && empty($state['runtime_dual_write'])) $v[]='reconciliation requires runtime dual-write';
        if (!empty($state['task_dual_write']) && empty($state['runtime_dual_write'])) $v[]='task dual-write requires runtime dual-write';
        if (!empty($state['runtime_dual_write']) && empty($state['shadow_compare'])) $v[]='runtime dual-write should follow shadow comparison';
        return $v;
    }
}
