<?php

declare(strict_types=1);

namespace local_subscriptions;

final class commerce_checkout_trust_ux_j14b_test extends \advanced_testcase {
    public function test_checkout_uses_vertical_identity_fields_and_legal_consent(): void {
        global $CFG;
        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/checkout/page.mustache');
        $action = file_get_contents($CFG->dirroot . '/local/subscriptions/commerce_checkout_action.php');
        $checkout = file_get_contents($CFG->dirroot . '/local/subscriptions/commerce_checkout.php');

        self::assertStringContainsString('name="accept_terms"', $template);
        self::assertStringContainsString('{{{legalacceptlabel}}}', $template);
        self::assertStringNotContainsString('class="row g-3"', $template);
        self::assertStringContainsString("optional_param('accept_terms', 0, PARAM_BOOL)", $action);
        self::assertStringContainsString("'legalacceptlabel'", $checkout);
    }
}
