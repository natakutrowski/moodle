<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\checkout;

defined('MOODLE_INTERNAL') || die();

/** Architecture checks for the first executable unified checkout. */
final class commerce_795h3_checkout_launch_test extends \advanced_testcase {
    public function test_checkout_action_uses_unified_runtime_and_provider_action(): void {
        $source = file_get_contents(__DIR__ . '/../../../commerce_checkout_action.php');

        $this->assertIsString($source);
        $this->assertStringContainsString('CommerceCheckoutRuntimeFactory::create()->launch', $source);
        $this->assertStringContainsString('$action?->is_redirect()', $source);
        $this->assertStringContainsString('$action?->is_form_post()', $source);
        $this->assertStringNotContainsString('pricing_manager', $source);
        $this->assertStringNotContainsString('$DB->', $source);
    }

    public function test_runtime_persists_before_provider_initialization(): void {
        $source = file_get_contents(
            __DIR__ . '/../../../classes/commerce/checkout/unified/CommerceCheckoutRuntime.php'
        );

        $this->assertIsString($source);
        $persist = strpos($source, 'persist_with_result(');
        $initialize = strpos($source, '$this->orchestrator->initialize');

        $this->assertNotFalse($persist);
        $this->assertNotFalse($initialize);
        $this->assertLessThan($initialize, $persist);
    }

    public function test_cart_uses_review_wording_in_all_supported_languages(): void {
        $expectations = [
            'fr' => 'Valider le panier',
            'en' => 'Review cart',
            'ru' => 'Подтвердить корзину',
        ];

        foreach ($expectations as $language => $label) {
            $source = file_get_contents(
                __DIR__ . '/../../../lang/' . $language . '/local_subscriptions.php'
            );
            $this->assertIsString($source);
            $this->assertStringContainsString(
                "\$string['commerce_cart_checkout'] = '" . $label . "';",
                $source
            );
        }
    }
}
