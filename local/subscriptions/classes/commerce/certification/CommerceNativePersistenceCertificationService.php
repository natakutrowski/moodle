<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\certification;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\migration\CommerceLegacyNativeComparator;
use local_subscriptions\commerce\migration\CommerceLegacyPurchaseSourceRegistry;
use local_subscriptions\commerce\migration\CommerceLegacySnapshotFactory;
use local_subscriptions\commerce\persistence\sql\CommercePurchaseSqlRepository;

/** Certifies the complete Legacy -> SQL -> native snapshot round trip. */
final class CommerceNativePersistenceCertificationService {
    public function __construct(
        private readonly CommerceLegacyPurchaseSourceRegistry $sources,
        private readonly CommerceLegacySnapshotFactory $snapshotfactory,
        private readonly CommercePurchaseSqlRepository $repository,
        private readonly CommerceLegacyNativeComparator $comparator,
        private readonly CommercePersistenceSnapshotHasher $hasher
    ) {
    }

    public function certify_one(string $family, int $legacyid, bool $cleanup = true): CommercePersistenceCertificationResult {
        $started = microtime(true);
        $family = strtolower(trim($family));
        $created = false;
        $cleanedup = false;
        $purchaseuuid = null;
        $reference = null;
        $expectedhash = null;
        $actualhash = null;

        try {
            if ($legacyid <= 0) {
                throw new \InvalidArgumentException('A Legacy Commerce identifier must be positive.');
            }

            $purchase = $this->sources->get($family)->get_by_id($legacyid);
            if ($purchase === null) {
                return new CommercePersistenceCertificationResult(
                    $family,
                    $legacyid,
                    CommercePersistenceCertificationResult::STATUS_SKIPPED,
                    null,
                    null,
                    null,
                    null,
                    $this->duration_ms($started),
                    false,
                    false,
                    [],
                    'The Legacy Commerce purchase does not exist.'
                );
            }

            $expected = $this->snapshotfactory->create($purchase);
            $purchaseuuid = $expected->get_purchase()->get_purchase_uuid();
            $reference = $expected->get_purchase()->get_reference();
            $expectedhash = $this->hasher->hash($expected);

            $actual = $this->repository->find_by_legacy_reference($family, $legacyid);
            if ($actual === null) {
                $this->repository->save($expected);
                $created = true;
                $actual = $this->repository->find_by_legacy_reference($family, $legacyid);
            }

            $comparison = $this->comparator->compare($expected, $actual);
            if ($actual !== null) {
                $actualhash = $this->hasher->hash($actual);
            }

            if ($created && $cleanup) {
                $cleanedup = $this->repository->delete_by_legacy_reference($family, $legacyid);
            }

            return new CommercePersistenceCertificationResult(
                $family,
                $legacyid,
                $comparison->is_equal()
                    ? CommercePersistenceCertificationResult::STATUS_CERTIFIED
                    : CommercePersistenceCertificationResult::STATUS_DIFFERENT,
                $purchaseuuid,
                $reference,
                $expectedhash,
                $actualhash,
                $this->duration_ms($started),
                $created,
                $cleanedup,
                $comparison->get_differences()
            );
        } catch (\Throwable $exception) {
            if ($created && $cleanup) {
                try {
                    $cleanedup = $this->repository->delete_by_legacy_reference($family, $legacyid);
                } catch (\Throwable) {
                    $cleanedup = false;
                }
            }

            return new CommercePersistenceCertificationResult(
                $family,
                $legacyid,
                CommercePersistenceCertificationResult::STATUS_FAILED,
                $purchaseuuid,
                $reference,
                $expectedhash,
                $actualhash,
                $this->duration_ms($started),
                $created,
                $cleanedup,
                [],
                $exception->getMessage()
            );
        }
    }

    /** @param int[] $legacyids */
    public function certify_batch(string $family, array $legacyids, bool $cleanup = true): CommercePersistenceCertificationSummary {
        $summary = new CommercePersistenceCertificationSummary();
        foreach (array_values(array_unique(array_map('intval', $legacyids))) as $legacyid) {
            if ($legacyid > 0) {
                $summary->add($this->certify_one($family, $legacyid, $cleanup));
            }
        }
        return $summary;
    }

    private function duration_ms(float $started): int {
        return max(0, (int)round((microtime(true) - $started) * 1000));
    }
}
