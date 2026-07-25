<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\idempotency;

defined('MOODLE_INTERNAL') || die();

use dml_exception;

final class CommerceIdempotencyRepository {
    private const TABLE = 'local_subs_commerce_idem';

    public function find(string $scope, string $key): ?CommerceIdempotencyRecord {
        global $DB;

        $record = $DB->get_record(self::TABLE, [
            'scope' => $scope,
            'idempotencykey' => $key,
        ]);

        return $record ? $this->map($record) : null;
    }

    public function reserve(string $scope, string $key, string $payloadhash, int $ttl = 300): CommerceIdempotencyRecord {
        global $DB;

        $existing = $this->find($scope, $key);
        if ($existing !== null) {
            if (!hash_equals($existing->get_payload_hash(), $payloadhash)) {
                throw new \RuntimeException('Idempotency key reused with a different payload.');
            }
            return $existing;
        }

        $now = time();
        try {
            $id = (int)$DB->insert_record(self::TABLE, (object)[
                'scope' => $scope,
                'idempotencykey' => $key,
                'payloadhash' => $payloadhash,
                'status' => 'processing',
                'resultjson' => null,
                'errormessage' => null,
                'lockeduntil' => $now + $ttl,
                'attempts' => 1,
                'timecreated' => $now,
                'timemodified' => $now,
                'timecompleted' => null,
            ]);
        } catch (dml_exception $exception) {
            $existing = $this->find($scope, $key);
            if ($existing !== null && hash_equals($existing->get_payload_hash(), $payloadhash)) {
                return $existing;
            }
            throw $exception;
        }

        $record = $this->find($scope, $key);
        if ($record === null) {
            throw new \RuntimeException('Unable to reserve Commerce idempotency key.');
        }
        return $record;
    }

    public function complete(int $id, array $result): void {
        global $DB;
        $now = time();
        $DB->update_record(self::TABLE, (object)[
            'id' => $id,
            'status' => 'completed',
            'resultjson' => json_encode($result, JSON_THROW_ON_ERROR),
            'errormessage' => null,
            'lockeduntil' => 0,
            'timemodified' => $now,
            'timecompleted' => $now,
        ]);
    }

    public function fail(int $id, string $message): void {
        global $DB;
        $DB->update_record(self::TABLE, (object)[
            'id' => $id,
            'status' => 'failed',
            'errormessage' => $message,
            'lockeduntil' => 0,
            'timemodified' => time(),
        ]);
    }

    private function map(\stdClass $record): CommerceIdempotencyRecord {
        $result = null;
        if (!empty($record->resultjson)) {
            $decoded = json_decode($record->resultjson, true);
            $result = is_array($decoded) ? $decoded : null;
        }

        return new CommerceIdempotencyRecord(
            (int)$record->id,
            (string)$record->scope,
            (string)$record->idempotencykey,
            (string)$record->payloadhash,
            (string)$record->status,
            $result,
            $record->errormessage !== null ? (string)$record->errormessage : null
        );
    }
}
