<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\recovery\CommerceRecoveryDiagnostic;

final class commerce_795h63_recovery_cli_test extends advanced_testcase {
    public function test_diagnostic_serialization_is_stable_for_cli_json_output(): void {
        $diagnostic = new CommerceRecoveryDiagnostic(
            ['reference' => 'rec-h63', 'status' => 'payment_pending'],
            [['id' => 7, 'status' => 'paid']],
            [],
            null,
            ['paid_purchase_not_fulfilled'],
            ['complete_fulfillment']
        );

        $data = $diagnostic->to_array();
        $this->assertSame('rec-h63', $data['purchase']['reference']);
        $this->assertTrue($data['repairable']);
        $this->assertFalse($data['healthy']);
        $this->assertJson(json_encode($data, JSON_THROW_ON_ERROR));
    }
}
