<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\cart\ownership;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\repository\CommerceProductComponentRepository;

/** Evaluates effective ownership of the child products contained in a bundle. */
final class CommerceBundlePurchaseEligibilityService {
    public function __construct(
        private readonly CommerceProductComponentRepository $components,
        private readonly CommerceCartOwnershipGateway $ownership
    ) {
    }

    public function evaluate(int $customerid, string $bundlesku): CommerceBundlePurchaseEligibility {
        $components = $this->components->find_by_parent_sku($bundlesku);
        if ($components === [] || $customerid <= 0) {
            return new CommerceBundlePurchaseEligibility(count($components), []);
        }

        $owned = [];
        foreach ($components as $component) {
            $childsku = $component->get_child_product_sku();
            if ($this->ownership->owns($customerid, $childsku)) {
                $owned[] = $childsku;
            }
        }

        return new CommerceBundlePurchaseEligibility(count($components), array_values(array_unique($owned)));
    }
}
