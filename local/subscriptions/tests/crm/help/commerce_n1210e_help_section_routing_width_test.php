<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_n1210e_help_section_routing_width_test extends \advanced_testcase {

    private function file(string $relative): string {
        $path = dirname(__DIR__, 3) . '/' . ltrim($relative, '/');
        self::assertFileExists($path);
        $content = file_get_contents($path);
        self::assertIsString($content);
        return $content;
    }

    public function test_help_section_parameter_drives_focused_views(): void {
        $index = $this->file(
            'admin/help/index.php'
        );

        self::assertStringContainsString(
            "['guides', 'articles']",
            $index
        );
        self::assertStringContainsString(
            "\$section !== 'articles'",
            $index
        );
        self::assertStringContainsString(
            "\$section !== 'guides'",
            $index
        );
        self::assertStringContainsString(
            "\$section === 'guides'",
            $index
        );
        self::assertStringContainsString(
            "\$section === 'articles'",
            $index
        );
    }

    public function test_help_center_no_longer_relies_on_anchor_only_navigation(): void {
        $index = $this->file(
            'admin/help/index.php'
        );

        self::assertStringContainsString(
            "\$urlparams['section']",
            $index
        );
        self::assertStringContainsString(
            '$pagesubtitle',
            $index
        );
    }
}
