<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\producttype;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProductType;

/** Commerce product type reserved for Commerce services. */
final class ServiceProductType extends AbstractCommerceProductType {

    public function __construct() {
        parent::__construct(
            CommerceProductType::SERVICE,
            'Service',
            'i/settings',
            new CommerceProductTypeCapabilities(
                composable: true,
                expandable: false,
                directlypurchasable: true
            )
        );
    }
}
