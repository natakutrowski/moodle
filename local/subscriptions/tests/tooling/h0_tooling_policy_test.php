<?php

namespace local_subscriptions;

/**
 * Permanent tooling structure guardrails.
 *
 * @coversNothing
 */
final class h0_tooling_policy_test extends \advanced_testcase {
    public function test_no_php_file_lives_at_test_or_cli_root(): void {
        $pluginroot = dirname(__DIR__, 2);
        self::assertSame([], glob($pluginroot . '/tests/*.php') ?: []);
        self::assertSame([], glob($pluginroot . '/cli/*.php') ?: []);
    }

    public function test_cli_contains_no_html_artifact(): void {
        $cliroot = dirname(__DIR__, 2) . '/cli';
        $htmlfiles = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($cliroot, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'html') {
                $htmlfiles[] = $file->getPathname();
            }
        }
        self::assertSame([], $htmlfiles);
    }
}
