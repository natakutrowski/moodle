<?php

declare(strict_types=1);

namespace theme_edly;

final class customer_breadcrumb_j10e31_test extends \advanced_testcase {
    public function test_customer_breadcrumb_only_removes_real_site_home(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/theme/edly/javascript/main.js'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            "new URL(href, window.location.origin).pathname",
            $source
        );
        $this->assertStringContainsString(
            "var normalisedPath = pathname.replace(/\\/+$/, '') || '/';",
            $source
        );
        $this->assertStringContainsString(
            "normalisedPath === '/index.php'",
            $source
        );
        $this->assertStringContainsString(
            '/mon-campus/ may legitimately end with a slash and must be kept.',
            $source
        );
        $this->assertStringNotContainsString(
            "/\\/(?:index\\.php)?(?:[?#].*)?$/.test(href)",
            $source
        );
    }
}
