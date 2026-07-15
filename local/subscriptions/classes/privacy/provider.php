<?php

namespace local_subscriptions\privacy;

defined('MOODLE_INTERNAL') || die();

use context;
use context_system;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

final class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    public static function get_metadata(
        collection $collection
    ): collection {
        $collection->add_database_table(
            'local_subscriptions_inbox_contact',
            [
                'displayname' =>
                    'privacy:metadata:inbox_contact:displayname',
                'primaryemail' =>
                    'privacy:metadata:inbox_contact:primaryemail',
                'normalizedemail' =>
                    'privacy:metadata:inbox_contact:normalizedemail',
                'matcheduserid' =>
                    'privacy:metadata:inbox_contact:matcheduserid',
                'matchstatus' =>
                    'privacy:metadata:inbox_contact:matchstatus',
                'matchsource' =>
                    'privacy:metadata:inbox_contact:matchsource',
                'matchconfidence' =>
                    'privacy:metadata:inbox_contact:matchconfidence',
                'lastmatchedat' =>
                    'privacy:metadata:inbox_contact:lastmatchedat',
            ],
            'privacy:metadata:inbox_contact'
        );

        $collection->add_database_table(
            'local_subscriptions_inbox_thread',
            [
                'contactid' =>
                    'privacy:metadata:inbox_thread:contactid',
                'subject' =>
                    'privacy:metadata:inbox_thread:subject',
                'assigneduserid' =>
                    'privacy:metadata:inbox_thread:assigneduserid',
                'status' =>
                    'privacy:metadata:inbox_thread:status',
                'priority' =>
                    'privacy:metadata:inbox_thread:priority',
                'lastmessageat' =>
                    'privacy:metadata:inbox_thread:lastmessageat',
            ],
            'privacy:metadata:inbox_thread'
        );

        $collection->add_database_table(
            'local_subscriptions_inbox_message',
            [
                'threadid' =>
                    'privacy:metadata:inbox_message:threadid',
                'direction' =>
                    'privacy:metadata:inbox_message:direction',
                'subject' =>
                    'privacy:metadata:inbox_message:subject',
                'bodytext' =>
                    'privacy:metadata:inbox_message:bodytext',
                'bodyhtml' =>
                    'privacy:metadata:inbox_message:bodyhtml',
                'receivedat' =>
                    'privacy:metadata:inbox_message:receivedat',
                'sentat' =>
                    'privacy:metadata:inbox_message:sentat',
                'createdby' =>
                    'privacy:metadata:inbox_message:createdby',
            ],
            'privacy:metadata:inbox_message'
        );

        $collection->add_database_table(
            'local_subscriptions_inbox_participant',
            [
                'messageid' =>
                    'privacy:metadata:inbox_participant:messageid',
                'contactid' =>
                    'privacy:metadata:inbox_participant:contactid',
                'participanttype' =>
                    'privacy:metadata:inbox_participant:participanttype',
                'email' =>
                    'privacy:metadata:inbox_participant:email',
                'displayname' =>
                    'privacy:metadata:inbox_participant:displayname',
            ],
            'privacy:metadata:inbox_participant'
        );

        $collection->add_database_table(
            'local_subscriptions_inbox_attachment',
            [
                'messageid' =>
                    'privacy:metadata:inbox_attachment:messageid',
                'filename' =>
                    'privacy:metadata:inbox_attachment:filename',
                'mimetype' =>
                    'privacy:metadata:inbox_attachment:mimetype',
                'filesize' =>
                    'privacy:metadata:inbox_attachment:filesize',
            ],
            'privacy:metadata:inbox_attachment'
        );

        return $collection;
    }

    public static function get_contexts_for_userid(
        int $userid
    ): contextlist {
        global $DB;

        $contextlist = new contextlist();

        $hassystemdata = false;

        if (
            $DB->record_exists(
                'local_subscriptions_inbox_contact',
                ['matcheduserid' => $userid]
            )
        ) {
            $hassystemdata = true;
        }

        if (
            !$hassystemdata &&
            $DB->record_exists(
                'local_subscriptions_inbox_thread',
                ['assigneduserid' => $userid]
            )
        ) {
            $hassystemdata = true;
        }

        if (
            !$hassystemdata &&
            $DB->record_exists(
                'local_subscriptions_inbox_message',
                ['createdby' => $userid]
            )
        ) {
            $hassystemdata = true;
        }

        if ($hassystemdata) {
            $contextlist->add_system_context();
        }

        return $contextlist;
    }

    public static function export_user_data(
        approved_contextlist $contextlist
    ): void {
        global $DB;

        $userid = $contextlist->get_user()->id;

        $systemcontext =
            context_system::instance();

        if (
            !in_array(
                $systemcontext->id,
                $contextlist->get_contextids(),
                true
            )
        ) {
            return;
        }

        $sql = "
            SELECT
                t.id AS threadid,
                t.subject AS threadsubject,
                t.status AS threadstatus,
                t.priority,
                t.lastmessageat,
                c.primaryemail,
                c.displayname,
                c.matchstatus
              FROM {local_subscriptions_inbox_thread} t
              JOIN {local_subscriptions_inbox_contact} c
                ON c.id = t.contactid
             WHERE c.matcheduserid = :userid
                OR t.assigneduserid = :assigneduserid
          ORDER BY t.lastmessageat ASC, t.id ASC
        ";

        $threads = $DB->get_records_sql(
            $sql,
            [
                'userid' => $userid,
                'assigneduserid' => $userid,
            ]
        );

        foreach ($threads as $thread) {
            $messages = $DB->get_records(
                'local_subscriptions_inbox_message',
                ['threadid' => $thread->threadid],
                '
                    COALESCE(receivedat, sentat, timecreated) ASC,
                    id ASC
                '
            );

            $exportmessages = [];

            foreach ($messages as $message) {
                $exportmessages[] = [
                    'direction' => $message->direction,
                    'status' => $message->status,
                    'subject' => $message->subject,
                    'bodytext' => $message->bodytext,
                    'bodyhtml' => $message->bodyhtml,
                    'receivedat' =>
                        transform::datetime(
                            $message->receivedat
                        ),
                    'sentat' =>
                        transform::datetime(
                            $message->sentat
                        ),
                    'createdby' =>
                        $message->createdby,
                ];
            }

            writer::with_context(
                $systemcontext
            )->export_data(
                [
                    get_string(
                        'privacy:path:inbox',
                        'local_subscriptions'
                    ),
                    'thread_' .
                        (int)$thread->threadid,
                ],
                (object)[
                    'subject' =>
                        $thread->threadsubject,
                    'status' =>
                        $thread->threadstatus,
                    'priority' =>
                        $thread->priority,
                    'contactemail' =>
                        $thread->primaryemail,
                    'contactname' =>
                        $thread->displayname,
                    'matchstatus' =>
                        $thread->matchstatus,
                    'lastmessageat' =>
                        transform::datetime(
                            $thread->lastmessageat
                        ),
                    'messages' =>
                        $exportmessages,
                ]
            );
        }
    }

    public static function delete_data_for_all_users_in_context(
        context $context
    ): void {
        global $DB;

        if (
            $context->contextlevel !==
            CONTEXT_SYSTEM
        ) {
            return;
        }

        self::delete_all_inbox_data();
    }

    public static function delete_data_for_user(
        approved_contextlist $contextlist
    ): void {
        global $DB;

        $systemcontext =
            context_system::instance();

        if (
            !in_array(
                $systemcontext->id,
                $contextlist->get_contextids(),
                true
            )
        ) {
            return;
        }

        self::anonymize_user_references(
            (int)$contextlist->get_user()->id
        );
    }

    public static function get_users_in_context(
        userlist $userlist
    ): void {
        global $DB;

        $context = $userlist->get_context();

        if (
            $context->contextlevel !==
            CONTEXT_SYSTEM
        ) {
            return;
        }

        $sql = "
            SELECT DISTINCT matcheduserid AS userid
              FROM {local_subscriptions_inbox_contact}
             WHERE matcheduserid IS NOT NULL
        ";

        $userlist->add_from_sql(
            'userid',
            $sql,
            []
        );

        $sql = "
            SELECT DISTINCT assigneduserid AS userid
              FROM {local_subscriptions_inbox_thread}
             WHERE assigneduserid IS NOT NULL
        ";

        $userlist->add_from_sql(
            'userid',
            $sql,
            []
        );

        $sql = "
            SELECT DISTINCT createdby AS userid
              FROM {local_subscriptions_inbox_message}
             WHERE createdby IS NOT NULL
        ";

        $userlist->add_from_sql(
            'userid',
            $sql,
            []
        );
    }

    public static function delete_data_for_users(
        approved_userlist $userlist
    ): void {
        if (
            $userlist->get_context()->contextlevel !==
            CONTEXT_SYSTEM
        ) {
            return;
        }

        foreach ($userlist->get_userids() as $userid) {
            self::anonymize_user_references(
                (int)$userid
            );
        }
    }

    private static function anonymize_user_references(
        int $userid
    ): void {
        global $DB;

        $DB->set_field(
            'local_subscriptions_inbox_contact',
            'matcheduserid',
            null,
            ['matcheduserid' => $userid]
        );

        $DB->set_field(
            'local_subscriptions_inbox_thread',
            'assigneduserid',
            null,
            ['assigneduserid' => $userid]
        );

        $DB->set_field(
            'local_subscriptions_inbox_message',
            'createdby',
            null,
            ['createdby' => $userid]
        );
    }

    private static function delete_all_inbox_data(): void {
        global $DB;

        $systemcontext =
            context_system::instance();

        get_file_storage()->delete_area_files(
            $systemcontext->id,
            'local_subscriptions',
            'inbox_attachment'
        );

        $transaction =
            $DB->start_delegated_transaction();

        try {
            $DB->delete_records(
                'local_subscriptions_inbox_thread_tag'
            );
            $DB->delete_records(
                'local_subscriptions_inbox_tag'
            );
            $DB->delete_records(
                'local_subscriptions_inbox_attachment'
            );
            $DB->delete_records(
                'local_subscriptions_inbox_participant'
            );

            if (
                $DB->get_manager()->table_exists(
                    new \xmldb_table(
                        'local_subscriptions_inbox_remote'
                    )
                )
            ) {
                $DB->delete_records(
                    'local_subscriptions_inbox_remote'
                );
            }

            $DB->delete_records(
                'local_subscriptions_inbox_message'
            );
            $DB->delete_records(
                'local_subscriptions_inbox_thread'
            );
            $DB->delete_records(
                'local_subscriptions_inbox_contact'
            );
            $DB->delete_records(
                'local_subscriptions_inbox_sync_log'
            );
            $DB->delete_records(
                'local_subscriptions_inbox_team_member'
            );
            $DB->delete_records(
                'local_subscriptions_inbox_team'
            );
            $DB->delete_records(
                'local_subscriptions_inbox_account'
            );

            $transaction->allow_commit();
        } catch (\Throwable $exception) {
            $transaction->rollback(
                $exception
            );

            throw $exception;
        }
    }
}