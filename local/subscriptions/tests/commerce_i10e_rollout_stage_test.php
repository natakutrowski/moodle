<?php

declare(strict_types=1);
namespace local_subscriptions;
defined('MOODLE_INTERNAL') || die();
use advanced_testcase;
use local_subscriptions\commerce\rollout\CommerceRolloutStageEvaluator;
final class commerce_i10e_rollout_stage_test extends advanced_testcase {
 public function test_runtime_without_shadow_is_reported(): void {
  $state=['runtime_dual_write'=>true,'shadow_compare'=>false,'task_dual_write'=>false,'reconciliation'=>false,'repair'=>false];
  $this->assertContains('runtime dual-write should follow shadow comparison',(new CommerceRolloutStageEvaluator())->violations($state));
 }
}
