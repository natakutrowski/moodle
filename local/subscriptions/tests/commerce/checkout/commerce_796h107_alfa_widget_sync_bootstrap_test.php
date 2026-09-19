<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h107_alfa_widget_sync_bootstrap_test extends advanced_testcase {

    public function test_alfa_widget_bundle_is_not_deferred(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $this->assertIsString($template);

        $this->assertStringContainsString('id="alfa-payment-script"', $template);
        $this->assertStringContainsString('src="{{alfawidgetscripturl}}"', $template);
        $this->assertStringNotContainsString('defer></script>', $template);
    }


    public function test_current_runtime_diagnostics_and_explicit_init_are_preserved(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_alfa_widget.js'
        );
        $this->assertIsString($js);

        $this->assertStringContainsString("const BUILD_ID = '7.96H10.10'", $js);
        $this->assertStringContainsString('scriptInitType:', $js);
        $this->assertStringContainsString("'Official widget init called'", $js);
        $this->assertStringContainsString('wakeOfficialWidget(', $js);
    }

}
