<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\certification;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\commerce\cart\presentation\CommerceCartPresenter;
use local_subscriptions\commerce\cart\service\CommerceCartCalculator;
use local_subscriptions\commerce\cart\service\CommerceCartService;
use local_subscriptions\commerce\promotion\service\CommercePromotionEngine;

/** Final certification contract for Commerce 7.95G. */
final class commerce_795g8_cart_promotion_certification_test extends \advanced_testcase {
    public function test_public_cart_and_promotion_api_signatures_are_frozen(): void {
        $this->assertMethodSignature(
            CommerceCartPresenter::class,
            'present',
            ['snapshot', 'language'],
            1
        );
        $this->assertMethodSignature(
            CommerceCartService::class,
            'apply_promotion_code',
            ['customerid', 'currency', 'code'],
            3
        );
        $this->assertMethodSignature(
            CommerceCartService::class,
            'remove_promotion_code',
            ['customerid', 'currency'],
            2
        );
        $this->assertMethodSignature(
            CommerceCartService::class,
            'snapshot',
            ['customerid', 'currency', 'language', 'at'],
            3
        );
        $this->assertMethodSignature(
            CommerceCartCalculator::class,
            'calculate',
            ['cart', 'language', 'at'],
            2
        );
        $this->assertMethodSignature(
            CommercePromotionEngine::class,
            'calculate',
            ['subtotalminor', 'currency', 'userid', 'items', 'manualcode', 'at'],
            6
        );
    }

    public function test_cart_mutation_endpoint_keeps_security_and_result_contracts(): void {
        $source = file_get_contents(__DIR__ . '/../../../cart_action.php');

        $this->assertIsString($source);
        $this->assertStringContainsString('require_sesskey();', $source);
        $this->assertStringContainsString("required_param('action', PARAM_ALPHA)", $source);
        $this->assertStringContainsString("optional_param('returnurl'", $source);
        $this->assertStringContainsString('PARAM_LOCALURL', $source);
        $this->assertStringContainsString('$result->get_messages()', $source);
        $this->assertStringNotContainsString('$service->snapshot(', $source);
    }

    public function test_rejected_promotion_contract_is_documented_and_certified(): void {
        $service = file_get_contents(
            __DIR__ . '/../../../classes/commerce/cart/service/CommerceCartService.php'
        );
        $cartaction = file_get_contents(__DIR__ . '/../../../cart_action.php');

        $this->assertIsString($service);
        $this->assertIsString($cartaction);
        $this->assertStringContainsString(
            'A manual code is persisted only after a complete server-side calculation accepts it.',
            $service
        );
        $this->assertStringContainsString('$this->save($candidate);', $service);
        $this->assertStringContainsString(
            'rejected codes never enter the cart state',
            $cartaction
        );
    }

    public function test_g8_documentation_declares_pre_checkout_contracts(): void {
        $documentation = file_get_contents(
            __DIR__ . '/../../../docs/commerce/7.95G8-cart-promotion-certification.md'
        );

        $this->assertIsString($documentation);
        $this->assertStringContainsString('API freeze', $documentation);
        $this->assertStringContainsString('CartSnapshot', $documentation);
        $this->assertStringContainsString('Promotion adjustments', $documentation);
        $this->assertStringContainsString('Unified Checkout', $documentation);
    }

    /**
     * @param class-string $class
     * @param string[] $parameternames
     */
    private function assertMethodSignature(
        string $class,
        string $method,
        array $parameternames,
        int $requiredparameters
    ): void {
        $reflection = new \ReflectionMethod($class, $method);
        $parameters = $reflection->getParameters();

        $this->assertTrue($reflection->isPublic(), $class . '::' . $method . ' must remain public.');
        $this->assertSame($parameternames, array_map(
            static fn(\ReflectionParameter $parameter): string => $parameter->getName(),
            $parameters
        ));
        $this->assertSame($requiredparameters, $reflection->getNumberOfRequiredParameters());
    }
}
