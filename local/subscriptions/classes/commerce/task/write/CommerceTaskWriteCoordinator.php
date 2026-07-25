<?php

declare(strict_types=1);
namespace local_subscriptions\commerce\task\write;
defined('MOODLE_INTERNAL') || die();
use local_subscriptions\commerce\command\dto\CommerceCommandRequest;
use local_subscriptions\commerce\command\policy\CommerceWritePolicy;
use local_subscriptions\commerce\command\service\CommercePurchaseCommandService;
final class CommerceTaskWriteCoordinator {
    public function __construct(private readonly CommercePurchaseCommandService $commands, private readonly CommerceWritePolicy $policy) {}
    public function synchronise(array $references, string $trigger): CommerceTaskWriteResult {
        if (!$this->policy->native_dual_write_enabled('task')) { return new CommerceTaskWriteResult(); }
        $ok = 0; $failed = 0;
        foreach ($references as $reference) {
            $family = (string)($reference['family'] ?? ''); $legacyid = (int)($reference['legacyid'] ?? 0);
            if (!in_array($family, ['subscription', 'digital'], true) || $legacyid <= 0) { $failed++; continue; }
            $result = $this->commands->synchronise(new CommerceCommandRequest($family, $legacyid, $trigger, 'task', $trigger . ':' . $family . ':' . $legacyid));
            $result->is_successful() ? $ok++ : $failed++;
        }
        return new CommerceTaskWriteResult(count($references), $ok, $failed);
    }
}
