<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use local_subscriptions\commerce\purchase\presentation\CommercePurchasePresentation;
use local_subscriptions\commerce\purchase\status\CommerceCommercialStatus;

final class commerce_purchase_presentation_test extends advanced_testcase {
    public function test_commercial_status_badge_is_accessible_text(): void {
        $html = CommercePurchasePresentation::commercial_status_badge(CommerceCommercialStatus::FULFILLED);
        $this->assertStringContainsString('badge', $html);
        $this->assertStringContainsString(get_string('commerce_purchase_commercial_status_fulfilled', 'local_subscriptions'), $html);
    }

    public function test_money_keeps_currency_isolated(): void {
        $this->assertSame('123.45 EUR', CommercePurchasePresentation::money(12345, 'eur'));
    }
}
