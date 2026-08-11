<?php

declare(strict_types=1);

namespace local_subscriptions\tests\commerce\catalog;

defined('MOODLE_INTERNAL') || die();

final class commerce_catalog_e19d_purchase_actions_test extends \advanced_testcase {
    private function plugin_file(string $relative): string {
        global $CFG;

        return $CFG->dirroot . '/local/subscriptions/' . $relative;
    }

    public function test_retry_uses_action_service_factory(): void {
        $source = file_get_contents($this->plugin_file('admin/commerce/purchases/retry_fulfillment.php'));

        $this->assertStringContainsString('CommercePurchaseActionServiceFactory::create()', $source);
        $this->assertStringNotContainsString('new CommercePurchaseActionService()', $source);
    }

    public function test_entitlement_repository_receives_dependencies(): void {
        $source = file_get_contents($this->plugin_file('classes/commerce/purchase/action/CommercePurchaseActionService.php'));

        $this->assertStringContainsString('new CommerceEntitlementGrantRepository(', $source);
        $this->assertStringContainsString('$this->db,', $source);
        $this->assertStringContainsString('new CommerceEntitlementGrantRecordMapper()', $source);
    }

    public function test_purchase_status_dimensions_are_labelled(): void {
        $source = file_get_contents($this->plugin_file('classes/commerce/purchase/presentation/CommercePurchasePresentation.php'));

        $this->assertStringContainsString('commerce_purchase_dimension_payment', $source);
        $this->assertStringContainsString('commerce_purchase_dimension_order', $source);
        $this->assertStringContainsString('commerce_purchase_dimension_delivery', $source);
        $this->assertStringContainsString('commerce_purchase_dimension_access', $source);
        $this->assertStringContainsString('commerce_purchase_payment_not_required', $source);
    }

    public function test_plan_upgrade_title_is_formatted_after_page_configuration(): void {
        $source = file_get_contents($this->plugin_file('admin/plans/upgrades.php'));
        $configureposition = strpos($source, 'CrmPageConfigurator::configure(');
        $formatposition = strpos($source, 'format_string($plan->name)');

        $this->assertNotFalse($configureposition);
        $this->assertNotFalse($formatposition);
        $this->assertGreaterThan($configureposition, $formatposition);
    }

    public function test_actions_below_tables_have_vertical_spacing(): void {
        $planview = file_get_contents($this->plugin_file('admin/commerce/plans/view.php'));
        $accessscope = file_get_contents($this->plugin_file('admin/commerce/products/access_scope.php'));

        $this->assertGreaterThanOrEqual(2, substr_count($planview, 'btn btn-outline-primary mt-3'));
        $this->assertStringContainsString('d-flex gap-2 mt-3 mb-4 flex-wrap', $accessscope);
    }


    public function test_native_purchase_admin_events_are_registered(): void {
        $source = file_get_contents(__DIR__ . '/../../../classes/admin/AdminEvents.php');
        $this->assertStringContainsString('self::COMMERCE_PURCHASE_FULFILLMENT_RETRIED', $source);
        $this->assertStringContainsString('self::COMMERCE_PURCHASE_NOTE_ADDED', $source);
    }

    public function test_free_paid_purchase_uses_completed_order_label(): void {
        $source = file_get_contents(
            __DIR__ . '/../../../classes/commerce/purchase/presentation/CommercePurchasePresentation.php'
        );
        $this->assertStringContainsString('$totalminor === 0', $source);
        $this->assertStringContainsString('commerce_purchase_order_status_completed', $source);
    }

    public function test_initial_fulfillment_is_distinguished_from_retry(): void {
        $view = file_get_contents($this->plugin_file('admin/commerce/purchases/view.php'));
        $service = file_get_contents($this->plugin_file('classes/commerce/purchase/action/CommercePurchaseActionService.php'));

        $this->assertStringContainsString('commerce_purchase_start_fulfillment', $view);
        $this->assertStringContainsString('$purchase->fulfillments === []', $view);
        $this->assertStringContainsString("'missing_grants'", $service);
        $this->assertStringContainsString("'mode' => \$isinitial ? 'start' : 'retry'", $service);
    }
}
