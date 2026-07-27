<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\shadow\runtime;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\shadow\CommerceShadowComparator;
use local_subscriptions\commerce\shadow\CommerceShadowDivergenceClassifier;
use local_subscriptions\commerce\shadow\CommerceShadowExecutionService;
use local_subscriptions\commerce\shadow\persistence\CommerceShadowPersistenceRepository;

/** G4-G6 orchestration: observe Legacy, simulate Native, compare and persist. */
final class CommerceShadowRuntimeService {
    public function __construct(
        private readonly CommerceLegacyObservationCollector $legacy,
        private readonly CommerceShadowExecutionService $native,
        private readonly CommerceShadowComparator $comparator,
        private readonly CommerceShadowDivergenceClassifier $classifier,
        private readonly CommerceShadowPersistenceRepository $repository
    ) {
    }

    public function run(string $purchasereference, string $source, string $entrypoint, ?int $actoruserid = null): int {
        $legacy = $this->legacy->collect($purchasereference, $source);
        $native = $this->native->execute($purchasereference, $source, $actoruserid);
        $comparison = $this->comparator->compare($legacy, $native);
        return $this->repository->save(
            $entrypoint,
            $legacy,
            $native,
            $comparison,
            $this->classifier->classify($comparison)
        );
    }
}
