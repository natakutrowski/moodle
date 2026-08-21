<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_media_j13e3_test extends \advanced_testcase {
    public function test_admin_and_resolver_support_showroom_specific_media(): void {
        global $CFG;

        $admin = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/products/storefront_builder.php'
        );
        $resolver = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/showroom/CommerceShowroomProductResolver.php'
        );
        $service = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/showroom/CommerceShowroomMediaService.php'
        );

        self::assertIsString($admin);
        self::assertIsString($resolver);
        self::assertIsString($service);
        self::assertStringContainsString('storefront_showroom_file', $admin);
        self::assertStringContainsString('storefront_showroom_key', $admin);
        self::assertStringContainsString("public const SLOT = 'showroom';", $service);
        self::assertStringContainsString("!empty(\$showroommedia['hasimage'])", $resolver);
    }
}
