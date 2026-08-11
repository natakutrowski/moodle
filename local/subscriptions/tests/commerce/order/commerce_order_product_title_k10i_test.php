<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();


final class commerce_order_product_title_k10i_test extends \advanced_testcase {
    public function test_shared_order_presentation_uses_central_display_name_resolver(): void {
        global $CFG;
        $source = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/order/presentation/CommerceOrderPresentationService.php');
        self::assertIsString($source);
        self::assertStringContainsString('CommerceProductDisplayNameResolver', $source);
        self::assertStringContainsString('private function resolve_item_label', $source);
        self::assertStringContainsString('CommerceProductDisplayNameResolver::create($this->database)', $source);
        self::assertStringContainsString('$resolver->resolve(', $source);
    }
}
