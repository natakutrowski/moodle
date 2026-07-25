<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\migration;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\persistence\sql\CommercePurchaseSqlRepository;

/** Idempotent orchestration service for Legacy-to-native Commerce migration. */
final class CommerceLegacyPurchaseMigrator {
    public function __construct(
        private readonly CommerceLegacyPurchaseSourceRegistry $sources,
        private readonly CommerceLegacySnapshotFactory $snapshotfactory,
        private readonly CommercePurchaseSqlRepository $repository,
        private readonly CommerceLegacyNativeComparator $comparator
    ) {
    }

    public function migrate_one(string $family, int $legacyid, bool $execute = false): CommerceLegacyMigrationResult {
        $family = strtolower(trim($family));
        if ($legacyid <= 0) {
            throw new \InvalidArgumentException('A Legacy Commerce identifier must be positive.');
        }

        try {
            $purchase = $this->sources->get($family)->get_by_id($legacyid);
            if ($purchase === null) {
                return $this->result_with_issue(
                    $family,
                    $legacyid,
                    CommerceLegacyMigrationResult::STATUS_SKIPPED,
                    'legacy_not_found',
                    'The Legacy Commerce purchase does not exist.',
                    CommerceLegacyMigrationIssue::SEVERITY_WARNING
                );
            }

            $expected = $this->snapshotfactory->create($purchase);
            $purchaserecord = $expected->get_purchase();
            $existing = $this->repository->find_by_legacy_reference($family, $legacyid);

            if ($existing !== null) {
                $comparison = $this->comparator->compare($expected, $existing);
                if ($comparison->is_equal()) {
                    return new CommerceLegacyMigrationResult(
                        $family,
                        $legacyid,
                        CommerceLegacyMigrationResult::STATUS_ALREADY_PRESENT,
                        $purchaserecord->get_purchase_uuid(),
                        $purchaserecord->get_reference()
                    );
                }

                return new CommerceLegacyMigrationResult(
                    $family,
                    $legacyid,
                    CommerceLegacyMigrationResult::STATUS_INVALID,
                    $purchaserecord->get_purchase_uuid(),
                    $purchaserecord->get_reference(),
                    [new CommerceLegacyMigrationIssue(
                        'native_snapshot_mismatch',
                        'A native purchase already exists but differs from the current Legacy projection.',
                        CommerceLegacyMigrationIssue::SEVERITY_ERROR,
                        ['differences' => $comparison->get_differences()]
                    )]
                );
            }

            if (!$execute) {
                return new CommerceLegacyMigrationResult(
                    $family,
                    $legacyid,
                    CommerceLegacyMigrationResult::STATUS_DRY_RUN,
                    $purchaserecord->get_purchase_uuid(),
                    $purchaserecord->get_reference()
                );
            }

            $this->repository->save($expected);
            $persisted = $this->repository->find_by_legacy_reference($family, $legacyid);
            $comparison = $this->comparator->compare($expected, $persisted);
            if (!$comparison->is_equal()) {
                // I4D.9 safety: never leave a newly-created, uncertified
                // native aggregate behind after post-write verification.
                $this->repository->delete_by_legacy_reference($family, $legacyid);

                return new CommerceLegacyMigrationResult(
                    $family,
                    $legacyid,
                    CommerceLegacyMigrationResult::STATUS_FAILED,
                    $purchaserecord->get_purchase_uuid(),
                    $purchaserecord->get_reference(),
                    [new CommerceLegacyMigrationIssue(
                        'post_write_verification_failed',
                        'The native snapshot could not be certified after writing.',
                        CommerceLegacyMigrationIssue::SEVERITY_ERROR,
                        ['differences' => $comparison->get_differences()]
                    )]
                );
            }

            return new CommerceLegacyMigrationResult(
                $family,
                $legacyid,
                CommerceLegacyMigrationResult::STATUS_MIGRATED,
                $purchaserecord->get_purchase_uuid(),
                $purchaserecord->get_reference()
            );
        } catch (\coding_exception|\InvalidArgumentException $exception) {
            return $this->result_with_issue(
                $family,
                $legacyid,
                CommerceLegacyMigrationResult::STATUS_INVALID,
                'invalid_legacy_purchase',
                $exception->getMessage(),
                CommerceLegacyMigrationIssue::SEVERITY_ERROR,
                ['exception' => $exception::class]
            );
        } catch (\Throwable $exception) {
            return $this->result_with_issue(
                $family,
                $legacyid,
                CommerceLegacyMigrationResult::STATUS_FAILED,
                'migration_exception',
                $exception->getMessage(),
                CommerceLegacyMigrationIssue::SEVERITY_ERROR,
                ['exception' => $exception::class]
            );
        }
    }

    /** @param int[] $legacyids */
    public function migrate_batch(string $family, array $legacyids, bool $execute = false): CommerceLegacyMigrationSummary {
        $summary = new CommerceLegacyMigrationSummary();
        foreach (array_values(array_unique(array_map('intval', $legacyids))) as $legacyid) {
            if ($legacyid <= 0) {
                continue;
            }
            $summary->add($this->migrate_one($family, $legacyid, $execute));
        }
        return $summary;
    }

    private function result_with_issue(
        string $family,
        int $legacyid,
        string $status,
        string $code,
        string $message,
        string $severity,
        array $context = []
    ): CommerceLegacyMigrationResult {
        return new CommerceLegacyMigrationResult(
            $family,
            $legacyid,
            $status,
            null,
            null,
            [new CommerceLegacyMigrationIssue($code, $message, $severity, $context)]
        );
    }
}
