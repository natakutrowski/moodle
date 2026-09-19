<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796g82_credentials_accordion_ui_test extends advanced_testcase {
    public function test_credentials_are_grouped_by_provider_and_environment(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/configuration/section.php'
        );

        foreach ([
            '$rendercredentialsgroup',
            "'stripe' => [",
            "'alfa' => [",
            "'paypal' => [",
            "'live_sas' => [",
            "'sandbox' => [",
            'commerce-payment-credentials-provider',
            'commerce-payment-credentials-environment',
        ] as $expected) {
            $this->assertStringContainsString(
                $expected,
                $contents
            );
        }
    }

    public function test_secret_keep_semantics_are_preserved(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/configuration/section.php'
        );

        $this->assertStringContainsString(
            "\$definitionfield['type'] === 'password_keep'",
            $contents
        );
        $this->assertStringContainsString(
            "trim((string)\$clean) === ''",
            $contents
        );
        $this->assertStringContainsString(
            "'placeholder' => trim(\$current) !== '' ? '••••••••' : ''",
            $contents
        );
    }

    public function test_provider_logo_is_not_repeated_on_every_credential_field(): void {
        global $CFG;

        $contents = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/admin/commerce/configuration/section.php'
        );

        $this->assertStringContainsString(
            "\$fielddef['hideprovidericon'] = true;",
            $contents
        );
        $this->assertStringContainsString(
            "empty(\$fielddef['hideprovidericon'])",
            $contents
        );
    }
}
