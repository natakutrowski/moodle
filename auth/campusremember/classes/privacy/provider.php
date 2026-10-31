<?php
// GPL v3 or later.
namespace auth_campusremember\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\writer;
use core_privacy\local\request\transform;

class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\core_userlist_provider,
        \core_privacy\local\request\plugin\provider {
    private const TABLE = 'auth_campusremember_tokens';

    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(self::TABLE, [
            'userid' => 'privacy:metadata:userid',
            'selector' => 'privacy:metadata:token',
            'tokenhash' => 'privacy:metadata:token',
            'pwfingerprint' => 'privacy:metadata:token',
            'timecreated' => 'privacy:metadata:timestamp',
            'lastused' => 'privacy:metadata:timestamp',
            'expires' => 'privacy:metadata:timestamp',
        ], 'privacy:metadata:table');
        return $collection;
    }

    public static function get_contexts_for_userid(int $userid): contextlist {
        $list = new contextlist();
        $list->add_from_sql(
            'SELECT ctx.id FROM {auth_campusremember_tokens} t
              JOIN {context} ctx ON ctx.instanceid = t.userid
             WHERE ctx.contextlevel = :level AND t.userid = :userid',
            ['level' => CONTEXT_USER, 'userid' => $userid]
        );
        return $list;
    }

    public static function get_users_in_context(userlist $userlist) {
        $ctx = $userlist->get_context();
        if ($ctx instanceof \context_user) {
            $userlist->add_from_sql('userid',
                'SELECT userid FROM {auth_campusremember_tokens} WHERE userid = ?',
                [$ctx->instanceid]);
        }
    }

    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $ctx) {
            if (!($ctx instanceof \context_user) || $ctx->instanceid != $userid) {
                continue;
            }
            $rows = $DB->get_records(self::TABLE, ['userid' => $userid], 'id ASC');
            $devices = [];
            foreach ($rows as $row) {
                // Do not export authenticating cookies, hashes, selectors or fingerprints.
                $devices[] = (object)[
                    'created' => transform::datetime($row->timecreated),
                    'lastused' => transform::datetime($row->lastused),
                    'expires' => transform::datetime($row->expires),
                ];
            }
            writer::with_context($ctx)->export_data(['remembered_devices'], (object)['devices' => $devices]);
        }
    }

    public static function delete_data_for_all_users_in_context(\context $context) {
        if ($context instanceof \context_user) {
            self::delete_user_data((int)$context->instanceid);
        }
    }

    public static function delete_data_for_user(approved_contextlist $contextlist) {
        $userid = (int)$contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $ctx) {
            if ($ctx instanceof \context_user && (int)$ctx->instanceid === $userid) {
                self::delete_user_data($userid);
                return;
            }
        }
    }

    public static function delete_data_for_users(approved_userlist $userlist) {
        $ctx = $userlist->get_context();
        if ($ctx instanceof \context_user) {
            self::delete_user_data((int)$ctx->instanceid);
        }
    }

    private static function delete_user_data(int $userid): void {
        global $DB;
        $DB->delete_records(self::TABLE, ['userid' => $userid]);
    }
}
