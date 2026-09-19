<?php
declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796d31_fx_preview_selection_test extends advanced_testcase {
    public function test_selection_happens_in_preview_and_buttons_have_spacing(): void {
        global $CFG;
        $contents = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/admin/commerce/configuration/currencies.php'
        );

        $this->assertStringNotContainsString("refreshcurrencies[]", $contents);
        $this->assertStringContainsString("applycurrencies[]", $contents);
        $this->assertStringContainsString("commerce-fx-apply-form", $contents);
        $this->assertStringContainsString("data-fx-apply-select-all", $contents);
        $this->assertStringContainsString("data-fx-apply-deselect-all", $contents);
        $this->assertStringContainsString("d-flex gap-2 mt-4", $contents);
    }
}
