<?php
// CampusFR Remember DB upgrade to 1.0.2.
defined('MOODLE_INTERNAL') || die();

function xmldb_auth_campusremember_upgrade($oldversion): bool {
    global $DB;
    $dbman = $DB->get_manager();
    if ($oldversion < 2026103103) {
        $table = new xmldb_table('auth_campusremember_tokens');
        $field = new xmldb_field('prevtokenhash', XMLDB_TYPE_CHAR, '64', null, null, null, null, 'tokenhash');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('prevvaliduntil', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'prevtokenhash');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('lastrotated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'prevvaliduntil');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2026103103, 'auth', 'campusremember');
    }
    return true;
}
