<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\producttype;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProductType;

/** Commerce product type representing a composed product. */
final class BundleProductType extends AbstractCommerceProductType {

    public function __construct() {
        parent::__construct(
            CommerceProductType::BUNDLE,
            'Bundle',
            'i/collection',
            new CommerceProductTypeCapabilities(
                composable: true,
                expandable: true,
                directlypurchasable: true,
                supportsentsitlements: false
            )
        );
    }
}
