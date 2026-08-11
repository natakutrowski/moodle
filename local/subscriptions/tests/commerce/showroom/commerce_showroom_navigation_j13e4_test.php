<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_navigation_j13e4_test extends \advanced_testcase {
    public function test_storefront_and_showroom_are_linked_with_tracking(): void {
        global $CFG;

        $controller = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/storefront_product.php'
        );
        $panel = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/storefront/product_commerce_panel.mustache'
        );
        $resolver = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/showroom/CommerceShowroomProductResolver.php'
        );

        self::assertIsString($controller);
        self::assertIsString($panel);
        self::assertIsString($resolver);
        self::assertStringContainsString('CommerceShowroomProductLinkService', $controller);
        self::assertStringContainsString('{{#hasshowroom}}', $panel);
        self::assertStringContainsString("'source' => 'showroom'", $resolver);
        self::assertStringContainsString("'source' => 'storefront'", file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/showroom/CommerceShowroomProductLinkService.php'
        ));
    }
}
