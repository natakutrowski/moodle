<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_personal_offer_identity_conflict_m13a_test extends advanced_testcase {
    public function test_conflict_ux_keeps_identity_enforcement_and_logout_retry(): void {
        global $CFG;
        $root = $CFG->dirroot . '/local/subscriptions/';
        $checkout = file_get_contents($root . 'classes/commerce/personaloffer/service/CommercePersonalOfferCheckoutService.php');
        $offer = file_get_contents($root . 'offer.php');
        $continue = file_get_contents($root . 'personal_offer_identity_continue.php');
        $this->assertStringContainsString('CommercePersonalOfferIdentityConflictException', $checkout);
        $this->assertStringContainsString("throw new \\moodle_exception('commerce_personal_offer_identity_mismatch'", $checkout);
        $this->assertStringContainsString('catch (CommercePersonalOfferIdentityConflictException $exception)', $offer);
        $this->assertStringContainsString('personal_offer_identity_continue.php', $offer);
        $this->assertStringContainsString('require_logout();', $continue);
        $this->assertStringContainsString('redirect($returnurl);', $continue);
    }
}
