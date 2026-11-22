<?php
// Moodle database upgrade — local_campusreader. GPL-3.0-or-later.
defined('MOODLE_INTERNAL') || die();

function xmldb_local_campusreader_upgrade($oldversion): bool {
    global $DB;
    $dbman = $DB->get_manager();
    if ($oldversion < 2026111301) {
        $table = new xmldb_table('local_cr_collection');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('slug', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null);
            $table->add_field('title', XMLDB_TYPE_CHAR, '191', null, XMLDB_NOTNULL, null, null);
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timezone', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, 'Europe/Paris');
            $table->add_field('published', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('slug_u', XMLDB_INDEX_UNIQUE, ['slug']);
            $table->add_index('course_i', XMLDB_INDEX_NOTUNIQUE, ['courseid']);
            $dbman->create_table($table);
        }
        $table = new xmldb_table('local_cr_book');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('collectionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('slug', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null);
            $table->add_field('title', XMLDB_TYPE_CHAR, '191', null, XMLDB_NOTNULL, null, null);
            $table->add_field('subtitle', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('published', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('coll_slug_u', XMLDB_INDEX_UNIQUE, ['collectionid', 'slug']);
            $dbman->create_table($table);
        }
        $table = new xmldb_table('local_cr_slot');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('collectionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('position', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('unlockat', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('bookid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('teaser', XMLDB_TYPE_CHAR, '191', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('coll_pos_u', XMLDB_INDEX_UNIQUE, ['collectionid', 'position']);
            $table->add_index('book_i', XMLDB_INDEX_NOTUNIQUE, ['bookid']);
            $dbman->create_table($table);
        }
        $table = new xmldb_table('local_cr_page');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('bookid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('pageindex', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('kind', XMLDB_TYPE_CHAR, '16', null, XMLDB_NOTNULL, null, null);
            $table->add_field('title', XMLDB_TYPE_CHAR, '191', null, XMLDB_NOTNULL, null, null);
            $table->add_field('paragraphsjson', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
            $table->add_field('imagefile', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('imagealt', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('book_page_u', XMLDB_INDEX_UNIQUE, ['bookid', 'pageindex']);
            $dbman->create_table($table);
        }
        $table = new xmldb_table('local_cr_progress');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('bookid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('pageindex', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('user_book_u', XMLDB_INDEX_UNIQUE, ['userid', 'bookid']);
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2026111301, 'local', 'campusreader');
    }
    if ($oldversion < 2026111302) {
        // Presentation only: existing content and entitlement data remain untouched.
        $table = new xmldb_table('local_cr_collection');
        $field = new xmldb_field('presentationmode', XMLDB_TYPE_CHAR, '12', null,
            XMLDB_NOTNULL, null, 'auto');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('libraryafter', XMLDB_TYPE_INTEGER, '10', null,
            XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $table = new xmldb_table('local_cr_slot');
        $field = new xmldb_field('layoutsize', XMLDB_TYPE_CHAR, '12', null,
            XMLDB_NOTNULL, null, 'normal');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2026111302, 'local', 'campusreader');
    }
    if ($oldversion < 2026111401) {
        // D2.4: New independent anthology draft aggregate. Existing books/slots untouched.
        $table = new xmldb_table('local_cr_anthology');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('slug', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null);
            $table->add_field('title', XMLDB_TYPE_CHAR, '191', null, XMLDB_NOTNULL, null, null);
            $table->add_field('subtitle', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('status', XMLDB_TYPE_CHAR, '12', null, XMLDB_NOTNULL, null, 'draft');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('slug_u', XMLDB_INDEX_UNIQUE, ['slug']);
            $dbman->create_table($table);
        }
        $table = new xmldb_table('local_cr_anthology_story');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('anthologyid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('bookid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('position', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('anth_pos_u', XMLDB_INDEX_UNIQUE, ['anthologyid', 'position']);
            $table->add_index('anth_book_u', XMLDB_INDEX_UNIQUE, ['anthologyid', 'bookid']);
            $table->add_index('book_i', XMLDB_INDEX_NOTUNIQUE, ['bookid']);
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2026111401, 'local', 'campusreader');
    }
    if ($oldversion < 2026111402) {
        // D2.4.2a: private, append-only snapshots. No publication or public file handler.
        $table = new xmldb_table('local_cr_anthrev');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('anthologyid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('revno', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('digest', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null);
            $table->add_field('snapshotjson', XMLDB_TYPE_TEXT, 'big', null, XMLDB_NOTNULL, null, null);
            $table->add_field('state', XMLDB_TYPE_CHAR, '12', null, XMLDB_NOTNULL, null, 'review');
            $table->add_field('createdby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('approvedby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('approvedat', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('anth_rev_u', XMLDB_INDEX_UNIQUE, ['anthologyid', 'revno']);
            $table->add_index('anth_state_i', XMLDB_INDEX_NOTUNIQUE, ['anthologyid', 'state']);
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2026111402, 'local', 'campusreader');
    }
    if ($oldversion < 2026111403) {
        // D2.4.3: published editions + product associations, safely closed until explicit publication.
        $table = new xmldb_table('local_cr_publication');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('slug', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null);
            $table->add_field('kind', XMLDB_TYPE_CHAR, '12', null, XMLDB_NOTNULL, null, null);
            $table->add_field('anthologyid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('revisionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('collectionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('calendarjson', XMLDB_TYPE_TEXT, 'big', null, XMLDB_NOTNULL, null, null);
            $table->add_field('productsku', XMLDB_TYPE_CHAR, '100', null, null, null, null);
            $table->add_field('status', XMLDB_TYPE_CHAR, '12', null, XMLDB_NOTNULL, null, 'draft');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('publishedat', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('slug_u', XMLDB_INDEX_UNIQUE, ['slug']);
            $table->add_index('anth_i', XMLDB_INDEX_NOTUNIQUE, ['anthologyid']);
            $table->add_index('prod_i', XMLDB_INDEX_NOTUNIQUE, ['productsku']);
            $table->add_index('status_i', XMLDB_INDEX_NOTUNIQUE, ['status']);
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2026111403, 'local', 'campusreader');
    }
    if ($oldversion < 2026111501) {
        // D2.8: persistent personal vocabulary. No enrolments, purchases or existing text modified.
        $table = new xmldb_table('local_cr_vocab');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('lang', XMLDB_TYPE_CHAR, '12', null, XMLDB_NOTNULL, null, 'fr');
            $table->add_field('wordhash', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null);
            $table->add_field('word', XMLDB_TYPE_CHAR, '191', null, XMLDB_NOTNULL, null, null);
            $table->add_field('translation', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('user_word_u', XMLDB_INDEX_UNIQUE, ['userid', 'lang', 'wordhash']);
            $dbman->create_table($table);
        }
        $table = new xmldb_table('local_cr_vocab_source');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('vocabid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('pubslug', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null);
            $table->add_field('chapter', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('pageindex', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('source_u', XMLDB_INDEX_UNIQUE, ['vocabid', 'pubslug', 'chapter', 'pageindex']);
            $table->add_index('pub_i', XMLDB_INDEX_NOTUNIQUE, ['pubslug']);
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2026111501, 'local', 'campusreader');
    }
    return true;
}
