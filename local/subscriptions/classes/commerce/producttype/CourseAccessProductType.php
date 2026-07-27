<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\producttype;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\catalog\domain\CommerceProductType;

/** Commerce product type representing course access. */
final class CourseAccessProductType extends AbstractCommerceProductType {

    public function __construct() {
        parent::__construct(
            CommerceProductType::COURSE_ACCESS,
            'Course access',
            'i/course',
            new CommerceProductTypeCapabilities(
                composable: true,
                expandable: false,
                directlypurchasable: true
            )
        );
    }
}
