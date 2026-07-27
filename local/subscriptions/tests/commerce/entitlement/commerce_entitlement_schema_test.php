<?php

declare(strict_types=1);

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;

final class commerce_entitlement_schema_test extends advanced_testcase {
    public function test_native_entitlement_grant_ledger_schema_exists(): void {
        global $DB;

        $columns = $DB->get_columns('local_subs_commerce_grant');

        $this->assertArrayHasKey('grantreference', $columns);
        $this->assertArrayHasKey('idempotencykey', $columns);
        $this->assertArrayHasKey('purchasereference', $columns);
        $this->assertArrayHasKey('productsku', $columns);
        $this->assertArrayHasKey('resourcekey', $columns);
        $this->assertArrayHasKey('beneficiaryuserid', $columns);
        $this->assertArrayHasKey('status', $columns);
        $this->assertArrayHasKey('configurationjson', $columns);
        $this->assertArrayHasKey('metadatajson', $columns);
    }
}
