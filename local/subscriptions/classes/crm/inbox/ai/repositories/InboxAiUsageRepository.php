<?php

namespace local_subscriptions\crm\inbox\ai\repositories;

defined('MOODLE_INTERNAL') || die();

final class InboxAiUsageRepository {

    private const TABLE =
        'local_subscriptions_inbox_ai_result';

    public function count_since(
        int $since,
        ?int $actorid = null,
        bool $providerrequestsOnly = false
    ): int {
        global $DB;

        $conditions = [
            'timecreated >= :since',
        ];

        $params = [
            'since' => $since,
        ];

        if ($actorid !== null && $actorid > 0) {
            $conditions[] =
                'requestedby = :actorid';

            $params['actorid'] = $actorid;
        }

        if ($providerrequestsOnly) {
            $conditions[] =
                'provider <> :fallbackprovider';

            $params['fallbackprovider'] =
                'fallback';
        }

        return (int)$DB->count_records_select(
            self::TABLE,
            implode(' AND ', $conditions),
            $params
        );
    }

    public function count_failures_since(
        int $since,
        ?string $provider = null
    ): int {
        global $DB;

        $conditions = [
            'timecreated >= :since',
            'status = :status',
        ];

        $params = [
            'since' => $since,
            'status' => 'failed',
        ];

        if ($provider !== null) {
            $conditions[] =
                'provider = :provider';

            $params['provider'] = $provider;
        }

        return (int)$DB->count_records_select(
            self::TABLE,
            implode(' AND ', $conditions),
            $params
        );
    }

    public function token_usage_since(
        int $since,
        ?string $provider = null
    ): array {
        global $DB;

        $conditions = [
            'timecreated >= :since',
        ];

        $params = [
            'since' => $since,
        ];

        if ($provider !== null) {
            $conditions[] =
                'provider = :provider';

            $params['provider'] = $provider;
        }

        $sql = "
            SELECT
                COALESCE(SUM(inputtokens), 0)
                    AS inputtokens,
                COALESCE(SUM(outputtokens), 0)
                    AS outputtokens,
                COALESCE(SUM(totaltokens), 0)
                    AS totaltokens
              FROM {" . self::TABLE . "}
             WHERE " .
            implode(' AND ', $conditions);

        $record = $DB->get_record_sql(
            $sql,
            $params
        );

        return [
            'inputtokens' =>
                (int)($record->inputtokens ?? 0),
            'outputtokens' =>
                (int)($record->outputtokens ?? 0),
            'totaltokens' =>
                (int)($record->totaltokens ?? 0),
        ];
    }

    public function latest(): ?object {
        global $DB;

        $records = $DB->get_records(
            self::TABLE,
            [],
            'timecreated DESC, id DESC',
            '*',
            0,
            1
        );

        return $records
            ? reset($records)
            : null;
    }
}