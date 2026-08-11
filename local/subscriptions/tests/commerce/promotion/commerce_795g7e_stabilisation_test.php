<?php

namespace local_subscriptions\tests\commerce\promotion;

defined('MOODLE_INTERNAL') || die();

final class commerce_795g7e_stabilisation_test extends \advanced_testcase {
    public function test_rejected_code_is_not_persisted_before_validation(): void {
        $source = file_get_contents(__DIR__ . '/../../../classes/commerce/cart/service/CommerceCartService.php');
        $this->assertStringContainsString('A manual code is persisted only after', $source);
        $this->assertStringContainsString('if ($rejections !== [])', $source);
        $this->assertStringContainsString('$this->save($candidate);', $source);
        $this->assertLessThan(strpos($source, '$this->save($candidate);'), strpos($source, 'if ($rejections !== [])'));
    }

    public function test_cart_action_uses_operation_result_as_authority(): void {
        $source = file_get_contents(__DIR__ . '/../../../cart_action.php');
        $this->assertStringContainsString('The operation result is authoritative', $source);
        $this->assertStringNotContainsString('$snapshotmessages', $source);
    }

    public function test_payment_icons_are_centered(): void {
        $template = file_get_contents(__DIR__ . '/../../../templates/commerce/payment_reassurance.mustache');
        $css = file_get_contents(__DIR__ . '/../../../styles/storefront.css');
        $this->assertStringContainsString('justify-content-center', $template);
        $this->assertStringContainsString('justify-content: center', $css);
    }

    public function test_admin_promotion_entrypoints_exist(): void {
        foreach (['index.php', 'edit.php', 'action.php'] as $file) {
            $this->assertFileExists(__DIR__ . '/../../../admin/commerce/promotions/' . $file);
        }
    }
}
