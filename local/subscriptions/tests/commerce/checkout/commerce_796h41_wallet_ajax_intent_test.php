<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h41_wallet_ajax_intent_test extends advanced_testcase {
    public function test_checkout_action_can_return_embedded_intent_json(): void {
        global $CFG;

        $action = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/commerce_checkout_action.php'
        );
        $this->assertIsString($action);

        $this->assertStringContainsString("optional_param('ajax'", $action);
        $this->assertStringContainsString("'clientSecret'", $action);
        $this->assertStringContainsString("'embedded'", $action);
    }

    public function test_wallet_intent_is_initialized_only_inside_confirm_handler(): void {
        global $CFG;

        $js = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/amd/src/checkout_express_wallets.js'
        );
        $this->assertIsString($js);

        $confirm = strpos($js, "'confirm'");
        $intent = strpos($js, 'await initializePayment(');
        $this->assertNotFalse($confirm);
        $this->assertNotFalse($intent);
        $this->assertGreaterThan($confirm, $intent);
        $this->assertStringContainsString('payload.clientSecret', $js);
    }
}
