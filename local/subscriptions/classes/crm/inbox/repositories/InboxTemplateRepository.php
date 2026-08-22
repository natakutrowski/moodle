<?php

declare(strict_types=1);

namespace local_subscriptions\crm\inbox\repositories;

defined('MOODLE_INTERNAL') || die();

final class InboxTemplateRepository {

    private const TABLE =
        'local_subscriptions_inbox_template';

    public function find(int $id): ?object {
        global $DB;

        $record = $DB->get_record(
            self::TABLE,
            ['id' => $id]
        );

        return $record ?: null;
    }

    /**
     * @return object[]
     */
    public function get_all(
        ?string $type = null
    ): array {
        global $DB;

        $conditions = [];

        if ($type !== null) {
            $conditions['type'] = $type;
        }

        return array_values(
            $DB->get_records(
                self::TABLE,
                $conditions,
                'type ASC, sortorder ASC, name ASC, id ASC'
            )
        );
    }

    /**
     * @return object[]
     */
    public function get_enabled_for_account(
        int $accountid,
        string $type
    ): array {
        global $DB;

        $sql = "
            SELECT *
              FROM {" . self::TABLE . "}
             WHERE enabled = 1
               AND type = :type
               AND (
                    accountid IS NULL
                    OR accountid = :accountid
               )
          ORDER BY
                CASE
                    WHEN accountid = :accountsort
                    THEN 0
                    ELSE 1
                END,
                sortorder ASC,
                name ASC,
                id ASC
        ";

        return array_values(
            $DB->get_records_sql(
                $sql,
                [
                    'type' => $type,
                    'accountid' => $accountid,
                    'accountsort' => $accountid,
                ]
            )
        );
    }

    public function upsert(
        ?int $id,
        ?int $accountid,
        string $type,
        string $name,
        ?string $subject,
        ?string $bodytext,
        ?string $bodyhtml,
        bool $enabled,
        int $sortorder,
        int $actorid
    ): object {
        global $DB;

        $now = time();

        $record = (object)[
            'accountid' =>
                $accountid !== null && $accountid > 0
                    ? $accountid
                    : null,
            'type' => $type,
            'name' => trim($name),
            'subject' =>
                $subject !== null
                    ? trim($subject)
                    : null,
            'bodytext' =>
                $bodytext !== null
                    ? $bodytext
                    : null,
            'bodyhtml' =>
                $bodyhtml !== null
                    ? $bodyhtml
                    : null,
            'enabled' => $enabled ? 1 : 0,
            'sortorder' => $sortorder,
            'timemodified' => $now,
        ];

        if ($id !== null && $id > 0) {
            $existing = $this->find($id);

            if (!$existing) {
                throw new \moodle_exception(
                    'crm_inbox_template_not_found_o9',
                    'local_subscriptions'
                );
            }

            $record->id = $id;

            $DB->update_record(
                self::TABLE,
                $record
            );

            return $this->find($id);
        }

        $record->createdby = $actorid;
        $record->timecreated = $now;

        $newid = $DB->insert_record(
            self::TABLE,
            $record
        );

        return $this->find(
            (int)$newid
        );
    }

    public function delete(int $id): void {
        global $DB;

        $DB->delete_records(
            self::TABLE,
            ['id' => $id]
        );
    }
}
