<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\producttype;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProductType;

/** Commerce product type representing a downloadable digital product. */
final class DigitalDownloadProductType extends AbstractCommerceProductType {

    public function __construct() {
        parent::__construct(
            CommerceProductType::DIGITAL_DOWNLOAD,
            'Digital download',
            'i/file',
            new CommerceProductTypeCapabilities(
                composable: true,
                expandable: false,
                directlypurchasable: true
            )
        );
    }
}
