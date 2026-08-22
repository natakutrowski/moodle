<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_795o16_1_2_full_height_panes_test
    extends \advanced_testcase {

    private function css(): string {
        $path = dirname(__DIR__, 3) . '/styles.css';

        self::assertFileExists($path);

        $content = file_get_contents($path);
        self::assertIsString($content);

        return $content;
    }

    public function test_workspace_reaches_near_bottom_of_dynamic_viewport(): void {
        $css = $this->css();

        self::assertStringContainsString(
            'height: calc(100dvh - 11.75rem)',
            $css
        );

        self::assertStringContainsString(
            'max-height: none',
            $css
        );
    }

    public function test_list_and_reading_panes_stretch_to_workspace_height(): void {
        $css = $this->css();

        self::assertStringContainsString(
            'height: 100%',
            $css
        );

        self::assertStringContainsString(
            'align-self: stretch',
            $css
        );

        self::assertStringContainsString(
            'min-height: 0',
            $css
        );
    }
}
