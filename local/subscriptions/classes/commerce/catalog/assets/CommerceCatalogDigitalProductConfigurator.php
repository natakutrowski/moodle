<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\catalog\assets;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProduct;
use local_subscriptions\commerce\catalog\domain\CommerceProductEntitlementDefinition;
use local_subscriptions\commerce\catalog\domain\CommerceProductType;

/** Builds the canonical Native delivery definition for a digital product. */
final class CommerceCatalogDigitalProductConfigurator {
    /**
     * @param CommerceProductEntitlementDefinition[] $definitions
     * @return CommerceProductEntitlementDefinition[]
     */
    public function with_default_entitlement(CommerceProduct $product, array $definitions): array {
        if ($product->get_type() !== CommerceProductType::DIGITAL_DOWNLOAD) {
            return $definitions;
        }

        foreach ($definitions as $definition) {
            if (!$definition instanceof CommerceProductEntitlementDefinition) {
                throw new \coding_exception('Invalid Commerce entitlement definition collection.');
            }
            if ($definition->get_type() === CommerceProductType::DIGITAL_DOWNLOAD) {
                return $definitions;
            }
        }

        $definitions[] = new CommerceProductEntitlementDefinition(
            $product->get_sku(),
            CommerceProductType::DIGITAL_DOWNLOAD,
            'digital:sku:' . $product->get_sku(),
            null,
            1,
            [],
            count($definitions)
        );

        return $definitions;
    }
}
