<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\certification;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\persistence\CommercePurchasePersistenceSnapshot;

/** Computes a stable SHA-256 fingerprint for a persistence snapshot. */
final class CommercePersistenceSnapshotHasher {
    public function __construct(
        private readonly CommercePersistenceSnapshotCanonicalizer $canonicalizer = new CommercePersistenceSnapshotCanonicalizer()
    ) {
    }

    public function hash(CommercePurchasePersistenceSnapshot $snapshot): string {
        $json = json_encode(
            $this->canonicalizer->canonicalize($snapshot),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR
        );
        return hash('sha256', $json);
    }
}
