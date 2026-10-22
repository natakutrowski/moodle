<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\producttype;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProductType;

/** Commerce product type representing a voluntary contribution without entitlements. */
final class DonationProductType extends AbstractCommerceProductType {

    public function __construct() {
        parent::__construct(
            CommerceProductType::DONATION,
            'Donation',
            'i/heart',
            new CommerceProductTypeCapabilities(
                composable: false,
                expandable: false,
                directlypurchasable: true,
                supportsentsitlements: false
            )
        );
    }
}
