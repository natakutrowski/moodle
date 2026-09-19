<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

/**
 * 7.96J pre-certification guard: every PHPUnit class in local_subscriptions
 * must have a unique fully-qualified class name.
 */
final class commerce_796j1_unique_test_classes_test extends advanced_testcase {
    public function test_local_subscriptions_test_classes_have_unique_fqcn(): void {
        global $CFG;

        $testroot = $CFG->dirroot . '/local/subscriptions/tests';
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $testroot,
                \FilesystemIterator::SKIP_DOTS
            )
        );

        $classes = [];
        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $source = file_get_contents($file->getPathname());
            if ($source === false) {
                continue;
            }

            preg_match(
                '/^\s*namespace\s+([^;]+);/m',
                $source,
                $namespacematch
            );
            preg_match(
                '/^\s*(?:final\s+|abstract\s+)?class\s+([A-Za-z_][A-Za-z0-9_]*)/m',
                $source,
                $classmatch
            );

            if (empty($classmatch[1])) {
                continue;
            }

            $namespace = trim((string)($namespacematch[1] ?? ''));
            $fqcn = ($namespace !== '' ? $namespace . '\\' : '')
                . $classmatch[1];

            $classes[$fqcn][] = str_replace(
                $CFG->dirroot . '/local/subscriptions/',
                '',
                $file->getPathname()
            );
        }

        $duplicates = array_filter(
            $classes,
            static fn(array $files): bool => count($files) > 1
        );

        self::assertSame(
            [],
            $duplicates,
            "Duplicate PHPUnit FQCNs detected:\n"
                . json_encode(
                    $duplicates,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
                )
        );
    }
}
