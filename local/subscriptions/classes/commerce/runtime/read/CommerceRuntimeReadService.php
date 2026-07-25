<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\runtime\read;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\migration\CommerceLegacyNativeComparator;
use local_subscriptions\commerce\migration\CommerceLegacyPurchaseSourceRegistry;
use local_subscriptions\commerce\migration\CommerceLegacySnapshotFactory;
use local_subscriptions\commerce\persistence\CommercePurchasePersistenceSnapshot;
use local_subscriptions\commerce\persistence\sql\CommercePurchaseSqlRepository;

/**
 * I7 runtime read switch.
 *
 * This service is intentionally not wired into CRM/student screens yet. I8 and I9
 * will migrate consumers to this single facade after the strategy is certified.
 */
final class CommerceRuntimeReadService {
    public function __construct(
        private readonly CommerceLegacyPurchaseSourceRegistry $sources,
        private readonly CommerceLegacySnapshotFactory $snapshotfactory,
        private readonly CommercePurchaseSqlRepository $repository,
        private readonly CommerceLegacyNativeComparator $comparator,
        private readonly CommerceRuntimeReadFeatureToggle $toggle,
        private readonly CommerceRuntimeReadMetrics $metrics,
        private readonly CommerceRuntimeReadLogger $logger
    ) {
    }

    public function read(string $family, int $legacyid, string $trigger = 'unspecified'): CommerceRuntimeReadResult {
        $family = strtolower(trim($family));
        if ($legacyid <= 0) { throw new \InvalidArgumentException('A positive Legacy Commerce identifier is required.'); }
        $mode = $this->toggle->get_mode();
        $result = match ($mode) {
            CommerceRuntimeReadMode::LEGACY => $this->read_legacy($family, $legacyid, $mode),
            CommerceRuntimeReadMode::SHADOW => $this->read_shadow($family, $legacyid, $mode),
            CommerceRuntimeReadMode::NATIVE => $this->read_native($family, $legacyid, $mode),
            CommerceRuntimeReadMode::AUTO => $this->read_auto($family, $legacyid, $mode),
        };
        $this->metrics->record($result);
        $this->logger->log($result, $trigger);
        if ($this->toggle->is_strict() && $result->has_issue()) {
            throw new \RuntimeException('Commerce runtime read issue: ' . $result->get_status());
        }
        return $result;
    }

    public function get_snapshot(string $family, int $legacyid, string $trigger = 'unspecified'): ?CommercePurchasePersistenceSnapshot {
        return $this->read($family, $legacyid, $trigger)->get_snapshot();
    }

    public function get_metrics(): CommerceRuntimeReadMetrics { return $this->metrics; }

    private function read_legacy(string $family, int $legacyid, string $mode): CommerceRuntimeReadResult {
        [$snapshot, $duration, $message] = $this->load_legacy($family, $legacyid);
        return new CommerceRuntimeReadResult($family, $legacyid, $mode,
            $snapshot ? CommerceRuntimeReadResult::STATUS_OK : CommerceRuntimeReadResult::STATUS_INVALID,
            $snapshot ? CommerceRuntimeReadResult::SOURCE_LEGACY : CommerceRuntimeReadResult::SOURCE_NONE,
            $snapshot, false, [], $duration, 0.0, $message);
    }

    private function read_native(string $family, int $legacyid, string $mode): CommerceRuntimeReadResult {
        [$snapshot, $duration, $message] = $this->load_native($family, $legacyid);
        return new CommerceRuntimeReadResult($family, $legacyid, $mode,
            $snapshot ? CommerceRuntimeReadResult::STATUS_OK : CommerceRuntimeReadResult::STATUS_MISSING,
            $snapshot ? CommerceRuntimeReadResult::SOURCE_NATIVE : CommerceRuntimeReadResult::SOURCE_NONE,
            $snapshot, false, [], 0.0, $duration, $message);
    }

    private function read_shadow(string $family, int $legacyid, string $mode): CommerceRuntimeReadResult {
        [$legacy, $legacyduration, $legacymessage] = $this->load_legacy($family, $legacyid);
        if ($legacy === null) {
            return new CommerceRuntimeReadResult($family, $legacyid, $mode, CommerceRuntimeReadResult::STATUS_INVALID,
                CommerceRuntimeReadResult::SOURCE_NONE, null, false, [], $legacyduration, 0.0, $legacymessage);
        }
        [$native, $nativeduration, $nativemessage] = $this->load_native($family, $legacyid);
        if ($native === null) {
            return new CommerceRuntimeReadResult($family, $legacyid, $mode, CommerceRuntimeReadResult::STATUS_MISSING,
                CommerceRuntimeReadResult::SOURCE_LEGACY, $legacy, false, [], $legacyduration, $nativeduration, $nativemessage);
        }
        $comparison = $this->comparator->compare($legacy, $native);
        $different = $comparison->get_status() !== 'equal';
        return new CommerceRuntimeReadResult($family, $legacyid, $mode,
            $different ? CommerceRuntimeReadResult::STATUS_DIFFERENT : CommerceRuntimeReadResult::STATUS_OK,
            CommerceRuntimeReadResult::SOURCE_LEGACY, $legacy, false, $comparison->get_differences(),
            $legacyduration, $nativeduration);
    }

    private function read_auto(string $family, int $legacyid, string $mode): CommerceRuntimeReadResult {
        [$native, $nativeduration, $nativemessage] = $this->load_native($family, $legacyid);
        if ($native !== null) {
            return new CommerceRuntimeReadResult($family, $legacyid, $mode, CommerceRuntimeReadResult::STATUS_OK,
                CommerceRuntimeReadResult::SOURCE_NATIVE, $native, false, [], 0.0, $nativeduration);
        }
        [$legacy, $legacyduration, $legacymessage] = $this->load_legacy($family, $legacyid);
        if ($legacy !== null) {
            return new CommerceRuntimeReadResult($family, $legacyid, $mode, CommerceRuntimeReadResult::STATUS_FALLBACK,
                CommerceRuntimeReadResult::SOURCE_LEGACY, $legacy, true, [], $legacyduration, $nativeduration,
                $nativemessage ?? 'Native snapshot unavailable.');
        }
        return new CommerceRuntimeReadResult($family, $legacyid, $mode, CommerceRuntimeReadResult::STATUS_INVALID,
            CommerceRuntimeReadResult::SOURCE_NONE, null, true, [], $legacyduration, $nativeduration,
            $legacymessage ?? $nativemessage);
    }

    private function load_legacy(string $family, int $legacyid): array {
        $start = microtime(true);
        try {
            $purchase = $this->sources->get($family)->get_by_id($legacyid);
            $snapshot = $purchase === null ? null : $this->snapshotfactory->create($purchase);
            return [$snapshot, (microtime(true) - $start) * 1000, $snapshot ? null : 'Legacy purchase unavailable.'];
        } catch (\Throwable $exception) {
            return [null, (microtime(true) - $start) * 1000, $exception->getMessage()];
        }
    }

    private function load_native(string $family, int $legacyid): array {
        $start = microtime(true);
        try {
            $snapshot = $this->repository->find_by_legacy_reference($family, $legacyid);
            return [$snapshot, (microtime(true) - $start) * 1000, $snapshot ? null : 'Native snapshot unavailable.'];
        } catch (\Throwable $exception) {
            return [null, (microtime(true) - $start) * 1000, $exception->getMessage()];
        }
    }
}
