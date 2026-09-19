<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h1010_alfa_production_diagnostics_cleanup_test extends advanced_testcase {

    public function test_customer_checkout_has_no_alfa_debug_surface_or_console_trace(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache'
        );
        $this->assertIsString($template);
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_alfa_widget.js'
        );
        $this->assertIsString($js);

        foreach ([
            'data-checkout-alfa-debug',
            'data-checkout-alfa-debug-copy',
            'data-checkout-alfa-debug-output',
        ] as $removed) {
            $this->assertStringNotContainsString($removed, $template);
        }
        $this->assertStringNotContainsString('console.info(', $js);
        $this->assertStringNotContainsString('console.error(', $js);
        $this->assertStringContainsString('if (!enabled)', $js);
        $this->assertStringContainsString('return () => {};', $js);
        $this->assertStringContainsString('window.__campusAlfaWidgetDebug', $js);
    }


    public function test_server_diagnostics_are_gated_by_debug_developer(): void {
        global $CFG;

        $checkout = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout.php'
        );
        $this->assertIsString($checkout);

        $this->assertStringContainsString("debugging('', DEBUG_DEVELOPER)", $checkout);
        $this->assertStringContainsString('\'alfawidgetdebug\' => $alfawidgetdebugenabled ? \'1\' : \'0\'', $checkout);
        $this->assertStringContainsString('$alfawidgetdebugenabled ? $alfawidgetserverdiagnostics : []', $checkout);
        $this->assertStringContainsString("'token_present'", $checkout);
        $this->assertStringContainsString("'token_length'", $checkout);
    }

}
