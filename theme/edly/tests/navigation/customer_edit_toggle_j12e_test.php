<?php

declare(strict_types=1);

namespace theme_edly;

final class customer_edit_toggle_j12e_test extends \advanced_testcase {
    public function test_edit_toggle_uses_shared_height_and_active_border(): void {
        $css = file_get_contents(dirname(__DIR__, 2) . '/style/customer-navigation.css');
        self::assertStringContainsString('height: 2.75rem', $css);
        self::assertStringContainsString(':has(.form-check-input:checked)', $css);
        self::assertStringContainsString('border-color:#f60b72', str_replace(' ', '', $css));
    }
}
