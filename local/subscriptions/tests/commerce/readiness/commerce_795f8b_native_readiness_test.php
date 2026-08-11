<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\readiness\CommerceNativeReadinessAuditor;

final class commerce_795f8b_native_readiness_test extends advanced_testcase {
    public function test_auditor_reports_configuration_without_writing(): void {
        global $DB;
        $this->resetAfterTest();
        set_config('commerce_runtime_mode', 'native', 'local_subscriptions');
        set_config('commerce_runtime_read_mode', 'native', 'local_subscriptions');
        set_config('commerce_runtime_native_fallback_enabled', 1, 'local_subscriptions');
        set_config('commerce_checkout_enabled', 1, 'local_subscriptions');

        $before = $DB->count_records('config_plugins', ['plugin' => 'local_subscriptions']);
        $data = (new CommerceNativeReadinessAuditor($DB))->audit('native')->to_array();
        $after = $DB->count_records('config_plugins', ['plugin' => 'local_subscriptions']);

        $this->assertSame($before, $after);
        $this->assertSame('native', $data['inventory']['runtime_mode']);
        $this->assertArrayHasKey('missing_tables', $data['inventory']);
    }

    public function test_catalog_table_names_match_install_xml(): void {
        global $CFG;

        $installxml = file_get_contents($CFG->dirroot . '/local/subscriptions/db/install.xml');
        $auditorsource = file_get_contents(
            $CFG->dirroot . '/local/subscriptions/classes/commerce/readiness/CommerceNativeReadinessAuditor.php'
        );

        $this->assertIsString($installxml);
        $this->assertIsString($auditorsource);
        foreach (['local_subs_commerce_prod_map', 'local_subs_commerce_prod_ent'] as $table) {
            $this->assertStringContainsString('NAME="' . $table . '"', $installxml);
            $this->assertStringContainsString("'" . $table . "'", $auditorsource);
        }
        $this->assertStringNotContainsString('local_subs_commerce_mapping', $auditorsource);
        $this->assertStringNotContainsString('local_subs_commerce_entitlement', $auditorsource);
    }
}
