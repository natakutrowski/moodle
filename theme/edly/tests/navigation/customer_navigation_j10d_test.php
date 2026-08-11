<?php

declare(strict_types=1);

namespace theme_edly;

final class customer_navigation_j10d_test extends \advanced_testcase {
    public function test_cart_badge_contract_is_present(): void {
        global $CFG;
        $source = file_get_contents($CFG->dirroot . '/theme/edly/classes/local/customer_navigation.php');
        $template = file_get_contents($CFG->dirroot . '/theme/edly/templates/customer_navigation.mustache');
        $this->assertStringContainsString('cart_count()', $source);
        $this->assertStringContainsString('hascartitems', $template);
    }
}
