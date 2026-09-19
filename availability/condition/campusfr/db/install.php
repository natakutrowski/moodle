<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Installs the runtime availability bridge on already configured promotion courses.
 */
function xmldb_availability_campusfr_install(): void {
    global $DB;

    // On a fresh Moodle/PHPUnit installation, availability plugins are
    // installed before local plugins. In that situation local_subscriptions
    // tables do not exist yet and there is nothing to backfill anyway.
    $manager = $DB->get_manager();
    $table = new xmldb_table('local_subs_commerce_course_cfg');

    if (!$manager->table_exists($table)) {
        return;
    }

    \local_subscriptions\commerce\education\access\CommercePedagogicalAvailabilitySynchronizer::create()
        ->sync_all_promotion_courses();
}
