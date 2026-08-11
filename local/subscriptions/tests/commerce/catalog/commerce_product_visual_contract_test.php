<?php

declare(strict_types=1);

namespace local_subscriptions;

use local_subscriptions\commerce\catalog\visual\CommerceProductVisualAuditService;
use local_subscriptions\commerce\catalog\visual\CommerceProductVisualFormat;

defined('MOODLE_INTERNAL') || die();

final class commerce_product_visual_contract_test
        extends \advanced_testcase {

    public function test_four_visual_formats_are_stable(): void {
        $this->assertSame(
            ['square', 'landscape', 'wide', 'portrait', 'showroom'],
            CommerceProductVisualFormat::all()
        );
        $this->assertSame('landscape', CommerceProductVisualFormat::for_role('storefront'));
        $this->assertSame('wide', CommerceProductVisualFormat::for_role('social'));
        $this->assertSame('portrait', CommerceProductVisualFormat::for_role('resources'));
        $this->assertSame('showroom', CommerceProductVisualFormat::for_role('showroom'));
        $this->assertTrue(CommerceProductVisualFormat::ratio_matches('portrait', 1200, 1500));
        $this->assertTrue(CommerceProductVisualFormat::ratio_matches('showroom', 1920, 1080));


    }

    public function test_placeholder_icons_depend_on_product_type(): void {
        $this->assertSame(
            'fa-solid fa-graduation-cap',
            CommerceProductVisualAuditService::placeholder_icon(
                'course_access'
            )
        );
        $this->assertSame(
            'fa-solid fa-file-arrow-down',
            CommerceProductVisualAuditService::placeholder_icon(
                'digital_download'
            )
        );
        $this->assertSame(
            'fa-solid fa-boxes-stacked',
            CommerceProductVisualAuditService::placeholder_icon(
                'bundle'
            )
        );
    }
}
