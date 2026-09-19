<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\payment\result\CommercePaymentAction;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h2_embedded_card_action_test extends advanced_testcase {
    public function test_payment_action_supports_embedded_execution(): void {
        $action = CommercePaymentAction::embedded(
            [
                'client_secret' => 'pi_secret',
                'publishable_key' => 'pk_test',
                'return_url' => 'https://example.test/return',
            ]
        );

        $this->assertTrue(
            $action->is_embedded()
        );
        $this->assertSame(
            CommercePaymentAction::TYPE_EMBEDDED,
            $action->get_type()
        );
        $this->assertNull(
            $action->get_url()
        );
    }
}
