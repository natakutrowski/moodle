<?php

namespace local_subscriptions\crm\inbox\repositories;

defined('MOODLE_INTERNAL') || die();

final class InboxDraftRepository {

    private const TABLE =
        'local_subscriptions_inbox_message';

    public function find_for_thread(
        int $threadid
    ): ?object {
        global $DB;

        $record = $DB->get_record(
            self::TABLE,
            [
                'threadid' => $threadid,
                'status' => 'draft',
            ],
            '*',
            IGNORE_MULTIPLE
        );

        return $record ?: null;
    }

    public function save(
        int $accountid,
        int $threadid,
        string $subject,
        string $bodytext,
        ?string $bodyhtml,
        int $actorid
    ): object {
        global $DB;

        $existing = $this->find_for_thread(
            $threadid
        );

        $now = time();

        $record = (object)[
            'threadid' => $threadid,
            'accountid' => $accountid,
            'direction' => 'outbound',
            'status' => 'draft',
            'subject' => $subject,
            'bodytext' => $bodytext,
            'bodyhtml' => $bodyhtml,
            'isread' => 1,
            'hasattachments' => 0,
            'createdby' => $actorid,
            'timemodified' => $now,
        ];

        if ($existing) {
            $record->id = (int)$existing->id;

            $DB->update_record(
                self::TABLE,
                $record
            );

            return $DB->get_record(
                self::TABLE,
                ['id' => $record->id],
                '*',
                MUST_EXIST
            );
        }

        $record->folder = null;
        $record->uidvalidity = null;
        $record->provideruid = null;
        $record->providerkey = null;
        $record->providermessageid = null;
        $record->providerparentid = null;
        $record->headersjson = null;
        $record->inreplyto = null;
        $record->referencesjson = null;
        $record->receivedat = null;
        $record->sentat = null;
        $record->checksum = null;
        $record->timecreated = $now;

        $id = $DB->insert_record(
            self::TABLE,
            $record
        );

        return $DB->get_record(
            self::TABLE,
            ['id' => $id],
            '*',
            MUST_EXIST
        );
    }

    public function mark_sending(
        int $messageid
    ): void {
        $this->set_status(
            $messageid,
            'sending'
        );
    }

    public function mark_sent(
        int $messageid,
        ?string $providermessageid,
        int $sentat
    ): void {
        global $DB;

        $DB->update_record(
            self::TABLE,
            (object)[
                'id' => $messageid,
                'status' => 'sent',
                'providermessageid' =>
                    $providermessageid,
                'sentat' => $sentat,
                'timemodified' => time(),
            ]
        );
    }

    public function mark_failed(
        int $messageid,
        string $error
    ): void {
        global $DB;

        $message = $DB->get_record(
            self::TABLE,
            ['id' => $messageid],
            'id,headersjson',
            MUST_EXIST
        );

        $headers = [];

        if (!empty($message->headersjson)) {
            $headers = json_decode(
                $message->headersjson,
                true
            ) ?: [];
        }

        $headers['senderror'] = $error;

        $DB->update_record(
            self::TABLE,
            (object)[
                'id' => $messageid,
                'status' => 'failed',
                'headersjson' => json_encode(
                    $headers,
                    JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES
                ),
                'timemodified' => time(),
            ]
        );
    }

    private function set_status(
        int $messageid,
        string $status
    ): void {
        global $DB;

        $DB->update_record(
            self::TABLE,
            (object)[
                'id' => $messageid,
                'status' => $status,
                'timemodified' => time(),
            ]
        );
    }
}