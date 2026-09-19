<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

final class commerce_796h13422_accessibility_buynow_test_fix_test extends \advanced_testcase {
    public function test_all_static_required_checkout_fields_expose_aria_required(): void {
        global $CFG;

        $template = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/templates/checkout/page.mustache'
        );

        $required = preg_match_all(
            '/<(?:input|select|textarea)\b[^>]*\brequired\b[^>]*>/is',
            $template,
            $matches
        );

        self::assertGreaterThanOrEqual(4, $required);

        foreach ($matches[0] as $field) {
            self::assertStringContainsString(
                'aria-required="true"',
                $field
            );
        }
    }

    public function test_buy_now_certification_is_bounded_to_buy_now_branch(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/tests/commerce/checkout/'
            . 'commerce_796h13323_buy_now_isolation_provider_cleanup_test.php'
        );

        self::assertStringContainsString(
            '"} else if (\\$action === \'remove\')"',
            $source
        );
        self::assertStringContainsString(
            '$end - $start',
            $source
        );
    }
}
