<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/**
 * Structural certification for the context-aware provider experience.
 *
 * @coversNothing
 */
final class commerce_provider_experience_j14e1_test extends \advanced_testcase {
    public function test_provider_experience_contract_is_context_aware(): void {
        global $CFG;

        $js = file_get_contents($CFG->dirroot . '/local/subscriptions/amd/src/provider_experience.js');
        $modal = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/checkout/provider_experience.mustache');
        $checkout = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache');
        $showroom = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/showroom/offer.mustache');

        $this->assertStringContainsString("provider !== 'alfa'", $js);
        $this->assertStringContainsString('checkout_express_eligibility.php', $js);
        $this->assertStringContainsString('data-provider-context="standard"', $checkout);
        $this->assertStringContainsString('data-provider-context="express-candidate"', $showroom);
        $this->assertStringNotContainsString('data-provider-experience-cancel', $modal);
    }
}
