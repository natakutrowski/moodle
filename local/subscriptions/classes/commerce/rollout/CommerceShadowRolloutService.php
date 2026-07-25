<?php

declare(strict_types=1);
namespace local_subscriptions\commerce\rollout;
defined('MOODLE_INTERNAL') || die();
use local_subscriptions\commerce\reconciliation\CommerceReconciliationFactory;
final class CommerceShadowRolloutService {
    public function compare(string $family, array $ids): CommerceShadowRolloutReport {
        if (!in_array($family, ['digital', 'subscription'], true)) { throw new \InvalidArgumentException('Unsupported family.'); }
        $service = CommerceReconciliationFactory::create(); $rows = [];
        foreach (array_values(array_unique(array_map('intval', $ids))) as $id) {
            if ($id <= 0) { continue; }
            $result = $service->reconcile($family, $id, false);
            $rows[] = ['family' => $family, 'id' => $id, 'equal' => $result->is_equal(),
                'issues' => count($result->get_issues()), 'repaired' => $result->was_repaired()];
        }
        return new CommerceShadowRolloutReport($rows);
    }
    public function assert_safe_flags(): void {
        (new CommerceRolloutGuard())->assert_safe_configuration();
        if (!empty(get_config('local_subscriptions', 'commerce_native_repair_enabled'))) {
            throw new \RuntimeException('Shadow rollout comparison must run with repair disabled.');
        }
    }
}
