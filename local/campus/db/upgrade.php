<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_local_campus_upgrade($oldversion) {
    global $DB;

    if ($oldversion < 2025102101) {
        // Création de la table trial si besoin.
        $dbman = $DB->get_manager();
        $table = new xmldb_table('local_campus_trial');
        if (!$dbman->table_exists($table)) {
            $xmlfile = __DIR__.'/install.xml';
            $xmldb = file_get_contents($xmlfile);
            // Dans Moodle, on crée plutôt via xmldb_* objects;
            // Par simplicité ici, crée la table à la main:
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('email', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
            $table->add_field('firstname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
            $table->add_field('lastname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('expiresat', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('lastseen', XMLDB_TYPE_INTEGER, '10', null, null);
            $table->add_field('reminder3_sent', XMLDB_TYPE_INTEGER, '10', null, null);
            $table->add_field('reminder7_sent', XMLDB_TYPE_INTEGER, '10', null, null);
            $table->add_field('status', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('email_ix', XMLDB_INDEX_NOTUNIQUE, ['email']);
            $table->add_index('expires_ix', XMLDB_INDEX_NOTUNIQUE, ['expiresat']);
            $table->add_index('status_ix', XMLDB_INDEX_NOTUNIQUE, ['status']);

            $dbman->create_table($table);
        }

        // Crée le rôle trialstudent si absent.
        $shortname = 'trialstudent';
        if (!$DB->record_exists('role', ['shortname'=>$shortname])) {
            $roleid = create_role(
                get_string('rolename_trialstudent','local_campus'),
                $shortname,
                get_string('roledesc_trialstudent','local_campus'),
                'student' // archétype
            );
            // Désactiver quelques capacités "actives"
            assign_capability('mod/quiz:attempt', CAP_PREVENT, $roleid, SYSCONTEXTID);
            assign_capability('mod/assign:submit', CAP_PREVENT, $roleid, SYSCONTEXTID);
            assign_capability('mod/forum:startdiscussion', CAP_PREVENT, $roleid, SYSCONTEXTID);
            assign_capability('mod/forum:replypost', CAP_PREVENT, $roleid, SYSCONTEXTID);
        }

        upgrade_plugin_savepoint(true, 2025102101, 'local', 'campus');
    }

    if ($oldversion < 2025102102) {
        $dbman = $DB->get_manager();
        $table = new xmldb_table('local_campus_trial');

        $field = new xmldb_field('ipaddress', XMLDB_TYPE_CHAR, '45', null, null, null, null, 'status');
        if (!$dbman->field_exists($table, $field)) { $dbman->add_field($table, $field); }

        $field = new xmldb_field('useragent', XMLDB_TYPE_TEXT, null, null, null, null, null, 'ipaddress');
        if (!$dbman->field_exists($table, $field)) { $dbman->add_field($table, $field); }

        upgrade_plugin_savepoint(true, 2025102102, 'local', 'campus');
    }


    return true;
}
