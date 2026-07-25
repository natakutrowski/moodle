<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\dualwrite;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\migration\CommerceLegacyNativeComparator;
use local_subscriptions\commerce\migration\CommerceLegacyPurchaseSourceRegistry;
use local_subscriptions\commerce\migration\CommerceLegacySnapshotFactory;
use local_subscriptions\commerce\persistence\sql\CommercePurchaseSqlRepository;

final class CommerceDualWriteService {
    public function __construct(
        private readonly CommerceLegacyPurchaseSourceRegistry $sources,
        private readonly CommerceLegacySnapshotFactory $snapshotfactory,
        private readonly CommercePurchaseSqlRepository $repository,
        private readonly CommerceLegacyNativeComparator $comparator,
        private readonly CommerceDualWriteFeatureToggle $toggle,
        private readonly CommerceDualWriteLogger $logger
    ) {
    }

    public function synchronise(string $family, int $legacyid, string $trigger = 'unspecified'): CommerceDualWriteResult {
        $family = strtolower(trim($family));
        if ($legacyid <= 0) {
            throw new \InvalidArgumentException('A positive Legacy Commerce identifier is required.');
        }

        if (!$this->toggle->is_enabled()) {
            return new CommerceDualWriteResult($family, $legacyid, CommerceDualWriteResult::STATUS_DISABLED);
        }

        try {
            $purchase = $this->sources->get($family)->get_by_id($legacyid);
            if ($purchase === null) {
                $result = new CommerceDualWriteResult(
                    $family,
                    $legacyid,
                    CommerceDualWriteResult::STATUS_SKIPPED,
                    null,
                    [],
                    'The Legacy purchase does not exist or is not yet materialised.'
                );
                $this->logger->log($result, $trigger);
                return $result;
            }

            $expected = $this->snapshotfactory->create($purchase);
            $purchaseuuid = $expected->get_purchase()->get_purchase_uuid();
            $existing = $this->repository->find_by_legacy_reference($family, $legacyid);

            if ($existing !== null) {
                $before = $this->comparator->compare($expected, $existing);
                if ($before->is_equal()) {
                    return new CommerceDualWriteResult(
                        $family,
                        $legacyid,
                        CommerceDualWriteResult::STATUS_UNCHANGED,
                        $purchaseuuid
                    );
                }
            }

            $status = $existing === null
                ? CommerceDualWriteResult::STATUS_CREATED
                : CommerceDualWriteResult::STATUS_UPDATED;

            $this->repository->save($expected);
            $persisted = $this->repository->find_by_legacy_reference($family, $legacyid);
            $after = $this->comparator->compare($expected, $persisted);

            if (!$after->is_equal()) {
                $result = new CommerceDualWriteResult(
                    $family,
                    $legacyid,
                    CommerceDualWriteResult::STATUS_FAILED,
                    $purchaseuuid,
                    $after->get_differences(),
                    'Native snapshot differs after dual-write.'
                );
                $this->logger->log($result, $trigger);
                if ($this->toggle->is_strict()) {
                    throw new \RuntimeException('Commerce dual-write post-write verification failed.');
                }
                return $result;
            }

            return new CommerceDualWriteResult($family, $legacyid, $status, $purchaseuuid);
        } catch (\Throwable $exception) {
            if ($this->toggle->is_strict()) {
                throw $exception;
            }

            $result = new CommerceDualWriteResult(
                $family,
                $legacyid,
                CommerceDualWriteResult::STATUS_FAILED,
                null,
                [],
                $exception->getMessage()
            );
            $this->logger->log($result, $trigger);
            return $result;
        }
    }
}
