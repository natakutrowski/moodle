<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\read;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\migration\CommerceLegacyNativeComparator;
use local_subscriptions\commerce\migration\CommerceLegacyPurchaseSourceRegistry;
use local_subscriptions\commerce\migration\CommerceLegacySnapshotFactory;
use local_subscriptions\commerce\persistence\CommercePurchasePersistenceSnapshot;
use local_subscriptions\commerce\persistence\sql\CommercePurchaseSqlRepository;

/**
 * I6 shadow-read facade.
 *
 * Legacy remains the returned source of truth. Native is read and compared only.
 */
final class CommerceNativeReadService {
    public function __construct(
        private readonly CommerceLegacyPurchaseSourceRegistry $sources,
        private readonly CommerceLegacySnapshotFactory $snapshotfactory,
        private readonly CommercePurchaseSqlRepository $repository,
        private readonly CommerceLegacyNativeComparator $comparator,
        private readonly CommerceNativeReadFeatureToggle $toggle,
        private readonly CommerceNativeReadMetrics $metrics,
        private readonly CommerceNativeReadLogger $logger
    ) {
    }

    public function read(string $family, int $legacyid, string $trigger = 'unspecified'): CommerceNativeReadResult {
        $family = strtolower(trim($family));
        if ($legacyid <= 0) {
            throw new \InvalidArgumentException('A positive Legacy Commerce identifier is required.');
        }

        $legacystart = microtime(true);
        $purchase = $this->sources->get($family)->get_by_id($legacyid);
        $legacyduration = (microtime(true) - $legacystart) * 1000;

        if ($purchase === null) {
            $result = new CommerceNativeReadResult(
                $family,
                $legacyid,
                CommerceNativeReadResult::STATUS_INVALID_LEGACY,
                null,
                [],
                $legacyduration,
                0.0,
                'The Legacy purchase does not exist or cannot be projected.'
            );
            $this->metrics->record($result->get_status(), $legacyduration, 0.0);
            $this->logger->log($result, $trigger);
            if ($this->toggle->is_strict()) {
                throw new \RuntimeException('Commerce native shadow read cannot project the Legacy purchase.');
            }
            return $result;
        }

        $legacysnapshot = $this->snapshotfactory->create($purchase);
        if (!$this->toggle->is_enabled()) {
            return new CommerceNativeReadResult(
                $family,
                $legacyid,
                CommerceNativeReadResult::STATUS_DISABLED,
                $legacysnapshot,
                [],
                $legacyduration
            );
        }

        $nativestart = microtime(true);
        $nativesnapshot = $this->repository->find_by_legacy_reference($family, $legacyid);
        $nativeduration = (microtime(true) - $nativestart) * 1000;
        $comparison = $this->comparator->compare($legacysnapshot, $nativesnapshot);

        $status = match ($comparison->get_status()) {
            'equal' => CommerceNativeReadResult::STATUS_EQUAL,
            'missing_native' => CommerceNativeReadResult::STATUS_MISSING_NATIVE,
            default => CommerceNativeReadResult::STATUS_DIFFERENT,
        };
        $result = new CommerceNativeReadResult(
            $family,
            $legacyid,
            $status,
            $legacysnapshot,
            $comparison->get_differences(),
            $legacyduration,
            $nativeduration
        );
        $this->metrics->record($status, $legacyduration, $nativeduration);
        $this->logger->log($result, $trigger);

        if ($result->has_issue() && $this->toggle->is_strict()) {
            throw new \RuntimeException('Commerce native shadow read detected a Legacy/native inconsistency.');
        }
        return $result;
    }

    /** Always returns the Legacy-projected snapshot during I6. */
    public function get_snapshot(string $family, int $legacyid, string $trigger = 'unspecified'): ?CommercePurchasePersistenceSnapshot {
        return $this->read($family, $legacyid, $trigger)->get_snapshot();
    }

    public function get_metrics(): CommerceNativeReadMetrics {
        return $this->metrics;
    }
}
