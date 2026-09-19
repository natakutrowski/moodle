<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\purchase\presentation\CommercePurchasePresentation;

defined('MOODLE_INTERNAL') || die();

final class commerce_796e131_commercial_status_fallback_test extends advanced_testcase {
    public function test_unknown_commercial_status_falls_back_without_invalid_get_string(): void {
        $label = CommercePurchasePresentation::commercial_status_label(
            'payment_pending'
        );

        $this->assertNotSame('', trim($label));
        $this->assertSame(
            CommercePurchasePresentation::technical_status_label(
                'payment',
                'payment_pending'
            ),
            $label
        );
    }

    public function test_existing_commercial_status_translation_still_wins(): void {
        $label = CommercePurchasePresentation::commercial_status_label(
            'fulfilled'
        );

        $this->assertSame(
            get_string(
                'commerce_purchase_commercial_status_fulfilled',
                'local_subscriptions'
            ),
            $label
        );
    }
}
