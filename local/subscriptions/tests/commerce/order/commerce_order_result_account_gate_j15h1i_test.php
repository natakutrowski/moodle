<?php
namespace local_subscriptions;

/** J15H.1I static regression coverage. */
final class commerce_order_result_account_gate_j15h1i_test extends \advanced_testcase {
    public function test_storefront_template_closes_responsive_section(): void {
        $template = file_get_contents(__DIR__ . '/../../../templates/storefront/product_card.mustache');
        $this->assertStringContainsString('{{/coverresponsive}}', $template);
        $this->assertLessThan(
            strpos($template, '{{/hascover}}'),
            strpos($template, '{{/coverresponsive}}')
        );
    }

    public function test_order_result_gates_courses_and_hides_next_links_for_provisional_accounts(): void {
        $source = file_get_contents(__DIR__ . '/../../../order_result.php');
        $this->assertStringContainsString('data-requires-account-finalisation', $source);
        $this->assertStringContainsString('if (!$requiresaccountfinalisation)', $source);
        $this->assertStringContainsString('commerce_view_order', $source);
    }
}
