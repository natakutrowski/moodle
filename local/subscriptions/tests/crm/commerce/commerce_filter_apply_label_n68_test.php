<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_filter_apply_label_n68_test extends advanced_testcase {
    public function test_commerce_filter_buttons_use_apply_label(): void {
        $root = dirname(__DIR__, 3);
        $commerceadmin = $root . '/admin/commerce';

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $commerceadmin,
                \FilesystemIterator::SKIP_DOTS
            )
        );

        $legacy = [];
        $usesapply = 0;

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $source = file_get_contents($file->getPathname());
            if (str_contains($source, "get_string('filter')")) {
                $legacy[] = str_replace(
                    $root . '/',
                    '',
                    $file->getPathname()
                );
            }
            if (str_contains(
                $source,
                "get_string('commerce_filters_apply', 'local_subscriptions')"
            )) {
                $usesapply++;
            }
        }

        self::assertSame(
            [],
            $legacy,
            'Legacy “Filter” button labels remain in: '
                . implode(', ', $legacy)
        );
        self::assertGreaterThanOrEqual(8, $usesapply);
    }

    public function test_apply_label_exists_in_all_supported_languages(): void {
        $root = dirname(__DIR__, 3);

        foreach (['fr', 'en', 'ru'] as $language) {
            $source = file_get_contents(
                $root . '/lang/' . $language . '/local_subscriptions.php'
            );
            self::assertStringContainsString(
                "\$string['commerce_filters_apply']",
                $source,
                $language
            );
        }
    }

    public function test_final_polish_does_not_bump_plugin_version(): void {
        $root = dirname(__DIR__, 3);
        $version = file_get_contents($root . '/version.php');

        self::assertStringContainsString(
            '$plugin->version = 2026081510;',
            $version
        );
    }
}
