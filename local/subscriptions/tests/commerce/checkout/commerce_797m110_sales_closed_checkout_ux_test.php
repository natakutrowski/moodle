<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/**
 * 7.97M1.10.1: an expected sales-window closure is a business state, not a
 * generic checkout preparation failure.
 */
final class commerce_797m110_sales_closed_checkout_ux_test extends \advanced_testcase {
    public function test_checkout_maps_sales_closed_to_specific_business_notice(): void {
        $source = file_get_contents(__DIR__ . '/../../../commerce_checkout.php');

        $this->assertIsString($source);
        $this->assertStringContainsString(
            'CommercePedagogicalSeatReservationException',
            $source
        );
        $this->assertStringContainsString(
            'CommercePedagogicalCapacityService::SALES_CLOSED',
            $source
        );
        $this->assertStringContainsString(
            "get_string('commerce_capacity_sales_closed', 'local_subscriptions')",
            $source
        );
        $this->assertStringContainsString(
            "get_string('commerce_checkout_prepare_error_reference', 'local_subscriptions', \$reference)",
            $source
        );

        $specific = strpos($source, 'CommercePedagogicalCapacityService::SALES_CLOSED');
        $generic = strpos($source, 'commerce_checkout_prepare_error_reference');
        $this->assertNotFalse($specific);
        $this->assertNotFalse($generic);
        $this->assertLessThan($generic, $specific);
    }
}
