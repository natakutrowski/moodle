<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\showroom;

defined('MOODLE_INTERNAL') || die();

final class commerce_showroom_large_package_j16s9_test extends \advanced_testcase {
    public function test_large_package_limits_and_disk_preflight_contract(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/showroom/cms/'
            . 'CommerceShowroomPortablePackageService.php'
        );

        self::assertStringContainsString(
            'public const MAX_ARCHIVE_BYTES = 8 * 1024 * 1024 * 1024;',
            $source
        );
        self::assertStringContainsString(
            'public const MAX_SINGLE_FILE_BYTES = 2 * 1024 * 1024 * 1024;',
            $source
        );
        self::assertStringContainsString(
            'private const EXPORT_DISK_HEADROOM = 2.25;',
            $source
        );
        self::assertStringContainsString(
            'public function preflight_export(int $showroomid): array',
            $source
        );
        self::assertStringContainsString(
            'disk_free_space($directory)',
            $source
        );
    }

    public function test_export_uses_store_and_post_export_validation(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/commerce/showroom/cms/'
            . 'CommerceShowroomPortablePackageService.php'
        );

        self::assertStringContainsString(
            '\\ZipArchive::CM_STORE',
            $source
        );
        self::assertStringContainsString(
            'validate_exported_archive(',
            $source
        );
        self::assertStringContainsString(
            '$zip->open($pathname, \\ZipArchive::RDONLY)',
            $source
        );
        self::assertStringContainsString(
            '$zip->numFiles !== $expectedfiles',
            $source
        );
        self::assertStringContainsString(
            '$archivesize < 100',
            $source
        );
    }

    public function test_download_uses_send_temp_file_not_generic_send_file(): void {
        global $CFG;

        $endpoint = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/showrooms/export_portable.php'
        );

        self::assertStringContainsString(
            'send_temp_file(',
            $endpoint
        );
        self::assertStringNotContainsString(
            'send_file(',
            $endpoint
        );
    }

    public function test_builder_export_opens_preflight_page(): void {
        global $CFG;

        $root = $CFG->dirroot . '/local/subscriptions/';
        $edit = file_get_contents(
            $root . 'admin/commerce/showrooms/edit.php'
        );
        $preflight = file_get_contents(
            $root . 'admin/commerce/showrooms/export_portable_preflight.php'
        );

        self::assertStringContainsString(
            '/admin/commerce/showrooms/export_portable_preflight.php',
            $edit
        );
        self::assertStringContainsString(
            'preflight_export($id)',
            $preflight
        );
        self::assertStringContainsString(
            'commerce_showroom_export_portable_start',
            $preflight
        );
    }
}
