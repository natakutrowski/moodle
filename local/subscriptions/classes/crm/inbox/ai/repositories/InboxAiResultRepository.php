<?php

namespace local_subscriptions\crm\inbox\ai\repositories;

defined('MOODLE_INTERNAL') || die();

use local_subscriptions\crm\inbox\ai\dto\InboxAiRequest;
use local_subscriptions\crm\inbox\ai\dto\InboxAiResult;
use local_subscriptions\crm\inbox\ai\domain\InboxAiStatus;

final class InboxAiResultRepository {

    private const TABLE =
        'local_subscriptions_inbox_ai_result';

    public function find_fresh(
        string $cachekey,
        ?int $now = null
    ): ?InboxAiResult {
        global $DB;

        $now ??= time();

        $records =
            $DB->get_records_select(
                self::TABLE,
                'cachekey = :cachekey
                AND expiresat > :now
                AND status IN (
                    :successstatus,
                    :partialstatus
                )',
                [
                    'cachekey' =>
                        $cachekey,

                    'now' =>
                        $now,

                    'successstatus' => InboxAiStatus::SUCCESS,

                    'partialstatus' => InboxAiStatus::PARTIAL,
                ],
                'generatedat DESC, id DESC',
                '*',
                0,
                1
            );

        if (!$records) {
            return null;
        }

        return $this->map_result(
            reset($records)
        );
    }

    public function save(
        InboxAiRequest $request,
        InboxAiResult $result,
        string $promptversion,
        string $inputhash,
        string $cachekey,
        int $expiresat
    ): int {
        global $DB;

        $now = time();

        $record = (object)[
            'threadid' =>
                $request->threadid,

            'messageid' =>
                $request->messageid,

            'capability' =>
                $request->capability,

            'provider' =>
                $result->provider,

            'model' =>
                $result->model,

            'promptversion' =>
                $promptversion,

            'inputhash' =>
                $inputhash,

            'cachekey' =>
                $cachekey,

            'status' =>
                $result->status,

            'confidence' =>
                max(
                    0,
                    min(
                        1,
                        $result->confidence
                    )
                ),

            'datajson' =>
                $this->encode_json(
                    $result->data
                ),

            'warningsjson' =>
                $this->encode_json(
                    $result->warnings
                ),

            'metadatajson' =>
                $this->encode_json(
                    $result->metadata
                ),

            'inputtokens' =>
                (int)(
                    $result->metadata[
                        'inputtokens'
                    ]
                    ?? 0
                ),

            'outputtokens' =>
                (int)(
                    $result->metadata[
                        'outputtokens'
                    ]
                    ?? 0
                ),

            'totaltokens' =>
                (int)(
                    $result->metadata[
                        'totaltokens'
                    ]
                    ?? 0
                ),

            'requestid' =>
                $this->nullable_string(
                    $result->metadata[
                        'requestid'
                    ]
                    ?? null
                ),

            'errormessage' =>
                $this->sanitize_error(
                    $result->error
                ),

            'requestedby' =>
                $request->actorid,

            'generatedat' =>
                $result->generatedat
                ?? $now,

            'expiresat' =>
                $expiresat,

            'timecreated' =>
                $now,

            'timemodified' =>
                $now,
        ];

        return (int)$DB->insert_record(
            self::TABLE,
            $record
        );
    }

    /**
     * @return object[]
     */
    public function get_for_thread(
        int $threadid,
        ?string $capability = null
    ): array {
        global $DB;

        $conditions = [
            'threadid' => $threadid,
        ];

        if ($capability !== null) {
            $conditions['capability'] =
                $capability;
        }

        return array_values(
            $DB->get_records(
                self::TABLE,
                $conditions,
                'generatedat DESC, id DESC'
            )
        );
    }

    public function delete_expired(
        int $before,
        int $limit = 1000
    ): int {
        global $DB;

        $records = $DB->get_records_select(
            self::TABLE,
            'expiresat > 0
             AND expiresat <= :before',
            ['before' => $before],
            'expiresat ASC, id ASC',
            'id',
            0,
            max(1, $limit)
        );

        if (!$records) {
            return 0;
        }

        $ids = array_map(
            'intval',
            array_keys($records)
        );

        [$insql, $params] =
            $DB->get_in_or_equal(
                $ids,
                SQL_PARAMS_NAMED,
                'airesultid'
            );

        $DB->delete_records_select(
            self::TABLE,
            "id {$insql}",
            $params
        );

        return count($ids);
    }

    private function map_result(
        object $record
    ): InboxAiResult {
        return new InboxAiResult(
            (string)$record->status,
            (string)$record->capability,
            (string)$record->provider,
            $this->nullable_string(
                $record->model ?? null
            ),
            $this->decode_json(
                $record->datajson ?? null
            ),
            (float)$record->confidence,
            $this->decode_json(
                $record->warningsjson ?? null
            ),
            $this->nullable_string(
                $record->errormessage ?? null
            ),
            (int)$record->generatedat,
            $this->decode_json(
                $record->metadatajson ?? null
            )
        );
    }

    private function encode_json(
        array $value
    ): string {
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
        if (
            $value === null ||
            trim($value) === ''
        ) {
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
        } catch (\Throwable $exception) {
            return [];
        }
    }

    private function sanitize_error(
        ?string $error
    ): ?string {
        if ($error === null) {
            return null;
        }

        $error = clean_param(
            $error,
            PARAM_TEXT
        );

        $error = preg_replace(
            '/Bearer\s+[A-Za-z0-9._\-]+/i',
            'Bearer [redacted]',
            $error
        ) ?? $error;

        $error = preg_replace(
            '/sk-[A-Za-z0-9_\-]+/i',
            '[redacted-api-key]',
            $error
        ) ?? $error;

        $error = trim($error);

        if ($error === '') {
            return null;
        }

        if (
            \core_text::strlen($error) >
            1000
        ) {
            $error =
                \core_text::substr(
                    $error,
                    0,
                    1000
                ) .
                '…';
        }

        return $error;
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
}