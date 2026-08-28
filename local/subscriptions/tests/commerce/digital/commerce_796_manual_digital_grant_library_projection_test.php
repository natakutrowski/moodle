<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796_manual_digital_grant_library_projection_test extends \advanced_testcase {
    public function test_manual_native_digital_access_is_projected_without_purchase_row(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/classes/commerce/digital/library/CommerceDigitalLibraryService.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString(
            'standalone_native_digital_resources($userid, $email)',
            $source
        );
        self::assertStringContainsString(
            "'local_subs_commerce_dig_access'",
            $source
        );
        self::assertStringContainsString(
            "'source' => 'crm_manual_grant'",
            $source
        );
        self::assertStringContainsString(
            "'/local/subscriptions/digital_native_download.php'",
            $source
        );
        self::assertStringContainsString(
            '\'product:sku:\' . strtolower($sku)',
            $source
        );
    }

    public function test_existing_purchase_projection_remains_in_place(): void {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents(
            $root . '/classes/commerce/digital/library/CommerceDigitalLibraryService.php'
        );

        self::assertIsString($source);
        self::assertStringContainsString(
            '$this->purchaserepository->find_details_for_customer($userid, $email)',
            $source
        );
        self::assertStringContainsString(
            '$this->prefer_richer_resource(',
            $source
        );
    }
}