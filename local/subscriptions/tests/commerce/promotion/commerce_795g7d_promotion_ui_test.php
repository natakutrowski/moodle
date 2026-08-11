<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\promotion;

defined('MOODLE_INTERNAL') || die();

final class commerce_795g7d_promotion_ui_test extends \advanced_testcase {
    public function test_cart_summary_contains_real_promotion_actions(): void {
        $template = file_get_contents(__DIR__ . '/../../../templates/cart/summary.mustache');
        $this->assertStringContainsString('name="action" value="applypromo"', $template);
        $this->assertStringContainsString('name="action" value="removepromo"', $template);
        $this->assertStringContainsString('{{#promotionadjustments}}', $template);
    }

    public function test_cart_action_uses_operation_result_as_authoritative_promotion_validation(): void {
        $source = file_get_contents(__DIR__ . '/../../../cart_action.php');
        $this->assertStringContainsString("\$action === 'applypromo'", $source);
        $this->assertStringContainsString('$result = $service->apply_promotion_code(', $source);
        $this->assertStringContainsString('rejected codes never enter the cart state', $source);
        $this->assertStringNotContainsString('$service->snapshot(', $source);
    }

    public function test_promotion_strings_exist_in_all_customer_languages(): void {
        foreach (['fr', 'en', 'ru'] as $language) {
            $source = file_get_contents(__DIR__ . '/../../../lang/' . $language . '/local_subscriptions.php');
            foreach (['commerce_cart_promo_code', 'commerce_cart_promo_apply',
                      'commerce_cart_message_promotion_not_found', 'commerce_cart_message_promotion_expired'] as $key) {
                $this->assertStringContainsString("\$string['" . $key . "']", $source);
            }
        }
    }
}
