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

    public function save_envelope(
        int $messageid,
        array $envelope
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

        $headers['draftenvelope'] = [
            'to' => array_values(
                $envelope['to'] ?? []
            ),
            'cc' => array_values(
                $envelope['cc'] ?? []
            ),
            'bcc' => array_values(
                $envelope['bcc'] ?? []
            ),
        ];

        $DB->update_record(
            self::TABLE,
            (object)[
                'id' => $messageid,
                'headersjson' => json_encode(
                    $headers,
                    JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES
                ),
                'timemodified' => time(),
            ]
        );
    }

    public function get_envelope(
        object $draft
    ): array {
        if (empty($draft->headersjson)) {
            return [
                'to' => [],
                'cc' => [],
                'bcc' => [],
            ];
        }

        $headers = json_decode(
            (string)$draft->headersjson,
            true
        ) ?: [];

        $envelope = $headers['draftenvelope']
            ?? [];

        return [
            'to' => array_values(
                $envelope['to'] ?? []
            ),
            'cc' => array_values(
                $envelope['cc'] ?? []
            ),
            'bcc' => array_values(
                $envelope['bcc'] ?? []
            ),
        ];
    }

    public function count_compose_drafts(
        int $actorid
    ): int {
        global $DB;

        return (int)$DB->count_records_sql(
            "
                SELECT COUNT(1)
                  FROM {local_subscriptions_inbox_message} m
                  JOIN {local_subscriptions_inbox_thread} t
                    ON t.id = m.threadid
                 WHERE m.status = 'draft'
                   AND m.direction = 'outbound'
                   AND m.createdby = :actorid
                   AND t.folder = 'DRAFTS'
                   AND t.locallydeleted = 0
            ",
            ['actorid' => $actorid]
        );
    }

    public function get_compose_drafts(
        int $actorid,
        int $limit = 50
    ): array {
        global $DB;

        return array_values(
            $DB->get_records_sql(
                "
                    SELECT
                        m.id AS draftid,
                        m.threadid,
                        m.accountid,
                        m.subject,
                        m.bodytext,
                        m.bodyhtml,
                        m.headersjson,
                        m.timemodified,
                        a.name AS accountname,
                        a.email AS accountemail
                      FROM {local_subscriptions_inbox_message} m
                      JOIN {local_subscriptions_inbox_thread} t
                        ON t.id = m.threadid
                      JOIN {local_subscriptions_inbox_account} a
                        ON a.id = m.accountid
                     WHERE m.status = 'draft'
                       AND m.direction = 'outbound'
                       AND m.createdby = :actorid
                       AND t.folder = 'DRAFTS'
                       AND t.locallydeleted = 0
                  ORDER BY m.timemodified DESC, m.id DESC
                ",
                ['actorid' => $actorid],
                0,
                max(1, $limit)
            )
        );
    }

    public function discard_compose_draft(
        int $threadid,
        int $actorid
    ): void {
        global $DB;

        $draft = $DB->get_record(
            self::TABLE,
            [
                'threadid' => $threadid,
                'status' => 'draft',
                'direction' => 'outbound',
                'createdby' => $actorid,
            ]
        );

        if (!$draft) {
            return;
        }

        $DB->set_field(
            'local_subscriptions_inbox_thread',
            'locallydeleted',
            1,
            ['id' => $threadid]
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

    public function record_sent_copy_result(
        int $messageid,
        ?string $folder,
        ?string $error
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

        $headers['sentcopy'] = [
            'folder' => $folder,
            'success' => $error === null,
            'error' => $error,
            'time' => time(),
        ];

        $DB->update_record(
            self::TABLE,
            (object)[
                'id' => $messageid,
                'headersjson' => json_encode(
                    $headers,
                    JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES
                ),
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