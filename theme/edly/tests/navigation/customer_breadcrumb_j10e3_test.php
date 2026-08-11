<?php

declare(strict_types=1);

namespace theme_edly;

final class customer_breadcrumb_j10e3_test extends \advanced_testcase {
    public function test_customer_breadcrumb_preserves_mon_campus_first_item(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/theme/edly/javascript/main.js'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString('preserve every custom first item', $source);
        $this->assertStringContainsString('var isSiteHome', $source);
        $this->assertStringNotContainsString(
            'firstItem.parentNode.removeChild(firstItem);',
            $source
        );
    }
}
