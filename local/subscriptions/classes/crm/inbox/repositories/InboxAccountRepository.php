<?php

namespace local_subscriptions\crm\inbox\repositories;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\domain\InboxAccount;

final class InboxAccountRepository {

    private const TABLE =
        'local_subscriptions_inbox_account';

    public function find(int $id): ?InboxAccount {
        global $DB;

        $record = $DB->get_record(
            self::TABLE,
            ['id' => $id]
        );

        return $record
            ? $this->map($record)
            : null;
    }

    public function find_by_email(
        string $email
    ): ?InboxAccount {
        global $DB;

        $record = $DB->get_record(
            self::TABLE,
            [
                'email' => $this->normalize_email(
                    $email
                ),
            ]
        );

        return $record
            ? $this->map($record)
            : null;
    }

    /**
     * @return InboxAccount[]
     */
    public function get_enabled(): array {
        global $DB;

        $records = $DB->get_records(
            self::TABLE,
            ['enabled' => 1],
            'id ASC'
        );

        return array_values(
            array_map(
                fn(object $record): InboxAccount =>
                    $this->map($record),
                $records
            )
        );
    }

    /**
     * @return InboxAccount[]
     */
    public function get_all(): array {
        global $DB;

        $records = $DB->get_records(
            self::TABLE,
            [],
            'name ASC, id ASC'
        );

        return array_values(
            array_map(
                fn(object $record): InboxAccount =>
                    $this->map($record),
                $records
            )
        );
    }

    public function upsert(
        string $name,
        string $email,
        string $provider,
        string $credentialkey,
        array $configuration,
        bool $enabled
    ): InboxAccount {
        global $DB;

        $email = $this->normalize_email($email);
        $now = time();

        $existing = $DB->get_record(
            self::TABLE,
            ['email' => $email]
        );

        $record = (object)[
            'name' => trim($name),
            'email' => $email,
            'provider' => trim($provider),
            'enabled' => $enabled ? 1 : 0,
            'credentialkey' => trim($credentialkey),
            'configurationjson' =>
                $this->encode_json($configuration),
            'timemodified' => $now,
        ];

        if ($existing) {
            $record->id = (int)$existing->id;

            $DB->update_record(
                self::TABLE,
                $record
            );

            return $this->find(
                (int)$existing->id
            );
        }

        $record->syncstatejson = null;
        $record->lastsyncedat = null;
        $record->lasterrorat = null;
        $record->lasterror = null;
        $record->timecreated = $now;

        $id = $DB->insert_record(
            self::TABLE,
            $record
        );

        return $this->find((int)$id);
    }

    public function update_sync_state(
        int $accountid,
        array $syncstate,
        ?int $lastsyncedat = null
    ): void {
        global $DB;

        $DB->update_record(
            self::TABLE,
            (object)[
                'id' => $accountid,
                'syncstatejson' =>
                    $this->encode_json($syncstate),
                'lastsyncedat' =>
                    $lastsyncedat ?? time(),
                'lasterrorat' => null,
                'lasterror' => null,
                'timemodified' => time(),
            ]
        );
    }

    public function record_error(
        int $accountid,
        string $message
    ): void {
        global $DB;

        $DB->update_record(
            self::TABLE,
            (object)[
                'id' => $accountid,
                'lasterrorat' => time(),
                'lasterror' => $message,
                'timemodified' => time(),
            ]
        );
    }

    public function set_enabled(
        int $accountid,
        bool $enabled
    ): void {
        global $DB;

        $DB->set_field(
            self::TABLE,
            'enabled',
            $enabled ? 1 : 0,
            ['id' => $accountid]
        );

        $DB->set_field(
            self::TABLE,
            'timemodified',
            time(),
            ['id' => $accountid]
        );
    }

    private function map(object $record): InboxAccount {
        return new InboxAccount(
            (int)$record->id,
            (string)$record->name,
            (string)$record->email,
            (string)$record->provider,
            (bool)$record->enabled,
            $this->nullable_string(
                $record->credentialkey ?? null
            ),
            $this->decode_json(
                $record->configurationjson ?? null
            ),
            $this->decode_json(
                $record->syncstatejson ?? null
            ),
            isset($record->lastsyncedat)
                ? (int)$record->lastsyncedat
                : null,
            isset($record->lasterrorat)
                ? (int)$record->lasterrorat
                : null,
            $this->nullable_string(
                $record->lasterror ?? null
            )
        );
    }

    private function normalize_email(
        string $email
    ): string {
        return \core_text::strtolower(
            trim($email)
        );
    }

    private function encode_json(array $value): string {
        return json_encode(
            $value,
            JSON_THROW_ON_ERROR |
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );
    }

    private function decode_json(
        ?string $value
    ): array {
        if ($value === null || trim($value) === '') {
            return [];
        }

        try {
            $decoded = json_decode(
                $value,
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            return is_array($decoded)
                ? $decoded
                : [];
        } catch (\JsonException $exception) {
            return [];
        }
    }

    private function nullable_string(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim((string)$value);

        return $value !== ''
            ? $value
            : null;
    }

    public function update_configuration(
        int $accountid,
        array $configuration
    ): void {
        global $DB;

        $DB->update_record(
            self::TABLE,
            (object)[
                'id' => $accountid,
                'configurationjson' =>
                    $this->encode_json($configuration),
                'timemodified' => time(),
            ]
        );
    }

}