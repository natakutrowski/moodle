<?php

function xmldb_local_subscriptions_upgrade($oldversion) {
    global $DB;

    if ($oldversion < 2025061101) {
        $table = new xmldb_table('user_subscription');
        $field = new xmldb_field('creation_date', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);

        if (!$DB->get_manager()->field_exists($table, $field)) {
            $DB->get_manager()->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2025061101, 'local', 'subscriptions');
    }

    return true;
}
