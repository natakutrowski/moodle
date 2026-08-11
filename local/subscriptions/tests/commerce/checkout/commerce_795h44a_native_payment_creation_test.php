<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\checkout;

defined('MOODLE_INTERNAL') || die();

/** Architecture checks for H4.4A native payment-attempt creation. */
final class commerce_795h44a_native_payment_creation_test extends \advanced_testcase {
    public function test_production_factory_injects_native_payment_repository(): void {
        $source = file_get_contents(
            __DIR__ . '/../../../classes/commerce/checkout/unified/CommerceCheckoutRuntimeFactory.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString("new CommercePaymentRepository(\$GLOBALS['DB'])", $source);
    }

    public function test_checkout_persister_creates_attempt_after_purchase_save(): void {
        $source = file_get_contents(
            __DIR__ . '/../../../classes/commerce/checkout/unified/CommerceCheckoutPurchasePersister.php'
        );

        $this->assertIsString($source);

        $save = strpos($source, '$purchaseid = $this->repository->save');
        $create = strpos($source, '$this->create_payment_attempt(', $save === false ? 0 : $save);

        $this->assertNotFalse($save);
        $this->assertNotFalse($create);
        $this->assertLessThan($create, $save);
        $this->assertStringContainsString("'source' => 'unified_checkout'", $source);
    }

    public function test_native_runtime_does_not_embed_the_operational_attempt_in_purchase_snapshot(): void {
        $source = file_get_contents(
            __DIR__ . '/../../../classes/commerce/checkout/unified/CommerceCheckoutPurchasePersister.php'
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            '$this->payments === null ? [new CommercePayment(',
            $source
        );
        $this->assertStringContainsString(')] : [],', $source);
    }
}
