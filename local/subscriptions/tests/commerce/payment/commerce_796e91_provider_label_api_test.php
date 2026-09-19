<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

final class commerce_796e91_provider_label_api_test extends advanced_testcase {
    public function test_refund_admin_pages_use_existing_provider_text_api(): void {
        global $CFG;

        foreach ([
            'admin/commerce/purchases/view.php',
            'admin/commerce/purchases/refund.php',
        ] as $file) {
            $contents = file_get_contents(
                $CFG->dirroot
                . '/local/subscriptions/'
                . $file
            );

            $this->assertStringNotContainsString(
                'Provider::label(',
                $contents
            );
        }

        $provider = file_get_contents(
            $CFG->dirroot
            . '/local/subscriptions/classes/payment/Provider.php'
        );

        $this->assertStringContainsString(
            'public static function get(',
            $provider
        );
    }
}
