<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\shadow\reporting;

defined('MOODLE_INTERNAL') || die();

/** Search filters for Commerce Shadow persisted runs. */
final class CommerceShadowSearchCriteria {
    public function __construct(
        public readonly ?string $purchasereference = null,
        public readonly ?string $source = null,
        public readonly ?string $entrypoint = null,
        public readonly ?string $comparisonstatus = null,
        public readonly ?string $classification = null,
        public readonly ?int $beneficiaryuserid = null,
        public readonly ?string $family = null,
        public readonly int $limit = 50,
        public readonly int $offset = 0
    ) {
        if ($limit < 1 || $limit > 1000) {
            throw new \coding_exception('Commerce Shadow search limit must be between 1 and 1000.');
        }
        if ($offset < 0) {
            throw new \coding_exception('Commerce Shadow search offset cannot be negative.');
        }
    }
}
