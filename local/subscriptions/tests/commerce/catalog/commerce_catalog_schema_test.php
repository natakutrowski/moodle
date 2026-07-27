<?php

namespace local_subscriptions;

defined('MOODLE_INTERNAL') || die();

/**
 * Native Commerce catalogue schema tests.
 *
 * @coversNothing
 */
final class commerce_catalog_schema_test extends \advanced_testcase {

    public function test_native_catalogue_tables_exist(): void {
        global $DB;

        $dbman = $DB->get_manager();
        foreach ([
            'local_subs_commerce_product',
            'local_subs_commerce_prod_price',
            'local_subs_commerce_prod_tr',
            'local_subs_commerce_prod_comp',
            'local_subs_commerce_prod_ent',
            'local_subs_commerce_prod_map',
        ] as $tablename) {
            $this->assertTrue($dbman->table_exists(new \xmldb_table($tablename)), $tablename);
        }
    }

    public function test_legacy_catalogue_schema_uses_canonical_field_names(): void {
        global $DB;

        $this->assertArrayHasKey('planid', $DB->get_columns('subscription_plan_price'));
        $this->assertArrayNotHasKey('plan_id', $DB->get_columns('subscription_plan_price'));

        $this->assertArrayHasKey('planid', $DB->get_columns('subscription_plan_translation'));
        $this->assertArrayNotHasKey('plan_id', $DB->get_columns('subscription_plan_translation'));

        $this->assertArrayHasKey('accessscopeid', $DB->get_columns('subscription_access_scope_translation'));
        $this->assertArrayNotHasKey('scope_id', $DB->get_columns('subscription_access_scope_translation'));
    }

}
