<?php

namespace local_subscriptions\crm\inbox\repositories;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\dto\InboxMessageData;
use local_subscriptions\crm\inbox\dto\InboxRemoteMessageState;

final class InboxRemoteMessageRepository {

    private const TABLE =
        'local_subscriptions_inbox_remote';

    public function upsert(
        int $messageid,
        int $accountid,
        InboxMessageData $message
    ): void {
        global $DB;

        $providerkey =
            InboxMessageRepository::provider_key(
                $message
            );

        $existing = $DB->get_record(
            self::TABLE,
            [
                'accountid' => $accountid,
                'providerkey' => $providerkey,
            ]
        );

        $now = time();

        if ($existing) {
            $DB->update_record(
                self::TABLE,
                (object)[
                    'id' => (int)$existing->id,
                    'messageid' => $messageid,
                    'active' => 1,
                    'lastseenat' => $now,
                    'timemodified' => $now,
                ]
            );

            return;
        }

        $DB->insert_record(
            self::TABLE,
            (object)[
                'messageid' => $messageid,
                'accountid' => $accountid,
                'folder' => $message->folder,
                'uidvalidity' =>
                    (string)$message->uidvalidity,
                'provideruid' =>
                    (string)$message->provideruid,
                'providerkey' => $providerkey,
                'active' => 1,
                'firstseenat' => $now,
                'lastseenat' => $now,
                'timecreated' => $now,
                'timemodified' => $now,
            ]
        );
    }


    /**
     * @return object[]
     */
    public function active_for_folder(
        int $accountid,
        string $folder
    ): array {
        global $DB;

        $sql = "
            SELECT
                remote.*,
                message.threadid,
                message.providermessageid,
                message.isread,
                message.direction
              FROM {" . self::TABLE . "} remote
              JOIN {local_subscriptions_inbox_message} message
                ON message.id = remote.messageid
             WHERE remote.accountid = :accountid
               AND remote.folder = :folder
               AND remote.active = 1
          ORDER BY remote.id ASC
        ";

        return array_values(
            $DB->get_records_sql(
                $sql,
                [
                    'accountid' => $accountid,
                    'folder' => $folder,
                ]
            )
        );
    }

    public function touch(
        int $remoteid
    ): void {
        global $DB;

        $now = time();

        $DB->update_record(
            self::TABLE,
            (object)[
                'id' => $remoteid,
                'active' => 1,
                'lastseenat' => $now,
                'timemodified' => $now,
            ]
        );
    }

    public function upsert_state(
        int $messageid,
        int $accountid,
        InboxRemoteMessageState $state
    ): void {
        global $DB;

        $providerkey = $state->provider_key();
        $existing = $DB->get_record(
            self::TABLE,
            [
                'accountid' => $accountid,
                'providerkey' => $providerkey,
            ]
        );
        $now = time();

        if ($existing) {
            $DB->update_record(
                self::TABLE,
                (object)[
                    'id' => (int)$existing->id,
                    'messageid' => $messageid,
                    'active' => 1,
                    'lastseenat' => $now,
                    'timemodified' => $now,
                ]
            );
            return;
        }

        $DB->insert_record(
            self::TABLE,
            (object)[
                'messageid' => $messageid,
                'accountid' => $accountid,
                'folder' => $state->folder,
                'uidvalidity' => $state->uidvalidity,
                'provideruid' => $state->provideruid,
                'providerkey' => $providerkey,
                'active' => 1,
                'firstseenat' => $now,
                'lastseenat' => $now,
                'timecreated' => $now,
                'timemodified' => $now,
            ]
        );
    }

    public function deactivate_other_locations(
        int $messageid,
        string $exceptproviderkey
    ): void {
        global $DB;

        $sql = "
            UPDATE {" . self::TABLE . "}
               SET active = 0,
                   timemodified = :timemodified
             WHERE messageid = :messageid
               AND providerkey <> :providerkey
        ";

        $DB->execute(
            $sql,
            [
                'timemodified' => time(),
                'messageid' => $messageid,
                'providerkey' => $exceptproviderkey,
            ]
        );
    }

    public function deactivate(
        int $remoteid
    ): void {
        global $DB;

        $DB->update_record(
            self::TABLE,
            (object)[
                'id' => $remoteid,
                'active' => 0,
                'timemodified' => time(),
            ]
        );
    }

}