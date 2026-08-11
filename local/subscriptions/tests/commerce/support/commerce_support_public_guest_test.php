<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_support_public_guest_test extends advanced_testcase {

    public function test_generic_support_page_no_longer_requires_login(): void {
        $root = dirname(__DIR__, 3);
        $source = (string)file_get_contents($root . '/support_request.php');

        $this->assertStringNotContainsString(
            "require_login();\n    if (isguestuser())",
            $source
        );
        $this->assertStringContainsString('$identityeditable', $source);
        $this->assertStringContainsString('UrlFactory::storefront()', $source);
    }

    public function test_guest_form_requires_email_but_not_first_or_last_name(): void {
        $root = dirname(__DIR__, 3);
        $source = (string)file_get_contents(
            $root . '/forms/commerce/support/CommerceSupportRequestForm.php'
        );

        $this->assertStringContainsString("'firstname'", $source);
        $this->assertStringContainsString("'lastname'", $source);
        $this->assertStringContainsString("'email'", $source);
        $this->assertStringContainsString(
            "\$mform->addRule('email', null, 'required'",
            $source
        );
        $this->assertStringNotContainsString(
            "\$mform->addRule('firstname', null, 'required'",
            $source
        );
        $this->assertStringNotContainsString(
            "\$mform->addRule('lastname', null, 'required'",
            $source
        );
    }

    public function test_domain_already_supports_anonymous_request(): void {
        $root = dirname(__DIR__, 3);
        $source = (string)file_get_contents(
            $root . '/classes/commerce/support/CommerceSupportRequest.php'
        );

        $this->assertStringContainsString('public readonly ?int $userid', $source);
        $this->assertStringContainsString('validate_email($customeremail)', $source);
    }
}
