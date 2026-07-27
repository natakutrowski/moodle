<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\shadow\reporting;

defined('MOODLE_INTERNAL') || die();

/** Read-only search service for persisted Commerce Shadow runs. */
final class CommerceShadowSearchService {
    private const TABLE = 'local_subs_commerce_shadow';

    public function search(CommerceShadowSearchCriteria $criteria): array {
        global $DB;

        [$where, $params] = $this->database_conditions($criteria);
        $records = $DB->get_records_select(
            self::TABLE,
            $where,
            $params,
            'timecreated DESC, id DESC',
            '*',
            0,
            0
        );

        $results = [];
        foreach ($records as $record) {
            $row = $this->normalize_record($record);
            if (!$this->matches_payload_filters($row, $criteria)) {
                continue;
            }
            $results[] = $row;
        }

        return array_slice($results, $criteria->offset, $criteria->limit);
    }

    private function database_conditions(CommerceShadowSearchCriteria $criteria): array {
        $conditions = [];
        $params = [];
        $mapping = [
            'purchasereference' => $criteria->purchasereference,
            'source' => $criteria->source,
            'entrypoint' => $criteria->entrypoint,
            'comparisonstatus' => $criteria->comparisonstatus,
            'classification' => $criteria->classification,
        ];
        foreach ($mapping as $field => $value) {
            if ($value === null || trim($value) === '') {
                continue;
            }
            $conditions[] = $field . ' = :' . $field;
            $params[$field] = trim($value);
        }
        return [$conditions === [] ? '1 = 1' : implode(' AND ', $conditions), $params];
    }

    private function normalize_record(\stdClass $record): array {
        return [
            'id' => (int) $record->id,
            'executionreference' => (string) $record->executionreference,
            'purchasereference' => (string) $record->purchasereference,
            'source' => (string) $record->source,
            'entrypoint' => (string) $record->entrypoint,
            'comparisonstatus' => (string) $record->comparisonstatus,
            'classification' => (string) $record->classification,
            'legacy' => $this->decode((string) $record->legacyjson),
            'native' => $this->decode((string) $record->nativejson),
            'differences' => $this->decode((string) $record->differencesjson),
            'errorclass' => $record->errorclass === null ? null : (string) $record->errorclass,
            'errormessage' => $record->errormessage === null ? null : (string) $record->errormessage,
            'timestarted' => (int) $record->timestarted,
            'timefinished' => (int) $record->timefinished,
            'timecreated' => (int) $record->timecreated,
            'durationms' => max(0, ((int) $record->timefinished - (int) $record->timestarted) * 1000),
        ];
    }

    private function matches_payload_filters(array $row, CommerceShadowSearchCriteria $criteria): bool {
        if ($criteria->beneficiaryuserid !== null && !$this->payload_contains_user($row, $criteria->beneficiaryuserid)) {
            return false;
        }
        if ($criteria->family !== null && trim($criteria->family) !== '' && !$this->payload_contains_family($row, trim($criteria->family))) {
            return false;
        }
        return true;
    }

    private function payload_contains_user(array $row, int $userid): bool {
        foreach ([$row['legacy'], $row['native']] as $payload) {
            $effects = $payload['effects'] ?? [];
            if (!is_array($effects)) {
                continue;
            }
            foreach ($effects as $effect) {
                if (!is_array($effect)) {
                    continue;
                }
                if ((int) ($effect['beneficiaryuserid'] ?? 0) === $userid) {
                    return true;
                }
            }
        }
        return false;
    }

    private function payload_contains_family(array $row, string $family): bool {
        $family = strtolower($family);
        foreach ([$row['legacy'], $row['native']] as $payload) {
            $effects = $payload['effects'] ?? [];
            if (!is_array($effects)) {
                continue;
            }
            foreach ($effects as $effect) {
                if (!is_array($effect)) {
                    continue;
                }
                $type = strtolower((string) ($effect['type'] ?? $effect['granttype'] ?? ''));
                if ($family === 'subscription' && $type === 'course_access') {
                    return true;
                }
                if ($family === 'digital' && $type === 'digital_download') {
                    return true;
                }
                if ($type === $family) {
                    return true;
                }
            }
        }
        return false;
    }

    private function decode(string $json): array {
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }
}
