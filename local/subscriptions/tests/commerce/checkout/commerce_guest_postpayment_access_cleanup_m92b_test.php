<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_guest_postpayment_access_cleanup_m92b_test extends \advanced_testcase {
    public function test_public_commerce_pages_do_not_use_unparameterised_core_nopermissions(): void {
        global $CFG;

        foreach ([
            'order_details.php',
            'guest_account_activation_start.php',
            'support_request.php',
            'order_result.php',
            'order_invoice.php',
            'commerce_action.php',
            'order_access.php',
        ] as $relative) {
            $source = file_get_contents(
                $CFG->dirroot . '/local/subscriptions/' . $relative
            );

            $this->assertStringNotContainsString(
                "moodle_exception('nopermissions', 'error')",
                $source,
                $relative
            );
            $this->assertStringContainsString(
                'commerce_public_access_denied',
                $source,
                $relative
            );
        }
    }

    public function test_activation_start_soft_redirects_when_activation_is_already_obsolete(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/guest_account_activation_start.php'
        );

        $this->assertStringContainsString('$activationobsolete', $source);
        $this->assertStringContainsString("!empty(\$metadata['password_set_at'])", $source);
        $this->assertStringContainsString("(\$metadata['account_origin'] ?? '') !== 'guest_checkout'", $source);
        $this->assertStringContainsString('UrlFactory::my_courses()', $source);
        $this->assertStringContainsString("'wantsurl' => \$destination->out(false)", $source);
    }

    public function test_access_denied_string_has_no_placeholder(): void {
        global $CFG;

        foreach (['fr', 'en', 'ru'] as $lang) {
            $source = file_get_contents(
                $CFG->dirroot . '/local/subscriptions/lang/' . $lang . '/local_subscriptions.php'
            );

            $this->assertMatchesRegularExpression(
                '/\$string\[\'commerce_public_access_denied\'\]\s*=\s*\'[^\']+\';/',
                $source,
                $lang
            );
            $this->assertStringNotContainsString(
                "commerce_public_access_denied'] = '({\$a})",
                $source,
                $lang
            );
        }
    }
}
