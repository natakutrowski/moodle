<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** Contract tests for J14E2 cart currency switching. */
final class commerce_cart_currency_switch_j14e2_test extends \advanced_testcase {
    public function test_cart_currency_switch_contract_is_present(): void {
        global $CFG;
        $this->resetAfterTest(true);

        $service = file_get_contents($CFG->dirroot . '/local/subscriptions/classes/commerce/cart/currency/CommerceCartCurrencySwitchService.php');
        $action = file_get_contents($CFG->dirroot . '/local/subscriptions/cart_action.php');
        $template = file_get_contents($CFG->dirroot . '/local/subscriptions/templates/cart/page.mustache');

        $this->assertStringContainsString('promotion_code', $service);
        $this->assertStringContainsString('removedlabels', $action);
        $this->assertStringContainsString('unique_removed_items', $service);
        $this->assertStringContainsString('CommerceProductTranslationRepository', $service);
        $this->assertStringContainsString('action" value="switchcurrency', $template);
        $this->assertStringContainsString('targetcurrency', $template);

    }
}
