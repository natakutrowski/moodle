<?php

declare(strict_types=1);

namespace theme_edly;

final class customer_breadcrumb_j10e33_test extends \advanced_testcase {
    public function test_renderer_removes_home_before_mon_campus(): void {
        $source = file_get_contents(
            dirname(__DIR__, 2) . '/classes/output/core_renderer.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            '$html = $this->remove_home_breadcrumb_item($html);',
            $source
        );
        $this->assertStringContainsString(
            'private function remove_home_breadcrumb_item(string $html): string',
            $source
        );
        $this->assertStringContainsString(
            '$normalised === \'/index.php\'',
            $source
        );
        $this->assertStringContainsString(
            'UrlFactory::my_campus()',
            $source
        );
    }
}
