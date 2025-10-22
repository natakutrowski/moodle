<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_block_edly_contact_form_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025101902) {
        $table = new xmldb_table('block_edly_contact_msg');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id',              XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('timecreated',     XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
            $table->add_field('userid',          XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
            $table->add_field('blockinstanceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
            $table->add_field('fullname',        XMLDB_TYPE_CHAR,    '255', null, null);
            $table->add_field('email',           XMLDB_TYPE_CHAR,    '255', null, null);
            $table->add_field('message',         XMLDB_TYPE_TEXT,    null,  null);
            $table->add_field('recipient',       XMLDB_TYPE_CHAR,    '255', null, null);
            $table->add_field('ip',              XMLDB_TYPE_CHAR,    '64',  null, null);
            $table->add_field('useragent',       XMLDB_TYPE_TEXT,    null,  null);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('idx_time',  XMLDB_INDEX_NOTUNIQUE, ['timecreated']);
            $table->add_index('idx_email', XMLDB_INDEX_NOTUNIQUE, ['email']);
            $table->add_index('idx_block', XMLDB_INDEX_NOTUNIQUE, ['blockinstanceid']);

            $dbman->create_table($table);
        }
        upgrade_block_savepoint(true, 2025101902, 'edly_contact_form');
    }

    if ($oldversion < 2025101904) {
        $table = new xmldb_table('block_edly_contact_msg');

        if ($dbman->table_exists($table)) {

            // 1) Supprimer l'index sur "email" avant de changer la colonne
            $idxemail = new xmldb_index('idx_email', XMLDB_INDEX_NOTUNIQUE, ['email']);
            if ($dbman->index_exists($table, $idxemail)) {
                $dbman->drop_index($table, $idxemail);
            }

            foreach ([
                ['fullname', 255],
                ['email',    255],
                ['recipient',255],
                ['ip',        64],
            ] as [$name, $len]) {
                $field = new xmldb_field($name, XMLDB_TYPE_CHAR, (string)$len, null, null, null, null);
                if ($dbman->field_exists($table, $field)) {
                    // Enlève le DEFAULT et autorise NULL
                    $dbman->change_field_default($table, $field); // default = null
                    $dbman->change_field_notnull($table, $field); // notnull = null (autorise NULL)
                }
            }            

            // 2) Changer "email" : plus de DEFAULT '', autoriser NULL (pas NOT NULL)
            $femail = new xmldb_field('email', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            if ($dbman->field_exists($table, $femail)) {
                // default = NULL
                $dbman->change_field_default($table, $femail);
                // notnull = false
                $dbman->change_field_notnull($table, $femail);
            }

            // 3) Recréer l'index sur "email"
            if (!$dbman->index_exists($table, $idxemail)) {
                $dbman->add_index($table, $idxemail);
            }
        }

        upgrade_block_savepoint(true, 2025101904, 'edly_contact_form');
    }


    return true;
}
