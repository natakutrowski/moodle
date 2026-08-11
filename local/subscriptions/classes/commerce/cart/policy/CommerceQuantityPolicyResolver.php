<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\cart\policy;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProduct;

/** Resolves quantity rules from catalogue metadata, defaulting safely to one unit. */
final class CommerceQuantityPolicyResolver {
    public function resolve(CommerceProduct $product): CommerceQuantityPolicy {
        $metadata = $product->get_metadata();
        $mode = strtolower(trim((string)($metadata['quantity_policy'] ?? 'single')));

        if ($mode === 'multiple' || $mode === 'range') {
            $minimum = max(1, (int)($metadata['min_quantity'] ?? 1));
            $maximumvalue = $metadata['max_quantity'] ?? null;
            $maximum = $maximumvalue === null || $maximumvalue === ''
                ? null
                : (int)$maximumvalue;
            $step = max(1, (int)($metadata['quantity_step'] ?? 1));

            return new CommerceRangeQuantityPolicy($minimum, $maximum, $step);
        }

        return new CommerceSingleQuantityPolicy();
    }
}
