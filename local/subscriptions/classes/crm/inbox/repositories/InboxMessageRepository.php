<?php

namespace local_subscriptions\crm\inbox\repositories;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\dto\InboxMessageData;
use local_subscriptions\crm\inbox\dto\InboxRemoteMessageState;

final class InboxMessageRepository {

    private const TABLE =
        'local_subscriptions_inbox_message';

    public function find(int $id): ?object {
        global $DB;

        $record = $DB->get_record(
            self::TABLE,
            ['id' => $id]
        );

        return $record ?: null;
    }

    public function find_by_provider_key(
        int $accountid,
        string $providerkey
    ): ?object {
        global $DB;

        $record = $DB->get_record(
            self::TABLE,
            [
                'accountid' => $accountid,
                'providerkey' => $providerkey,
            ]
        );

        return $record ?: null;
    }

    public function exists_by_provider_key(
        int $accountid,
        string $providerkey
    ): bool {
        global $DB;

        return $DB->record_exists(
            self::TABLE,
            [
                'accountid' => $accountid,
                'providerkey' => $providerkey,
            ]
        );
    }

    public function create(
        int $accountid,
        int $threadid,
        string $providerkey,
        InboxMessageData $message
    ): object {
        global $DB;

        $now = time();

        $id = $DB->insert_record(
            self::TABLE,
            (object)[
                'threadid' => $threadid,
                'accountid' => $accountid,
                'providermessageid' =>
                    $message->providermessageid,
                'providerparentid' =>
                    $message->providerparentid,
                'folder' => $message->folder,
                'uidvalidity' => $message->uidvalidity,
                'provideruid' => $message->provideruid,
                'providerkey' => $providerkey,
                'identitykey' =>
                    self::identity_key($message),
                'direction' => $message->direction,
                'status' => $message->status,
                'subject' => $message->subject,
                'bodytext' => $message->bodytext,
                'bodyhtml' => $message->bodyhtml,
                'headersjson' =>
                    $this->encode_json(
                        $message->headers
                    ),
                'inreplyto' => $message->inreplyto,
                'referencesjson' =>
                    $this->encode_json(
                        $message->references
                    ),
                'receivedat' => $message->receivedat,
                'sentat' => $message->sentat,
                'isread' => $message->isread ? 1 : 0,
                'hasattachments' =>
                    !empty($message->attachments) ? 1 : 0,
                'checksum' => $message->checksum,
                'createdby' => null,
                'timecreated' => $now,
                'timemodified' => $now,
            ]
        );

        return $this->find((int)$id);
    }


    public function update_remote_state(
        int $messageid,
        InboxRemoteMessageState $state
    ): bool {
        global $DB;

        $current = $this->find($messageid);

        if (!$current) {
            return false;
        }

        $providerkey = $state->provider_key();
        $changed =
            (string)$current->folder !== $state->folder
            || (string)$current->uidvalidity !== $state->uidvalidity
            || (string)$current->provideruid !== $state->provideruid
            || (string)$current->providerkey !== $providerkey
            || (int)$current->isread !== ($state->isread ? 1 : 0);

        if (!$changed) {
            return false;
        }

        $DB->update_record(
            self::TABLE,
            (object)[
                'id' => $messageid,
                'folder' => $state->folder,
                'uidvalidity' => $state->uidvalidity,
                'provideruid' => $state->provideruid,
                'providerkey' => $providerkey,
                'isread' => $state->isread ? 1 : 0,
                'timemodified' => time(),
            ]
        );

        return true;
    }

    public function set_read_state(
        int $messageid,
        bool $read
    ): bool {
        global $DB;

        $current = $DB->get_field(
            self::TABLE,
            'isread',
            ['id' => $messageid]
        );

        if ($current === false) {
            return false;
        }

        $wanted = $read ? 1 : 0;

        if ((int)$current === $wanted) {
            return false;
        }

        $DB->update_record(
            self::TABLE,
            (object)[
                'id' => $messageid,
                'isread' => $wanted,
                'timemodified' => time(),
            ]
        );

        return true;
    }

    public static function provider_key(
        InboxMessageData $message
    ): string {
        return hash(
            'sha256',
            implode('|', [
                $message->folder,
                $message->uidvalidity ?? '',
                $message->provideruid ?? '',
            ])
        );
    }

    private function encode_json(array $data): string {
        return json_encode(
            $data,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        ) ?: '[]';
    }

    public function find_existing(
        int $accountid,
        InboxMessageData $message
    ): ?object {
        global $DB;

        $identitykey = self::identity_key($message);

        $existing = $DB->get_record(
            self::TABLE,
            [
                'accountid' => $accountid,
                'identitykey' => $identitykey,
            ]
        );

        if ($existing) {
            return $existing;
        }

        if (
            $message->providermessageid !== null &&
            trim($message->providermessageid) !== ''
        ) {
            $existing = $DB->get_record(
                self::TABLE,
                [
                    'accountid' => $accountid,
                    'providermessageid' =>
                        $message->providermessageid,
                ],
                '*',
                IGNORE_MULTIPLE
            );

            if ($existing) {
                $this->set_identity_key(
                    (int)$existing->id,
                    $identitykey
                );

                return $existing;
            }
        }

        if (
            $message->checksum !== null &&
            trim($message->checksum) !== ''
        ) {
            $existing = $DB->get_record(
                self::TABLE,
                [
                    'accountid' => $accountid,
                    'checksum' => $message->checksum,
                ],
                '*',
                IGNORE_MULTIPLE
            );

            if ($existing) {
                $this->set_identity_key(
                    (int)$existing->id,
                    $identitykey
                );

                return $existing;
            }
        }

        return null;
    }

    public static function identity_key(
        InboxMessageData $message
    ): string {
        $messageid = \core_text::strtolower(
            trim((string)$message->providermessageid)
        );

        if ($messageid !== '') {
            return hash(
                'sha256',
                'message-id|' . $messageid
            );
        }

        $checksum = trim(
            (string)$message->checksum
        );

        if ($checksum !== '') {
            return hash(
                'sha256',
                'checksum|' . $checksum
            );
        }

        return hash(
            'sha256',
            implode('|', [
                'fallback',
                \core_text::strtolower(
                    trim((string)$message->subject)
                ),
                (string)(
                    $message->receivedat
                    ?? $message->sentat
                    ?? 0
                ),
            ])
        );
    }

    public function set_identity_key(
        int $messageid,
        string $identitykey
    ): void {
        global $DB;

        $DB->set_field(
            self::TABLE,
            'identitykey',
            $identitykey,
            ['id' => $messageid]
        );
    }

}