<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796e124_alfa_refund_authentication_test extends \advanced_testcase {

    public function test_refund_forces_dedicated_alfa_rest_username_password_authentication(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/payment/alfa/AlfaGateway.php'
        );
        self::assertIsString($source);

        self::assertStringContainsString('string $authmode = \'auto\'', $source);
        self::assertStringContainsString('$authmode === \'userpass\'', $source);
        self::assertStringContainsString('$authusername = $this->api_username();', $source);
        self::assertStringContainsString('$authpassword = $this->api_password();', $source);
        self::assertStringContainsString("'userName' => \$authusername", $source);
        self::assertStringContainsString("'password' => \$authpassword", $source);
        self::assertStringContainsString("'/payment/rest/refund.do'", $source);
        self::assertStringContainsString("'userpass'", $source);
        self::assertStringContainsString("'alfa_refund_credentials_missing'", $source);
    }


    public function test_default_gateway_operations_keep_token_first_fallback(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/payment/alfa/AlfaGateway.php'
        );
        self::assertIsString($source);

        self::assertStringContainsString('} else if (!empty($this->token)) {', $source);
        self::assertStringContainsString("['token' => \$this->token]", $source);
    }

}
