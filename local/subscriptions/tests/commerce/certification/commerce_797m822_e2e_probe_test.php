<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/** Static guard for the read-only M8.2 E2E inspection helper. */
final class commerce_797m822_e2e_probe_test extends \advanced_testcase {
    public function test_probe_is_read_only_and_covers_purchase_rights_surfaces(): void {
        global $CFG;

        $source = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/cli/commerce/certification/m82_e2e_probe.php'
        );
        self::assertIsString($source);
        self::assertStringContainsString('READ ONLY: this script performs no writes.', $source);
        self::assertStringContainsString("'local_subscriptions_commerce_purchase'", $source);
        self::assertStringContainsString("'local_subscriptions_commerce_purchase_item'", $source);
        self::assertStringContainsString("'local_subscriptions_commerce_payment'", $source);
        self::assertStringContainsString("'local_subs_commerce_grant'", $source);
        self::assertStringContainsString("'local_subs_commerce_dig_access'", $source);
        self::assertStringContainsString("'local_subs_commerce_ped_join'", $source);
        self::assertStringContainsString("'local_subs_commerce_ped_access'", $source);
        self::assertStringContainsString('local_subs_commerce_ped_gmem', $source);
        self::assertStringNotContainsString('->insert_record(', $source);
        self::assertStringNotContainsString('->update_record(', $source);
        self::assertStringNotContainsString('->delete_records(', $source);
        self::assertStringNotContainsString('->set_field(', $source);
    }
}
