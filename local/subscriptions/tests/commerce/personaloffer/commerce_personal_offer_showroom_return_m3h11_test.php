<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_personal_offer_showroom_return_m3h11_test extends \advanced_testcase {
    public function test_checkout_back_to_offer_reenters_signed_offer_boundary_before_showroom(): void {
        global $CFG;

        $source = (string)file_get_contents($CFG->dirroot . '/local/subscriptions/offer.php');

        $this->assertStringContainsString(
            "new moodle_url('/local/subscriptions/offer.php'",
            $source
        );
        $this->assertStringContainsString("'token' => \$token", $source);
        $this->assertStringContainsString("'currency' => \$currency", $source);
        $this->assertStringContainsString("'anchor' => 'showroom-offers'", $source);

        // It must not return directly to CommerceShowroomUrl from checkout originreturn,
        // otherwise Personal Offer session reinitialisation is bypassed.
        $this->assertStringContainsString(
            'Re-enter through the signed Personal Offer boundary',
            $source
        );
    }

    public function test_offer_entry_allows_only_fixed_showroom_anchor_and_applies_it_after_validation(): void {
        global $CFG;

        $source = (string)file_get_contents($CFG->dirroot . '/local/subscriptions/offer.php');

        $this->assertStringContainsString(
            "in_array(\$requestedanchor, ['', 'showroom-offers'], true)",
            $source
        );
        $this->assertStringContainsString("\$showroomtarget .= '#showroom-offers'", $source);
        $this->assertStringNotContainsString('?anchor=', $source);
    }

    public function test_personal_offer_session_is_initialised_before_showroom_redirect(): void {
        global $CFG;

        $source = (string)file_get_contents($CFG->dirroot . '/local/subscriptions/offer.php');

        $initialise = strpos($source, 'CommercePersonalOfferSessionService())->initialise(');
        $redirect = strpos($source, '$showroomurl = CommerceShowroomUrl::make(');

        $this->assertNotFalse($initialise);
        $this->assertNotFalse($redirect);
        $this->assertLessThan($redirect, $initialise);
    }
}
