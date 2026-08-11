<?php

declare(strict_types=1);

namespace theme_edly;

final class customer_breadcrumb_j10e32_test extends \advanced_testcase {
    public function test_renderer_prepends_mon_campus_to_course_breadcrumb(): void {
        $source = file_get_contents(
            dirname(__DIR__, 2) . '/classes/output/core_renderer.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString('public function navbar(): string', $source);
        $this->assertStringContainsString('UrlFactory::my_campus()', $source);
        $this->assertStringContainsString("'class' => 'breadcrumb-item'", $source);
    }
}
